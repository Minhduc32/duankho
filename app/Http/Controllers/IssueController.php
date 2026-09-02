<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Issue;
use App\Models\InventoryCarton;
use App\Services\OutboundService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class IssueController extends Controller
{
    private $outboundService;

    public function __construct(OutboundService $outboundService)
    {
        $this->outboundService = $outboundService;
    }

    public function index(Request $request)
    {
        $query = Issue::with('creator')->orderBy('created_at', 'DESC');

        if ($q = $request->get('q')) {
            $query->where(function ($qb) use ($q) {
                $qb->where('issue_code', 'like', "%{$q}%")
                   ->orWhere('order_number', 'like', "%{$q}%")
                   ->orWhere('note', 'like', "%{$q}%");
            });
        }
        if ($from = $request->get('from')) {
            $query->whereDate('issue_date', '>=', $from);
        }
        if ($to = $request->get('to')) {
            $query->whereDate('issue_date', '<=', $to);
        }

        $issues = $query->get();
        return view('issues.index', compact('issues'));
    }

    public function create()
    {
        $products = Product::all();
        return view('issues.create', compact('products'));
    }

    public function suggest(Request $request)
    {
        $productId = (int)$request->get('product_id', 0);
        $qty = (int)$request->get('qty', 0);
        $rule = $request->get('rule', 'FIFO');

        if ($productId <= 0 || $qty <= 0) {
            return response()->json(['error' => 'Thông tin sản phẩm hoặc số lượng không hợp lệ.'], 400);
        }

        $result = $this->outboundService->suggest($productId, $qty, $rule);
        return response()->json($result);
    }

    /**
     * Trả về thông tin tồn kho và lịch sử nhập hàng của một sản phẩm (AJAX)
     */
    public function productInfo(Request $request)
    {
        $productId = (int)$request->get('product_id', 0);

        if ($productId <= 0) {
            return response()->json(['error' => 'Sản phẩm không hợp lệ.'], 400);
        }

        $product = Product::findOrFail($productId);
        $stock = Product::getStockStatus($productId);

        // Lấy danh sách thùng hàng đang có trong kho + nhân viên nhập hàng
        $cartons = DB::table('inventory_cartons as ic')
            ->join('locations as l', 'ic.location_id', '=', 'l.id')
            ->join('receipt_details as rd', 'ic.receipt_detail_id', '=', 'rd.id')
            ->join('receipts as r', 'rd.receipt_id', '=', 'r.id')
            ->join('users as u', 'r.creator_id', '=', 'u.id')
            ->where('ic.product_id', $productId)
            ->where('ic.status', 'IN_STOCK')
            ->where('ic.current_pieces', '>', 0)
            ->select(
                'ic.carton_code',
                'ic.current_pieces',
                'ic.original_pieces',
                'ic.received_at',
                'rd.lot_number',
                'rd.price',
                'r.receipt_code',
                'r.po_number',
                'r.receipt_date',
                'u.full_name as creator_name',
                'l.zone', 'l.rack', 'l.level'
            )
            ->orderBy('ic.received_at', 'ASC')
            ->limit(20)
            ->get();

        return response()->json([
            'product'       => [
                'id'       => $product->id,
                'sku'      => $product->sku,
                'name'     => $product->name,
                'unit'     => $product->unit,
                'category' => $product->category,
            ],
            'stock'         => [
                'total_pieces'  => $stock ? $stock->total_pieces : 0,
                'total_cartons' => $stock ? $stock->total_cartons : 0,
            ],
            'cartons'       => $cartons,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'order_number' => 'required|string',
            'issue_date'   => 'required|date',
            'rule'         => 'required|string|in:FIFO,LIFO',
            'note'         => 'nullable|string',
            'items'        => 'required|array|min:1',
        ]);

        $items = $request->input('items');

        try {
            $preparedItems = [];
            foreach ($items as $item) {
                $productId = (int)$item['product_id'];
                $qty = (int)$item['qty'];

                if ($productId <= 0) {
                    throw new Exception("Sản phẩm được chọn không hợp lệ.");
                }
                if ($qty <= 0) {
                    throw new Exception("Số lượng xuất phải lớn hơn 0.");
                }

                $preparedItems[] = [
                    'product_id' => $productId,
                    'qty'        => $qty
                ];
            }

            $issue = $this->outboundService->createIssue(
                trim($request->order_number),
                $request->issue_date,
                Auth::id(),
                trim($request->note ?? ''),
                $preparedItems,
                $request->rule
            );

            return redirect()->route('outbound.index')
                ->with('success', 'Tạo phiếu xuất kho ' . $issue->issue_code . ' thành công!')
                ->with('toast', [
                    'type'    => 'primary',
                    'icon'    => 'fa-truck-moving',
                    'title'   => 'Xuất kho thành công!',
                    'message' => 'Mã phiếu <strong>' . $issue->issue_code . '</strong> đã được tạo. Tổng ' . count($preparedItems) . ' mặt hàng.',
                ]);
        } catch (Exception $e) {
            return back()->with('error', 'Lỗi xuất kho: ' . $e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
        $issue = Issue::with('creator')->findOrFail($id);
        
        $details = $issue->issueDetails()->with('product')->get();
        foreach ($details as $detail) {
            $detail->allocations = $detail->issueAllocations()
                ->join('inventory_cartons as ic', 'issue_allocations.inventory_carton_id', '=', 'ic.id')
                ->join('locations as l', 'ic.location_id', '=', 'l.id')
                ->leftJoin('receipt_details as rd', 'ic.receipt_detail_id', '=', 'rd.id')
                ->select(
                    'issue_allocations.*', 
                    'ic.carton_code', 
                    'rd.lot_number',
                    'l.zone', 'l.rack', 'l.level'
                )
                ->get();
        }

        return view('issues.view', compact('issue', 'details'));
    }

    public function edit($id)
    {
        $issue = Issue::with('creator')->findOrFail($id);
        $details = $issue->issueDetails()->with('product')->get();
        $products = Product::all();

        return view('issues.edit', compact('issue', 'details', 'products'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'order_number' => 'required|string',
            'issue_date'   => 'required|date',
            'rule'         => 'required|string|in:FIFO,LIFO',
            'note'         => 'nullable|string',
            'items'        => 'required|array|min:1',
        ]);

        $items = $request->input('items');

        try {
            $preparedItems = [];
            foreach ($items as $item) {
                $productId = (int)$item['product_id'];
                $qty = (int)$item['qty'];

                if ($productId <= 0) {
                    throw new Exception("Sản phẩm được chọn không hợp lệ.");
                }
                if ($qty <= 0) {
                    throw new Exception("Số lượng xuất phải lớn hơn 0.");
                }

                $preparedItems[] = [
                    'product_id' => $productId,
                    'qty'        => $qty
                ];
            }

            $this->outboundService->updateIssue(
                $id,
                trim($request->order_number),
                $request->issue_date,
                Auth::id(),
                trim($request->note ?? ''),
                $preparedItems,
                $request->rule
            );

            return redirect()->route('outbound.index')
                ->with('success', 'Cập nhật phiếu xuất kho thành công!')
                ->with('toast', [
                    'type'    => 'info',
                    'icon'    => 'fa-pen-to-square',
                    'title'   => 'Cập nhật phiếu xuất',
                    'message' => 'Phiếu xuất kho đã được cập nhật thông tin và tồn kho điều chỉnh lại.',
                ]);
        } catch (Exception $e) {
            return back()->with('error', 'Lỗi cập nhật phiếu xuất: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $this->outboundService->deleteIssue($id, Auth::id());
            return redirect()->route('outbound.index')
                ->with('success', 'Xóa phiếu xuất kho và khôi phục tồn kho thành công!')
                ->with('toast', [
                    'type'    => 'warning',
                    'icon'    => 'fa-rotate-left',
                    'title'   => 'Đã xóa phiếu xuất kho',
                    'message' => 'Phiếu xuất kho đã bị xóa. Tồn kho đã được khôi phục về trạng thái ban đầu.',
                ]);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Lỗi xóa phiếu xuất: ' . $e->getMessage());
        }
    }
}
