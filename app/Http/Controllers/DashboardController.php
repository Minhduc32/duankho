<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Location;
use App\Models\AuditLog;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Thống kê tồn kho chi tiết
        $stockStatus = Product::getStockStatus();
        
        $totalProducts = $stockStatus->count();
        $totalPieces = $stockStatus->sum('total_pieces');
        $totalCartons = $stockStatus->sum('total_cartons');
        $lowStockCount = $stockStatus->filter(function ($item) {
            return $item->total_pieces < $item->min_stock;
        })->count();

        // 2. Giá trị tổng tồn kho (dựa trên đơn giá nhập cuối)
        $totalInventoryValue = \App\Models\InventoryCarton::where('status', 'IN_STOCK')
            ->join('receipt_details as rd', 'inventory_cartons.receipt_detail_id', '=', 'rd.id')
            ->selectRaw('SUM(inventory_cartons.current_pieces * rd.price) as total_value')
            ->value('total_value') ?? 0;

        // 3. Số phiếu nhập / xuất trong ngày hôm nay
        $todayInbound = \App\Models\Receipt::whereDate('receipt_date', today())->count();
        $todayOutbound = \App\Models\Issue::whereDate('issue_date', today())->count();

        // 4. Thống kê theo 7 dãy kho
        $zoneStats = Location::getZoneStats();

        // 5. Hoạt động gần đây
        $recentLogs = AuditLog::with('user')
            ->orderBy('created_at', 'DESC')
            ->limit(8)
            ->get();

        return view('dashboard', compact(
            'totalProducts', 'totalPieces', 'totalCartons', 'lowStockCount',
            'totalInventoryValue', 'todayInbound', 'todayOutbound',
            'zoneStats', 'recentLogs'
        ));
    }

    public function zoneMap(Request $request)
    {
        $selectedZone = strtoupper($request->get('zone', 'A'));
        $zones = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];

        if (!in_array($selectedZone, $zones)) {
            $selectedZone = 'A';
        }

        $zoneStats = Location::getZoneStats();
        $inventory = Location::getInventoryByZone($selectedZone);

        // Chuẩn bị khung lưới 2D cho 5 kệ, 4 tầng
        $layoutData = [];
        for ($r = 1; $r <= 5; $r++) {
            for ($l = 1; $l <= 4; $l++) {
                $layoutData["Kệ {$r}"]["Tầng {$l}"] = [
                    'barcode' => $selectedZone . $r . $l,
                    'cartons' => []
                ];
            }
        }

        // Nhồi dữ liệu thực tế vào lưới
        foreach ($inventory as $item) {
            $rack = $item->rack;
            $level = $item->level;
            $layoutData[$rack][$level]['cartons'][] = $item;
        }

        return view('zones.map', compact('zones', 'selectedZone', 'zoneStats', 'layoutData'));
    }

    public function auditLogs()
    {
        $logs = AuditLog::with('user')
            ->orderBy('created_at', 'DESC')
            ->limit(100)
            ->get();

        return view('audit_logs', compact('logs'));
    }
}
