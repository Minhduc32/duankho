<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\InventoryCarton;
use App\Models\Receipt;
use App\Models\Issue;
use Carbon\Carbon;

class NotificationController extends Controller
{
    /**
     * Trả về danh sách tất cả thông báo dạng JSON
     */
    public function index()
    {
        $notifications = collect();

        // 1. Cảnh báo hàng dưới định mức tối thiểu (LOW STOCK)
        $stockStatus = Product::getStockStatus();
        foreach ($stockStatus as $product) {
            if ($product->total_pieces <= 0) {
                $notifications->push([
                    'id'       => 'out_' . $product->id,
                    'type'     => 'danger',
                    'icon'     => 'fa-circle-exclamation',
                    'title'    => 'Hết hàng: ' . $product->name,
                    'message'  => "Sản phẩm [{$product->sku}] hiện không còn tồn kho (0 cái). Cần nhập hàng ngay!",
                    'time'     => now()->toIso8601String(),
                    'link'     => route('products.index'),
                    'badge'    => 'HẾT HÀNG',
                ]);
            } elseif ($product->total_pieces < $product->min_stock) {
                $notifications->push([
                    'id'       => 'low_' . $product->id,
                    'type'     => 'warning',
                    'icon'     => 'fa-triangle-exclamation',
                    'title'    => 'Tồn kho thấp: ' . $product->name,
                    'message'  => "Tồn kho [{$product->sku}] chỉ còn " . number_format($product->total_pieces) . " cái (ngưỡng tối thiểu: " . number_format($product->min_stock) . " cái).",
                    'time'     => now()->toIso8601String(),
                    'link'     => route('products.index'),
                    'badge'    => 'THẤP',
                ]);
            }
        }

        // 2. Phiếu nhập kho hôm nay
        $todayReceipts = Receipt::whereDate('receipt_date', today())
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($todayReceipts as $receipt) {
            $notifications->push([
                'id'       => 'rec_' . $receipt->id,
                'type'     => 'success',
                'icon'     => 'fa-truck-ramp-box',
                'title'    => 'Nhập kho hôm nay: ' . $receipt->receipt_code,
                'message'  => 'PO #' . ($receipt->po_number ?? 'N/A') . ' đã được tạo lúc ' . Carbon::parse($receipt->created_at)->format('H:i'),
                'time'     => $receipt->created_at->toIso8601String(),
                'link'     => route('inbound.show', $receipt->id),
                'badge'    => 'NHẬP KHO',
            ]);
        }

        // 3. Phiếu xuất kho hôm nay
        $todayIssues = Issue::whereDate('issue_date', today())
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($todayIssues as $issue) {
            $notifications->push([
                'id'       => 'iss_' . $issue->id,
                'type'     => 'info',
                'icon'     => 'fa-truck-moving',
                'title'    => 'Xuất kho hôm nay: ' . $issue->issue_code,
                'message'  => 'SO #' . ($issue->order_number ?? 'N/A') . ' đã được tạo lúc ' . Carbon::parse($issue->created_at)->format('H:i'),
                'time'     => $issue->created_at->toIso8601String(),
                'link'     => route('outbound.show', $issue->id),
                'badge'    => 'XUẤT KHO',
            ]);
        }

        // 4. Thùng hàng sắp hết (current_pieces < 10% of original)
        $nearlyEmptyCartons = InventoryCarton::where('status', 'IN_STOCK')
            ->whereRaw('current_pieces < original_pieces * 0.1')
            ->where('current_pieces', '>', 0)
            ->with(['product', 'location'])
            ->limit(5)
            ->get();

        foreach ($nearlyEmptyCartons as $carton) {
            $notifications->push([
                'id'       => 'cart_' . $carton->id,
                'type'     => 'warning',
                'icon'     => 'fa-box-open',
                'title'    => 'Thùng hàng sắp cạn: ' . ($carton->product->name ?? ''),
                'message'  => "Thùng {$carton->carton_code} chỉ còn {$carton->current_pieces}/{$carton->original_pieces} cái tại " . ($carton->location ? "Dãy {$carton->location->zone} - {$carton->location->rack}" : ''),
                'time'     => $carton->updated_at->toIso8601String(),
                'link'     => route('inbound.index'),
                'badge'    => 'SẮP HẾT',
            ]);
        }

        // 5. Sản phẩm vượt tồn kho tối đa
        foreach ($stockStatus as $product) {
            if ($product->max_stock > 0 && $product->total_pieces > $product->max_stock) {
                $notifications->push([
                    'id'       => 'over_' . $product->id,
                    'type'     => 'info',
                    'icon'     => 'fa-layer-group',
                    'title'    => 'Tồn kho vượt mức: ' . $product->name,
                    'message'  => "[{$product->sku}] đang có " . number_format($product->total_pieces) . " cái, vượt mức tối đa " . number_format($product->max_stock) . " cái. Cân nhắc xuất kho.",
                    'time'     => now()->toIso8601String(),
                    'link'     => route('products.index'),
                    'badge'    => 'QUÁ MỨC',
                ]);
            }
        }

        // Sắp xếp theo loại ưu tiên: danger > warning > info > success
        $priorityMap = ['danger' => 0, 'warning' => 1, 'info' => 2, 'success' => 3];
        $sorted = $notifications->sortBy(fn($n) => $priorityMap[$n['type']] ?? 99)->values();

        return response()->json([
            'count'         => $sorted->count(),
            'unread'        => $sorted->count(),
            'notifications' => $sorted->take(20), // Giới hạn 20 thông báo
        ]);
    }

    /**
     * Trả về các phiếu nhập/xuất mới kể từ timestamp cho real-time polling
     * GET /api/notifications/recent?since=2024-01-01T00:00:00Z
     */
    public function recent(Request $request)
    {
        $since = $request->get('since');

        // Mặc định: 30 giây trước nếu không truyền
        try {
            $sinceDate = $since ? Carbon::parse($since) : now()->subSeconds(30);
        } catch (\Exception $e) {
            $sinceDate = now()->subSeconds(30);
        }

        $events = collect();

        // Phiếu nhập mới
        $newReceipts = Receipt::where('created_at', '>', $sinceDate)
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($newReceipts as $receipt) {
            $events->push([
                'id'      => 'new_rec_' . $receipt->id,
                'type'    => 'success',
                'icon'    => 'fa-truck-ramp-box',
                'title'   => 'Nhập kho mới: ' . $receipt->receipt_code,
                'message' => 'PO #' . ($receipt->po_number ?: 'N/A') . ' vừa được tạo lúc ' . Carbon::parse($receipt->created_at)->format('H:i:s'),
                'time'    => $receipt->created_at->toIso8601String(),
                'link'    => route('inbound.show', $receipt->id),
            ]);
        }

        // Phiếu xuất mới
        $newIssues = Issue::where('created_at', '>', $sinceDate)
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($newIssues as $issue) {
            $events->push([
                'id'      => 'new_iss_' . $issue->id,
                'type'    => 'primary',
                'icon'    => 'fa-truck-moving',
                'title'   => 'Xuất kho mới: ' . $issue->issue_code,
                'message' => 'SO #' . ($issue->order_number ?: 'N/A') . ' vừa được tạo lúc ' . Carbon::parse($issue->created_at)->format('H:i:s'),
                'time'    => $issue->created_at->toIso8601String(),
                'link'    => route('outbound.show', $issue->id),
            ]);
        }

        return response()->json([
            'has_new'    => $events->isNotEmpty(),
            'count'      => $events->count(),
            'events'     => $events->values(),
            'server_now' => now()->toIso8601String(),
        ]);
    }
}
