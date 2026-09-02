<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReportService;
use App\Models\Product;
use App\Models\User;

class ReportController extends Controller
{
    private $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index(Request $request)
    {
        $tab = $request->get('tab', 'nxt');
        $date = $request->get('date', date('Y-m-d'));
        
        $from_date = $request->get('from_date', date('Y-m-d'));
        $to_date = $request->get('to_date', date('Y-m-d'));
        $product_id = $request->get('product_id');
        $po_number = $request->get('po_number');
        $order_number = $request->get('order_number');
        $category = $request->get('category');
        $zone = $request->get('zone');
        $user_id = $request->get('user_id');
        $action_filter = $request->get('action_filter');

        $filters = compact(
            'from_date', 'to_date', 'product_id', 'po_number', 
            'order_number', 'category', 'zone', 'user_id'
        );
        $filters['action'] = $action_filter;

        $reportData = [];
        if ($tab === 'nxt') {
            $reportData = $this->reportService->getDailyNXT($date);
        } elseif ($tab === 'inbound') {
            $reportData = $this->reportService->getInboundReport($filters);
        } elseif ($tab === 'outbound') {
            $reportData = $this->reportService->getOutboundReport($filters);
        } elseif ($tab === 'inventory') {
            $reportData = $this->reportService->getInventoryReport($filters);
        } elseif ($tab === 'occupancy') {
            $reportData = $this->reportService->getOccupancyReport($filters);
        } elseif ($tab === 'audit') {
            $reportData = $this->reportService->getAuditLogReport($filters);
        }

        // Dropdowns for filters
        $products = Product::orderBy('sku', 'ASC')->get();
        $users = User::orderBy('full_name', 'ASC')->get();
        $categories = Product::whereNotNull('category')->distinct()->pluck('category');
        $zones = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
        $actions = ['CREATE', 'UPDATE', 'DELETE'];

        return view('reports.index', compact(
            'tab', 'date', 'from_date', 'to_date', 'product_id', 'po_number',
            'order_number', 'category', 'zone', 'user_id', 'action_filter',
            'reportData', 'products', 'users', 'categories', 'zones', 'actions'
        ));
    }

    // ─── Excel helper ────────────────────────────────────────────────────────────

    /**
     * Xuất Excel đơn giản dưới dạng XML Spreadsheet (mở được trong Excel với định dạng cột)
     * Trả về response với MIME type .xls và BOM UTF-8
     */
    private function streamExcel(string $filename, array $headers, array $rows, string $title = '')
    {
        // Sử dụng tab-delimited CSV với BOM UTF-8 và cột được wrap trong dấu nháy
        // Đây là cách đơn giản nhất mà không cần thư viện ngoài
        return response()->streamDownload(function () use ($headers, $rows, $title) {
            $output = fopen('php://output', 'w');

            // BOM UTF-8
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            // Title / metadata row
            if ($title) {
                fputcsv($output, [$title], ',', '"');
                fputcsv($output, ['Xuất ngày: ' . date('d/m/Y H:i')], ',', '"');
                fputcsv($output, [], ',', '"'); // blank row
            }

            // Header row
            fputcsv($output, $headers, ',', '"');

            // Data rows
            foreach ($rows as $row) {
                fputcsv($output, $row, ',', '"');
            }

            // Totals separator
            fputcsv($output, [], ',', '"');
            fputcsv($output, ['--- Kết thúc báo cáo ---'], ',', '"');

            fclose($output);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-cache, must-revalidate',
        ]);
    }

    // ─── Export Actions ───────────────────────────────────────────────────────────

    public function exportExcel(Request $request)
    {
        $date = $request->get('date', date('Y-m-d'));
        $reportData = $this->reportService->getDailyNXT($date);
        $filename = "Bao-Cao-NXT-" . $date . ".csv";

        $headers = [
            'STT',
            'Mã SKU', 
            'Tên sản phẩm', 
            'Danh mục', 
            'ĐVT',
            'Tồn Đầu Kỳ (Thùng)', 
            'Tồn Đầu Kỳ (Cái)', 
            'Nhập Trong Ngày (Thùng)', 
            'Nhập Trong Ngày (Cái)', 
            'Xuất Trong Ngày (Thùng)', 
            'Xuất Trong Ngày (Cái)', 
            'Tồn Cuối Kỳ (Thùng)', 
            'Tồn Cuối Kỳ (Cái)'
        ];

        $rows = [];
        $i = 1;
        foreach ($reportData as $row) {
            $rows[] = [
                $i++,
                $row['sku'],
                $row['product_name'],
                $row['category'] ?: '-',
                $row['unit'] ?? 'cái',
                $row['opening_cartons'],
                $row['opening_pieces'],
                $row['in_cartons'],
                $row['in_pieces'],
                $row['out_cartons'],
                $row['out_pieces'],
                $row['closing_cartons'],
                $row['closing_pieces']
            ];
        }

        // Totals row
        $rows[] = [''];
        $rows[] = [
            'TỔNG',
            '', '', '', '',
            array_sum(array_column($reportData, 'opening_cartons')),
            array_sum(array_column($reportData, 'opening_pieces')),
            array_sum(array_column($reportData, 'in_cartons')),
            array_sum(array_column($reportData, 'in_pieces')),
            array_sum(array_column($reportData, 'out_cartons')),
            array_sum(array_column($reportData, 'out_pieces')),
            array_sum(array_column($reportData, 'closing_cartons')),
            array_sum(array_column($reportData, 'closing_pieces')),
        ];

        return $this->streamExcel($filename, $headers, $rows, "BÁO CÁO NHẬP - XUẤT - TỒN KHO NGÀY " . date('d/m/Y', strtotime($date)));
    }

    public function exportInbound(Request $request)
    {
        $filters = $request->only(['from_date', 'to_date', 'product_id', 'po_number']);
        $reportData = $this->reportService->getInboundReport($filters);
        $filename = "Bao-Cao-Nhap-Kho-" . date('Ymd-His') . ".csv";

        $headers = [
            'STT',
            'Mã phiếu nhập', 
            'Số PO', 
            'Ngày nhập', 
            'Nhân viên nhập kho', 
            'Mã SKU', 
            'Tên sản phẩm', 
            'Danh mục', 
            'Số Lô', 
            'Mã Thùng', 
            'Số Cái/Thùng', 
            'Đơn giá nhập (VNĐ)', 
            'Thành tiền (VNĐ)',
            'Vị trí lưu kho'
        ];

        $rows = [];
        $i = 1;
        $totalPieces = 0;
        $totalAmount = 0;
        foreach ($reportData as $row) {
            $amount = $row->original_pieces * $row->price;
            $totalPieces += $row->original_pieces;
            $totalAmount += $amount;
            $rows[] = [
                $i++,
                $row->receipt_code,
                $row->po_number ?: '-',
                date('d/m/Y H:i', strtotime($row->receipt_date)),
                $row->creator_name,
                $row->sku,
                $row->product_name,
                $row->category ?: '-',
                $row->lot_number,
                $row->carton_code,
                $row->original_pieces,
                number_format($row->price, 0, '.', ','),
                number_format($amount, 0, '.', ','),
                "Dãy " . $row->zone . " - " . $row->rack . " - Tầng " . $row->level
            ];
        }

        // Totals
        $rows[] = [''];
        $rows[] = ['TỔNG CỘNG', '', '', '', '', '', '', '', '', '', $totalPieces, '', number_format($totalAmount, 0, '.', ','), ''];

        $fromLabel = $filters['from_date'] ?? date('Y-m-d');
        $toLabel   = $filters['to_date'] ?? date('Y-m-d');
        return $this->streamExcel($filename, $headers, $rows, "BÁO CÁO NHẬP KHO TỪ " . date('d/m/Y', strtotime($fromLabel)) . " ĐẾN " . date('d/m/Y', strtotime($toLabel)));
    }

    public function exportOutbound(Request $request)
    {
        $filters = $request->only(['from_date', 'to_date', 'product_id', 'order_number']);
        $reportData = $this->reportService->getOutboundReport($filters);
        $filename = "Bao-Cao-Xuat-Kho-" . date('Ymd-His') . ".csv";

        $headers = [
            'STT',
            'Mã phiếu xuất', 
            'Mã đơn hàng (SO #)', 
            'Ngày xuất', 
            'Nhân viên xuất kho', 
            'Mã SKU', 
            'Tên sản phẩm', 
            'Danh mục', 
            'Số Lô', 
            'Mã Thùng', 
            'Số Cái xuất', 
            'Vị trí lấy hàng'
        ];

        $rows = [];
        $i = 1;
        $totalPieces = 0;
        foreach ($reportData as $row) {
            $totalPieces += $row->pieces_issued;
            $rows[] = [
                $i++,
                $row->issue_code,
                $row->order_number ?: '-',
                date('d/m/Y H:i', strtotime($row->issue_date)),
                $row->creator_name,
                $row->sku,
                $row->product_name,
                $row->category ?: '-',
                $row->lot_number ?: '-',
                $row->carton_code,
                $row->pieces_issued,
                "Dãy " . $row->zone . " - " . $row->rack . " - Tầng " . $row->level
            ];
        }

        $rows[] = [''];
        $rows[] = ['TỔNG CỘNG', '', '', '', '', '', '', '', '', '', $totalPieces, ''];

        $fromLabel = $filters['from_date'] ?? date('Y-m-d');
        $toLabel   = $filters['to_date'] ?? date('Y-m-d');
        return $this->streamExcel($filename, $headers, $rows, "BÁO CÁO XUẤT KHO TỪ " . date('d/m/Y', strtotime($fromLabel)) . " ĐẾN " . date('d/m/Y', strtotime($toLabel)));
    }

    public function exportInventory(Request $request)
    {
        $filters = $request->only(['product_id', 'category', 'zone']);
        $reportData = $this->reportService->getInventoryReport($filters);
        $filename = "Bao-Cao-Ton-Kho-" . date('Ymd-His') . ".csv";

        $headers = [
            'STT',
            'Mã Thùng', 
            'Mã SKU', 
            'Tên sản phẩm', 
            'Danh mục', 
            'ĐVT',
            'Số Lô', 
            'Đơn giá (VNĐ)', 
            'Số Cái Ban Đầu', 
            'Số Cái Hiện Tại', 
            'Thành Tiền Hiện Tại (VNĐ)', 
            'Ngày nhận', 
            'Vị trí lưu kho',
            'Mức tồn kho'
        ];

        $rows = [];
        $i = 1;
        $totalPieces = 0;
        $totalValue = 0;
        foreach ($reportData as $row) {
            $value = $row->current_pieces * $row->price;
            $totalPieces += $row->current_pieces;
            $totalValue += $value;

            // Determine stock level
            $stockLevel = 'Bình thường';
            if (isset($row->min_stock) && $row->current_pieces < $row->min_stock) {
                $stockLevel = 'Tồn ít - Cần nhập';
            } elseif (isset($row->max_stock) && $row->current_pieces > $row->max_stock) {
                $stockLevel = 'Vượt định mức';
            }

            $rows[] = [
                $i++,
                $row->carton_code,
                $row->sku,
                $row->product_name,
                $row->category ?: '-',
                $row->unit ?? 'cái',
                $row->lot_number,
                number_format($row->price, 0, '.', ','),
                $row->original_pieces,
                $row->current_pieces,
                number_format($value, 0, '.', ','),
                date('d/m/Y H:i', strtotime($row->received_at)),
                "Dãy " . $row->zone . " - " . $row->rack . " - Tầng " . $row->level,
                $stockLevel
            ];
        }

        $rows[] = [''];
        $rows[] = ['TỔNG CỘNG', '', '', '', '', '', '', '', '', $totalPieces, number_format($totalValue, 0, '.', ','), '', '', ''];

        return $this->streamExcel($filename, $headers, $rows, "BÁO CÁO TỒN KHO - Thời điểm xuất: " . date('d/m/Y H:i'));
    }

    public function exportOccupancy(Request $request)
    {
        $filters = $request->only(['zone']);
        $reportData = $this->reportService->getOccupancyReport($filters);
        $filename = "Bao-Cao-Hieu-Suat-Kho-" . date('Ymd-His') . ".csv";

        $headers = [
            'STT',
            'Dãy kho', 
            'Vị trí', 
            'Barcode Vị trí', 
            'Trạng thái vị trí', 
            'Số loại sản phẩm', 
            'Số lượng thùng', 
            'Tổng số cái',
            'Tình trạng lấp đầy'
        ];

        $rows = [];
        $i = 1;
        foreach ($reportData as $row) {
            $fillStatus = $row->total_cartons > 0 ? 'Đang chứa hàng' : 'Vị trí trống';
            $rows[] = [
                $i++,
                "Dãy " . $row->zone,
                "Dãy " . $row->zone . " - " . $row->rack . " - Tầng " . $row->level,
                $row->location_barcode,
                $row->is_active ? 'Hoạt động' : 'Tạm khóa',
                $row->total_products,
                $row->total_cartons,
                $row->total_pieces,
                $fillStatus
            ];
        }

        $rows[] = [''];
        $rows[] = ['TỔNG', '', '', '', '', '', array_sum(collect($reportData)->pluck('total_cartons')->toArray()), array_sum(collect($reportData)->pluck('total_pieces')->toArray()), ''];

        return $this->streamExcel($filename, $headers, $rows, "BÁO CÁO HIỆU SUẤT KHO - Thời điểm xuất: " . date('d/m/Y H:i'));
    }

    public function exportAudit(Request $request)
    {
        $filters = $request->only(['from_date', 'to_date', 'user_id', 'action']);
        $filters['action'] = $request->get('action_filter', $request->get('action'));
        $reportData = $this->reportService->getAuditLogReport($filters);
        $filename = "Bao-Cao-Audit-Log-" . date('Ymd-His') . ".csv";

        $headers = [
            'STT',
            'Thời gian', 
            'Nhân viên thực hiện', 
            'Hành động', 
            'Bảng dữ liệu', 
            'ID Bản ghi', 
            'Địa chỉ IP',
            'Chi tiết hành động'
        ];

        $rows = [];
        $i = 1;
        foreach ($reportData as $row) {
            $desc = "";
            $newVals = is_string($row->new_values) ? json_decode($row->new_values, true) : (array)$row->new_values;
            $oldVals = is_string($row->old_values) ? json_decode($row->old_values, true) : (array)$row->old_values;

            if ($row->table_name == 'products') {
                $desc = "Sản phẩm SKU: " . ($newVals['sku'] ?? $oldVals['sku'] ?? '') . " - " . ($newVals['name'] ?? $oldVals['name'] ?? '');
            } elseif ($row->table_name == 'receipts') {
                $desc = "Phiếu nhập: " . ($newVals['receipt_code'] ?? $oldVals['receipt_code'] ?? '') . " | PO: " . ($newVals['po_number'] ?? $oldVals['po_number'] ?? '');
            } elseif ($row->table_name == 'issues') {
                $desc = "Phiếu xuất: " . ($newVals['issue_code'] ?? $oldVals['issue_code'] ?? '') . " | ĐH: " . ($newVals['order_number'] ?? $oldVals['order_number'] ?? '');
            } else {
                $desc = json_encode($newVals ?: $oldVals, JSON_UNESCAPED_UNICODE);
            }

            $actionLabel = match($row->action) {
                'CREATE' => 'Tạo mới',
                'UPDATE' => 'Cập nhật',
                'DELETE' => 'Xóa',
                default  => $row->action
            };

            $rows[] = [
                $i++,
                date('d/m/Y H:i:s', strtotime($row->created_at)),
                $row->user->full_name ?? 'Hệ thống',
                $actionLabel,
                $row->table_name,
                $row->record_id ?: '-',
                $row->ip_address,
                strip_tags($desc)
            ];
        }

        $fromLabel = $filters['from_date'] ?? date('Y-m-d');
        $toLabel   = $filters['to_date'] ?? date('Y-m-d');
        return $this->streamExcel($filename, $headers, $rows, "NHẬT KÝ THAO TÁC HỆ THỐNG TỪ " . date('d/m/Y', strtotime($fromLabel)) . " ĐẾN " . date('d/m/Y', strtotime($toLabel)));
    }
}
