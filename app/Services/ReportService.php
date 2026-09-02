<?php

namespace App\Services;

use App\Models\Product;
use App\Models\InventoryCarton;
use App\Models\IssueAllocation;
use Illuminate\Support\Facades\DB;

use App\Models\Receipt;
use App\Models\Issue;
use App\Models\Location;
use App\Models\AuditLog;

class ReportService
{
    /**
     * Tính toán báo cáo Nhập - Xuất - Tồn theo ngày (NXT)
     * 
     * @param string $date
     * @return array
     */
    public function getDailyNXT(string $date): array
    {
        $products = Product::orderBy('sku', 'ASC')->get();
        $reportData = [];

        foreach ($products as $p) {
            $productId = $p->id;

            // 1. Nhập trong ngày
            $inbound = InventoryCarton::where('product_id', $productId)
                ->whereDate('received_at', $date)
                ->select(
                    DB::raw('COALESCE(COUNT(id), 0) as cartons'),
                    DB::raw('COALESCE(SUM(original_pieces), 0) as pieces')
                )
                ->first();

            // 2. Xuất trong ngày
            $outPieces = IssueAllocation::join('issue_details as idt', 'issue_allocations.issue_detail_id', '=', 'idt.id')
                ->join('issues as i', 'idt.issue_id', '=', 'i.id')
                ->where('idt.product_id', $productId)
                ->whereDate('i.issue_date', $date)
                ->sum('issue_allocations.pieces_issued');

            $outCartons = IssueAllocation::join('issue_details as idt', 'issue_allocations.issue_detail_id', '=', 'idt.id')
                ->join('issues as i', 'idt.issue_id', '=', 'i.id')
                ->where('idt.product_id', $productId)
                ->whereDate('i.issue_date', $date)
                ->distinct('issue_allocations.inventory_carton_id')
                ->count('issue_allocations.inventory_carton_id');

            // 3. Tồn kho hiện tại (ngay lúc này)
            $stockNow = InventoryCarton::where('product_id', $productId)
                ->where('status', 'IN_STOCK')
                ->where('current_pieces', '>', 0)
                ->select(
                    DB::raw('COALESCE(COUNT(id), 0) as cartons'),
                    DB::raw('COALESCE(SUM(current_pieces), 0) as pieces')
                )
                ->first();

            // 4. Nhập / Xuất sau ngày báo cáo (để rollback về ngày báo cáo)
            $inAfter = InventoryCarton::where('product_id', $productId)
                ->whereDate('received_at', '>', $date)
                ->select(
                    DB::raw('COALESCE(COUNT(id), 0) as cartons'),
                    DB::raw('COALESCE(SUM(original_pieces), 0) as pieces')
                )
                ->first();

            $outAfter = IssueAllocation::join('issue_details as idt', 'issue_allocations.issue_detail_id', '=', 'idt.id')
                ->join('issues as i', 'idt.issue_id', '=', 'i.id')
                ->where('idt.product_id', $productId)
                ->whereDate('i.issue_date', '>', $date)
                ->select(
                    DB::raw('COALESCE(COUNT(DISTINCT issue_allocations.inventory_carton_id), 0) as cartons'),
                    DB::raw('COALESCE(SUM(issue_allocations.pieces_issued), 0) as pieces')
                )
                ->first();

            // Tồn cuối ngày báo cáo D
            $closingPieces = $stockNow->pieces + $outAfter->pieces - $inAfter->pieces;
            $closingCartons = $stockNow->cartons + $outAfter->cartons - $inAfter->cartons;

            // Tồn đầu ngày báo cáo D
            $openingPieces = $closingPieces - $inbound->pieces + $outPieces;
            $openingCartons = $closingCartons - $inbound->cartons + $outCartons;

            $reportData[] = [
                'sku'             => $p->sku,
                'product_name'    => $p->name,
                'category'        => $p->category,
                'opening_cartons' => max(0, $openingCartons),
                'opening_pieces'  => max(0, $openingPieces),
                'in_cartons'      => $inbound->cartons,
                'in_pieces'       => $inbound->pieces,
                'out_cartons'     => $outCartons,
                'out_pieces'      => $outPieces,
                'closing_cartons' => max(0, $closingCartons),
                'closing_pieces'  => max(0, $closingPieces)
            ];
        }

        return $reportData;
    }

    /**
     * Báo cáo chi tiết Nhập kho
     */
    public function getInboundReport(array $filters)
    {
        $query = Receipt::join('receipt_details as rd', 'receipts.id', '=', 'rd.receipt_id')
            ->join('inventory_cartons as ic', 'rd.id', '=', 'ic.receipt_detail_id')
            ->join('products as p', 'rd.product_id', '=', 'p.id')
            ->join('locations as l', 'ic.location_id', '=', 'l.id')
            ->join('users as u', 'receipts.creator_id', '=', 'u.id')
            ->select(
                'receipts.receipt_code',
                'receipts.po_number',
                'receipts.receipt_date',
                'u.full_name as creator_name',
                'p.sku',
                'p.name as product_name',
                'p.category',
                'rd.lot_number',
                'ic.carton_code',
                'ic.original_pieces',
                'rd.price',
                'l.zone',
                'l.rack',
                'l.level'
            );

        if (!empty($filters['from_date'])) {
            $query->whereDate('receipts.receipt_date', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->whereDate('receipts.receipt_date', '<=', $filters['to_date']);
        }
        if (!empty($filters['product_id'])) {
            $query->where('rd.product_id', '=', $filters['product_id']);
        }
        if (!empty($filters['po_number'])) {
            $query->where('receipts.po_number', 'LIKE', '%' . $filters['po_number'] . '%');
        }

        return $query->orderBy('receipts.receipt_date', 'DESC')
            ->orderBy('receipts.created_at', 'DESC')
            ->orderBy('ic.carton_code', 'ASC')
            ->get();
    }

    /**
     * Báo cáo chi tiết Xuất kho
     */
    public function getOutboundReport(array $filters)
    {
        $query = Issue::join('issue_details as idt', 'issues.id', '=', 'idt.issue_id')
            ->join('issue_allocations as ia', 'idt.id', '=', 'ia.issue_detail_id')
            ->join('inventory_cartons as ic', 'ia.inventory_carton_id', '=', 'ic.id')
            ->join('products as p', 'idt.product_id', '=', 'p.id')
            ->join('locations as l', 'ic.location_id', '=', 'l.id')
            ->leftJoin('receipt_details as rd', 'ic.receipt_detail_id', '=', 'rd.id')
            ->join('users as u', 'issues.creator_id', '=', 'u.id')
            ->select(
                'issues.issue_code',
                'issues.order_number',
                'issues.issue_date',
                'issues.status',
                'issues.note',
                'u.full_name as creator_name',
                'p.sku',
                'p.name as product_name',
                'p.category',
                'rd.lot_number',
                'ic.carton_code',
                'ia.pieces_issued',
                'l.zone',
                'l.rack',
                'l.level'
            );

        if (!empty($filters['from_date'])) {
            $query->whereDate('issues.issue_date', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->whereDate('issues.issue_date', '<=', $filters['to_date']);
        }
        if (!empty($filters['product_id'])) {
            $query->where('idt.product_id', '=', $filters['product_id']);
        }
        if (!empty($filters['order_number'])) {
            $query->where('issues.order_number', 'LIKE', '%' . $filters['order_number'] . '%');
        }

        return $query->orderBy('issues.issue_date', 'DESC')
            ->orderBy('issues.created_at', 'DESC')
            ->orderBy('ic.carton_code', 'ASC')
            ->get();
    }

    /**
     * Báo cáo Tồn kho & Định mức hiện tại
     */
    public function getInventoryReport(array $filters)
    {
        $query = InventoryCarton::join('products as p', 'inventory_cartons.product_id', '=', 'p.id')
            ->join('locations as l', 'inventory_cartons.location_id', '=', 'l.id')
            ->leftJoin('receipt_details as rd', 'inventory_cartons.receipt_detail_id', '=', 'rd.id')
            ->select(
                'inventory_cartons.carton_code',
                'p.sku',
                'p.name as product_name',
                'p.category',
                'p.min_stock',
                'p.max_stock',
                'rd.lot_number',
                'rd.price',
                'inventory_cartons.original_pieces',
                'inventory_cartons.current_pieces',
                'inventory_cartons.received_at',
                'l.zone',
                'l.rack',
                'l.level'
            )
            ->where('inventory_cartons.status', '=', 'IN_STOCK')
            ->where('inventory_cartons.current_pieces', '>', 0);

        if (!empty($filters['product_id'])) {
            $query->where('inventory_cartons.product_id', '=', $filters['product_id']);
        }
        if (!empty($filters['category'])) {
            $query->where('p.category', '=', $filters['category']);
        }
        if (!empty($filters['zone'])) {
            $query->where('l.zone', '=', $filters['zone']);
        }

        return $query->orderBy('p.sku', 'ASC')
            ->orderBy('l.zone', 'ASC')
            ->orderBy('l.rack', 'ASC')
            ->orderBy('l.level', 'ASC')
            ->get();
    }

    /**
     * Báo cáo Hiệu suất & Sơ đồ Kệ
     */
    public function getOccupancyReport(array $filters)
    {
        $query = Location::leftJoin('inventory_cartons as ic', function($join) {
            $join->on('locations.id', '=', 'ic.location_id')
                 ->where('ic.status', '=', 'IN_STOCK')
                 ->where('ic.current_pieces', '>', 0);
        })
        ->leftJoin('products as p', 'ic.product_id', '=', 'p.id')
        ->select(
            'locations.id as location_id',
            'locations.zone',
            'locations.rack',
            'locations.level',
            'locations.barcode as location_barcode',
            'locations.is_active',
            DB::raw('COUNT(DISTINCT ic.product_id) as total_products'),
            DB::raw('COALESCE(COUNT(ic.id), 0) as total_cartons'),
            DB::raw('COALESCE(SUM(ic.current_pieces), 0) as total_pieces')
        )
        ->groupBy('locations.id', 'locations.zone', 'locations.rack', 'locations.level', 'locations.barcode', 'locations.is_active');

        if (!empty($filters['zone'])) {
            $query->where('locations.zone', '=', $filters['zone']);
        }

        return $query->orderBy('locations.zone', 'ASC')
            ->orderBy('locations.rack', 'ASC')
            ->orderBy('locations.level', 'ASC')
            ->get();
    }

    /**
     * Báo cáo Audit Log
     */
    public function getAuditLogReport(array $filters)
    {
        $query = AuditLog::with('user');

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }
        if (!empty($filters['user_id'])) {
            $query->where('user_id', '=', $filters['user_id']);
        }
        if (!empty($filters['action'])) {
            $query->where('action', '=', $filters['action']);
        }

        return $query->orderBy('created_at', 'DESC')->get();
    }
}
