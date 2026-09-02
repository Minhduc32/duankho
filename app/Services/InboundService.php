<?php

namespace App\Services;

use App\Models\Receipt;
use App\Models\ReceiptDetail;
use App\Models\InventoryCarton;
use App\Models\Product;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;

class InboundService
{
    /**
     * Lập phiếu nhập kho và xếp thùng
     * 
     * @param string $poNumber
     * @param string $receiptDate
     * @param int $creatorId
     * @param string|null $note
     * @param array $items
     * @return Receipt
     */
    public function createReceipt(string $poNumber, string $receiptDate, int $creatorId, ?string $note, array $items)
    {
        return DB::transaction(function () use ($poNumber, $receiptDate, $creatorId, $note, $items) {
            // Sinh mã phiếu tự động
            $datePrefix = date('ymd', strtotime($receiptDate));
            $count = Receipt::where('receipt_code', 'LIKE', "GRN-{$datePrefix}-%")->count() + 1;
            $receiptCode = "GRN-{$datePrefix}-" . str_pad($count, 3, '0', STR_PAD_LEFT);

            // 1. Tạo Receipt
            $receipt = Receipt::create([
                'receipt_code' => $receiptCode,
                'po_number'    => $poNumber,
                'receipt_date' => $receiptDate,
                'creator_id'   => $creatorId,
                'status'       => 'COMPLETED',
                'note'         => $note
            ]);

            // 2. Tạo chi tiết phiếu và thùng hàng
            foreach ($items as $item) {
                $productId = $item['product_id'];
                $lotNumber = trim($item['lot_number']);
                $price = $item['price'] ?? 0;
                $cartons = $item['cartons']; // Mảng: [['pieces' => X, 'location_id' => Y], ...]

                $totalCartons = count($cartons);
                $totalPieces = array_sum(array_column($cartons, 'pieces'));

                // Tạo ReceiptDetail
                $detail = ReceiptDetail::create([
                    'receipt_id'    => $receipt->id,
                    'product_id'    => $productId,
                    'lot_number'    => $lotNumber,
                    'total_cartons' => $totalCartons,
                    'total_pieces'  => $totalPieces,
                    'price'         => $price
                ]);

                // Lấy SKU để sinh mã thùng
                $product = Product::findOrFail($productId);
                $cartonIndex = 1;

                foreach ($cartons as $c) {
                    $pieces = (int)$c['pieces'];
                    $locationId = (int)$c['location_id'];

                    // Định dạng mã thùng: C-LOT-SKU-INDEX
                    $cartonCode = "C-{$lotNumber}-{$product->sku}-" . str_pad($cartonIndex++, 2, '0', STR_PAD_LEFT);

                    InventoryCarton::create([
                        'receipt_detail_id' => $detail->id,
                        'product_id'        => $productId,
                        'carton_code'       => $cartonCode,
                        'original_pieces'   => $pieces,
                        'current_pieces'    => $pieces,
                        'location_id'       => $locationId,
                        'status'            => 'IN_STOCK',
                        'received_at'       => $receiptDate
                    ]);
                }
            }

            // Ghi Audit Log
            AuditLog::logAction($creatorId, 'CREATE', 'receipts', $receipt->id, null, [
                'receipt_code' => $receiptCode,
                'items_count' => count($items)
            ]);

            return $receipt;
        });
    }

    /**
     * Kiểm tra xem phiếu nhập đã phát sinh giao dịch xuất hoặc phân bổ chưa
     */
    public function isReceiptIssued(int $receiptId): bool
    {
        $hasIssuedCartons = InventoryCarton::whereIn('receipt_detail_id', function($query) use ($receiptId) {
            $query->select('id')->from('receipt_details')->where('receipt_id', $receiptId);
        })->where(function($q) {
            $q->where('status', '!=', 'IN_STOCK')
              ->orWhereColumn('current_pieces', '<', 'original_pieces');
        })->exists();

        if ($hasIssuedCartons) {
            return true;
        }

        return \App\Models\IssueAllocation::whereIn('inventory_carton_id', function($query) use ($receiptId) {
            $query->select('ic.id')
                ->from('inventory_cartons as ic')
                ->join('receipt_details as rd', 'ic.receipt_detail_id', '=', 'rd.id')
                ->where('rd.receipt_id', $receiptId);
        })->exists();
    }

    /**
     * Cập nhật phiếu nhập kho và xếp thùng lại (nếu chưa xuất)
     */
    public function updateReceipt(int $receiptId, string $poNumber, string $receiptDate, int $updaterId, ?string $note, array $items)
    {
        return DB::transaction(function () use ($receiptId, $poNumber, $receiptDate, $updaterId, $note, $items) {
            $receipt = Receipt::findOrFail($receiptId);
            $oldValues = $receipt->toArray();

            $isIssued = $this->isReceiptIssued($receiptId);

            // 1. Cập nhật thông tin chung
            $receipt->update([
                'po_number'    => $poNumber,
                'receipt_date' => $receiptDate,
                'note'         => $note
            ]);

            // 2. Nếu đã xuất hàng, cập nhật ngày nhận cho các thùng hàng còn lại và không chạm vào chi tiết hàng hóa
            if ($isIssued) {
                InventoryCarton::whereIn('receipt_detail_id', function($query) use ($receiptId) {
                    $query->select('id')->from('receipt_details')->where('receipt_id', $receiptId);
                })->update(['received_at' => $receiptDate]);

                AuditLog::logAction($updaterId, 'UPDATE', 'receipts', $receipt->id, $oldValues, [
                    'receipt_code' => $receipt->receipt_code,
                    'note' => 'Cập nhật thông tin chung (Hàng đã được xuất/phân bổ nên không sửa sản phẩm)'
                ]);

                return $receipt;
            }

            // 3. Nếu chưa xuất hàng, tiến hành dựng lại toàn bộ chi tiết và thùng hàng
            $details = ReceiptDetail::where('receipt_id', $receiptId)->get();
            foreach ($details as $d) {
                InventoryCarton::where('receipt_detail_id', $d->id)->delete();
                $d->delete();
            }

            foreach ($items as $item) {
                $productId = $item['product_id'];
                $lotNumber = trim($item['lot_number']);
                $price = $item['price'] ?? 0;
                $cartons = $item['cartons'];

                $totalCartons = count($cartons);
                $totalPieces = array_sum(array_column($cartons, 'pieces'));

                $detail = ReceiptDetail::create([
                    'receipt_id'    => $receipt->id,
                    'product_id'    => $productId,
                    'lot_number'    => $lotNumber,
                    'total_cartons' => $totalCartons,
                    'total_pieces'  => $totalPieces,
                    'price'         => $price
                ]);

                $product = Product::findOrFail($productId);
                $cartonIndex = 1;

                foreach ($cartons as $c) {
                    $pieces = (int)$c['pieces'];
                    $locationId = (int)$c['location_id'];
                    $cartonCode = "C-{$lotNumber}-{$product->sku}-" . str_pad($cartonIndex++, 2, '0', STR_PAD_LEFT);

                    InventoryCarton::create([
                        'receipt_detail_id' => $detail->id,
                        'product_id'        => $productId,
                        'carton_code'       => $cartonCode,
                        'original_pieces'   => $pieces,
                        'current_pieces'    => $pieces,
                        'location_id'       => $locationId,
                        'status'            => 'IN_STOCK',
                        'received_at'       => $receiptDate
                    ]);
                }
            }

            AuditLog::logAction($updaterId, 'UPDATE', 'receipts', $receipt->id, $oldValues, [
                'receipt_code' => $receipt->receipt_code,
                'items_count' => count($items)
            ]);

            return $receipt;
        });
    }

    /**
     * Xóa phiếu nhập kho
     */
    public function deleteReceipt(int $receiptId, int $deleterId)
    {
        return DB::transaction(function () use ($receiptId, $deleterId) {
            $receipt = Receipt::findOrFail($receiptId);
            $oldValues = $receipt->toArray();

            if ($this->isReceiptIssued($receiptId)) {
                throw new \Exception("Không thể xóa phiếu nhập kho do một số thùng hàng đã xuất kho hoặc đã được phân bổ.");
            }

            // Xóa các thùng hàng và chi tiết
            $details = ReceiptDetail::where('receipt_id', $receiptId)->get();
            foreach ($details as $d) {
                InventoryCarton::where('receipt_detail_id', $d->id)->delete();
                $d->delete();
            }

            $receipt->delete();

            AuditLog::logAction($deleterId, 'DELETE', 'receipts', $receiptId, $oldValues, null);
        });
    }
}
