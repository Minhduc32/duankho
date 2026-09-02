<?php

namespace App\Services;

use App\Models\Issue;
use App\Models\IssueDetail;
use App\Models\IssueAllocation;
use App\Models\InventoryCarton;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Exception;

class OutboundService
{
    /**
     * Thuật toán gợi ý phân bổ thùng hàng theo FIFO/LIFO
     * 
     * @param int $productId
     * @param int $requestedPieces
     * @param string $rule
     * @return array
     */
    public function suggest(int $productId, int $requestedPieces, string $rule = 'FIFO'): array
    {
        $orderByField = 'received_at';
        $direction = ($rule === 'LIFO') ? 'DESC' : 'ASC';

        // Lấy tất cả thùng hàng đang trong kho của sản phẩm này
        $cartons = InventoryCarton::join('locations as l', 'inventory_cartons.location_id', '=', 'l.id')
            ->leftJoin('receipt_details as rd', 'inventory_cartons.receipt_detail_id', '=', 'rd.id')
            ->select(
                'inventory_cartons.*',
                'l.zone', 'l.rack', 'l.level',
                'rd.lot_number'
            )
            ->where('inventory_cartons.product_id', $productId)
            ->where('inventory_cartons.status', 'IN_STOCK')
            ->where('inventory_cartons.current_pieces', '>', 0)
            ->orderBy("inventory_cartons.{$orderByField}", $direction)
            ->orderBy("inventory_cartons.id", $direction)
            ->get();

        $allocations = [];
        $remainingNeeded = $requestedPieces;

        foreach ($cartons as $carton) {
            if ($remainingNeeded <= 0) {
                break;
            }

            $currentPieces = $carton->current_pieces;
            $piecesToTake = min($currentPieces, $remainingNeeded);

            $allocations[] = [
                'inventory_carton_id' => $carton->id,
                'carton_code'         => $carton->carton_code,
                'lot_number'          => $carton->lot_number,
                'zone'                => $carton->zone,
                'rack'                => $carton->rack,
                'level'               => $carton->level,
                'current_pieces'      => $currentPieces,
                'pieces_issued'       => $piecesToTake
            ];

            $remainingNeeded -= $piecesToTake;
        }

        return [
            'allocations'      => $allocations,
            'remaining_needed' => $remainingNeeded,
        ];
    }

    /**
     * Tạo phiếu xuất kho (có kiểm tra tồn khả dụng và trừ tồn thực tế)
     * 
     * @param string $orderNumber
     * @param string $issueDate
     * @param int $creatorId
     * @param string|null $note
     * @param array $items
     * @param string $rule
     * @return Issue
     * @throws Exception
     */
    public function createIssue(string $orderNumber, string $issueDate, int $creatorId, ?string $note, array $items, string $rule = 'FIFO')
    {
        return DB::transaction(function () use ($orderNumber, $issueDate, $creatorId, $note, $items, $rule) {
            // Sinh mã phiếu xuất tự động
            $datePrefix = date('ymd', strtotime($issueDate));
            $count = Issue::where('issue_code', 'LIKE', "GDN-{$datePrefix}-%")->count() + 1;
            $issueCode = "GDN-{$datePrefix}-" . str_pad($count, 3, '0', STR_PAD_LEFT);

            // 1. Tạo Issue
            $issue = Issue::create([
                'issue_code'   => $issueCode,
                'order_number' => $orderNumber,
                'issue_date'   => $issueDate,
                'creator_id'   => $creatorId,
                'status'       => 'COMPLETED',
                'note'         => $note
            ]);

            // 2. Xử lý từng dòng mặt hàng
            foreach ($items as $item) {
                $productId = (int)$item['product_id'];
                $requestedPieces = (int)$item['qty'];

                // Chạy thuật toán gợi ý để phân bổ thùng và trừ tồn kho thực tế
                $suggestResult = $this->suggest($productId, $requestedPieces, $rule);

                if ($suggestResult['remaining_needed'] > 0) {
                    throw new Exception("Sản phẩm ID #{$productId} không đủ hàng tồn kho khả dụng để xuất. Thiếu {$suggestResult['remaining_needed']} cái.");
                }

                // Lưu dòng chi tiết phiếu xuất
                $detail = IssueDetail::create([
                    'issue_id'         => $issue->id,
                    'product_id'       => $productId,
                    'requested_pieces' => $requestedPieces,
                    'actual_pieces'    => $requestedPieces
                ]);

                // Lưu phân bổ và trừ tồn thực tế
                foreach ($suggestResult['allocations'] as $alloc) {
                    $cartonId = $alloc['inventory_carton_id'];
                    $piecesIssued = $alloc['pieces_issued'];

                    // Ghi nhận phân bổ
                    IssueAllocation::create([
                        'issue_detail_id'     => $detail->id,
                        'inventory_carton_id' => $cartonId,
                        'pieces_issued'       => $piecesIssued
                    ]);

                    // Trừ tồn kho trong thùng hàng
                    $carton = InventoryCarton::findOrFail($cartonId);
                    $newPieces = $carton->current_pieces - $piecesIssued;
                    $carton->update([
                        'current_pieces' => $newPieces,
                        'status'         => ($newPieces === 0) ? 'EXPORTED' : 'IN_STOCK'
                    ]);
                }
            }

            // Ghi Audit Log
            AuditLog::logAction($creatorId, 'CREATE', 'issues', $issue->id, null, [
                'issue_code' => $issueCode,
                'items_count' => count($items)
            ]);

            return $issue;
        });
    }

    /**
     * Cập nhật phiếu xuất kho và phân bổ lại (có khôi phục và trừ tồn kho)
     */
    public function updateIssue(int $issueId, string $orderNumber, string $issueDate, int $updaterId, ?string $note, array $items, string $rule = 'FIFO')
    {
        return DB::transaction(function () use ($issueId, $orderNumber, $issueDate, $updaterId, $note, $items, $rule) {
            $issue = Issue::findOrFail($issueId);
            $oldValues = $issue->toArray();

            // 1. Hoàn trả số lượng cũ về các thùng chứa ban đầu
            $details = IssueDetail::where('issue_id', $issueId)->get();
            foreach ($details as $d) {
                $allocations = IssueAllocation::where('issue_detail_id', $d->id)->get();
                foreach ($allocations as $alloc) {
                    $carton = InventoryCarton::findOrFail($alloc->inventory_carton_id);
                    $newPieces = $carton->current_pieces + $alloc->pieces_issued;
                    $carton->update([
                        'current_pieces' => $newPieces,
                        'status'         => 'IN_STOCK'
                    ]);
                    $alloc->delete();
                }
                $d->delete();
            }

            // 2. Chạy gợi ý phân bổ mới theo quy tắc được gửi lên
            foreach ($items as $item) {
                $productId = (int)$item['product_id'];
                $requestedPieces = (int)$item['qty'];

                $suggestResult = $this->suggest($productId, $requestedPieces, $rule);

                if ($suggestResult['remaining_needed'] > 0) {
                    throw new Exception("Sản phẩm ID #{$productId} không đủ hàng tồn kho khả dụng để xuất. Thiếu {$suggestResult['remaining_needed']} cái.");
                }

                $detail = IssueDetail::create([
                    'issue_id'         => $issue->id,
                    'product_id'       => $productId,
                    'requested_pieces' => $requestedPieces,
                    'actual_pieces'    => $requestedPieces
                ]);

                foreach ($suggestResult['allocations'] as $alloc) {
                    $cartonId = $alloc['inventory_carton_id'];
                    $piecesIssued = $alloc['pieces_issued'];

                    IssueAllocation::create([
                        'issue_detail_id'     => $detail->id,
                        'inventory_carton_id' => $cartonId,
                        'pieces_issued'       => $piecesIssued
                    ]);

                    $carton = InventoryCarton::findOrFail($cartonId);
                    $newPieces = $carton->current_pieces - $piecesIssued;
                    $carton->update([
                        'current_pieces' => $newPieces,
                        'status'         => ($newPieces === 0) ? 'EXPORTED' : 'IN_STOCK'
                    ]);
                }
            }

            // 3. Cập nhật thông tin phiếu xuất chính
            $issue->update([
                'order_number' => $orderNumber,
                'issue_date'   => $issueDate,
                'note'         => $note
            ]);

            AuditLog::logAction($updaterId, 'UPDATE', 'issues', $issue->id, $oldValues, [
                'issue_code' => $issue->issue_code,
                'items_count' => count($items)
            ]);

            return $issue;
        });
    }

    /**
     * Xóa phiếu xuất kho và hoàn trả hàng về các thùng chứa
     */
    public function deleteIssue(int $issueId, int $deleterId)
    {
        return DB::transaction(function () use ($issueId, $deleterId) {
            $issue = Issue::findOrFail($issueId);
            $oldValues = $issue->toArray();

            // Hoàn trả số lượng hàng về các thùng chứa ban đầu
            $details = IssueDetail::where('issue_id', $issueId)->get();
            foreach ($details as $d) {
                $allocations = IssueAllocation::where('issue_detail_id', $d->id)->get();
                foreach ($allocations as $alloc) {
                    $carton = InventoryCarton::findOrFail($alloc->inventory_carton_id);
                    $newPieces = $carton->current_pieces + $alloc->pieces_issued;
                    $carton->update([
                        'current_pieces' => $newPieces,
                        'status'         => 'IN_STOCK'
                    ]);
                    $alloc->delete();
                }
                $d->delete();
            }

            $issue->delete();

            AuditLog::logAction($deleterId, 'DELETE', 'issues', $issueId, $oldValues, null);
        });
    }
}
