<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Location;
use App\Models\Receipt;
use App\Services\InboundService;
use Illuminate\Support\Facades\Auth;
use Exception;

class ReceiptController extends Controller
{
    private $inboundService;

    public function __construct(InboundService $inboundService)
    {
        $this->inboundService = $inboundService;
    }

    public function index(Request $request)
    {
        $query = Receipt::with('creator')
            ->withCount([
                'receiptDetails as issued_cartons_count' => function ($q) {
                    $q->whereHas('inventoryCartons', function ($q2) {
                        $q2->where('status', '!=', 'IN_STOCK')
                           ->orWhereColumn('current_pieces', '<', 'original_pieces');
                    });
                },
                'receiptDetails as allocated_cartons_count' => function ($q) {
                    $q->whereHas('inventoryCartons', function ($q2) {
                        $q2->whereHas('issueAllocations');
                    });
                }
            ])
            ->orderBy('created_at', 'DESC');

        if ($q = $request->get('q')) {
            $query->where(function ($qb) use ($q) {
                $qb->where('receipt_code', 'like', "%{$q}%")
                   ->orWhere('po_number', 'like', "%{$q}%")
                   ->orWhere('note', 'like', "%{$q}%");
            });
        }
        if ($from = $request->get('from')) {
            $query->whereDate('receipt_date', '>=', $from);
        }
        if ($to = $request->get('to')) {
            $query->whereDate('receipt_date', '<=', $to);
        }

        $receipts = $query->get();
        return view('receipts.index', compact('receipts'));
    }

    public function create()
    {
        $products = Product::all();
        $locations = Location::where('is_active', true)->orderBy('zone')->orderBy('rack')->orderBy('level')->get();
        return view('receipts.create', compact('products', 'locations'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'po_number'    => 'nullable|string',
            'receipt_date' => 'required|date',
            'note'         => 'nullable|string',
            'items'        => 'required|array|min:1',
        ]);

        $items = $request->input('items');

        try {
            // Thực hiện chuyển đổi và validate chi tiết dữ liệu thùng hàng
            $preparedItems = [];
            foreach ($items as $itemIndex => $item) {
                if (empty($item['lot_number'])) {
                    throw new Exception("Sản phẩm số dòng " . ($itemIndex + 1) . " chưa nhập số Lô hàng.");
                }

                if (empty($item['cartons'])) {
                    throw new Exception("Lô '" . $item['lot_number'] . "' phải khai báo ít nhất một thùng.");
                }

                $cartons = [];
                foreach ($item['cartons'] as $cIndex => $c) {
                    $pieces = (int)$c['pieces'];
                    $locationId = (int)$c['location_id'];

                    if ($pieces <= 0) {
                        throw new Exception("Số lượng cái ở thùng " . ($cIndex + 1) . " của lô '" . $item['lot_number'] . "' phải lớn hơn 0.");
                    }
                    if ($locationId <= 0) {
                        throw new Exception("Chưa chọn vị trí cho thùng " . ($cIndex + 1) . " của lô '" . $item['lot_number'] . "'.");
                    }

                    $cartons[] = [
                        'pieces'      => $pieces,
                        'location_id' => $locationId
                    ];
                }

                $preparedItems[] = [
                    'product_id' => (int)$item['product_id'],
                    'lot_number' => trim($item['lot_number']),
                    'price'      => (float)($item['price'] ?? 0),
                    'cartons'    => $cartons
                ];
            }

            $receipt = $this->inboundService->createReceipt(
                trim($request->po_number ?? ''),
                $request->receipt_date,
                Auth::id(),
                trim($request->note ?? ''),
                $preparedItems
            );

            return redirect()->route('inbound.index')
                ->with('success', 'Tạo phiếu nhập kho ' . $receipt->receipt_code . ' thành công!')
                ->with('toast', [
                    'type'    => 'success',
                    'icon'    => 'fa-truck-ramp-box',
                    'title'   => 'Nhập kho thành công!',
                    'message' => 'Mã phiếu <strong>' . $receipt->receipt_code . '</strong> đã được tạo. Tổng ' . count($preparedItems) . ' mặt hàng.',
                ]);
        } catch (Exception $e) {
            return back()->with('error', 'Lỗi nhập kho: ' . $e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
        $receipt = Receipt::with('creator')->findOrFail($id);
        
        // Eager load details and nested cartons
        $details = $receipt->receiptDetails()->with('product')->get();
        foreach ($details as $detail) {
            $detail->cartons = $detail->inventoryCartons()->with('location')->get();
        }

        $is_editable = !$this->inboundService->isReceiptIssued($id);

        return view('receipts.view', compact('receipt', 'details', 'is_editable'));
    }

    public function edit($id)
    {
        $receipt = Receipt::with('creator')->findOrFail($id);
        $details = $receipt->receiptDetails()->with('product')->get();
        foreach ($details as $detail) {
            $detail->cartons = $detail->inventoryCartons()->with('location')->get();
        }

        $products = Product::all();
        $locations = Location::where('is_active', true)->orderBy('zone')->orderBy('rack')->orderBy('level')->get();
        $is_editable = !$this->inboundService->isReceiptIssued($id);

        return view('receipts.edit', compact('receipt', 'details', 'products', 'locations', 'is_editable'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'po_number'    => 'nullable|string',
            'receipt_date' => 'required|date',
            'note'         => 'nullable|string',
            'items'        => 'nullable|array',
        ]);

        $items = $request->input('items', []);

        try {
            $isEditable = !$this->inboundService->isReceiptIssued($id);
            $preparedItems = [];

            if ($isEditable && !empty($items)) {
                // Thực hiện chuẩn bị và kiểm tra định dạng
                foreach ($items as $itemIndex => $item) {
                    if (empty($item['lot_number'])) {
                        throw new Exception("Sản phẩm dòng " . ($itemIndex + 1) . " chưa nhập số Lô hàng.");
                    }

                    if (empty($item['cartons'])) {
                        throw new Exception("Lô '" . $item['lot_number'] . "' phải khai báo ít nhất một thùng.");
                    }

                    $cartons = [];
                    foreach ($item['cartons'] as $cIndex => $c) {
                        $pieces = (int)$c['pieces'];
                        $locationId = (int)$c['location_id'];

                        if ($pieces <= 0) {
                            throw new Exception("Số lượng cái ở thùng " . ($cIndex + 1) . " của lô '" . $item['lot_number'] . "' phải lớn hơn 0.");
                        }
                        if ($locationId <= 0) {
                            throw new Exception("Chưa chọn vị trí cho thùng " . ($cIndex + 1) . " của lô '" . $item['lot_number'] . "'.");
                        }

                        $cartons[] = [
                            'pieces'      => $pieces,
                            'location_id' => $locationId
                        ];
                    }

                    $preparedItems[] = [
                        'product_id' => (int)$item['product_id'],
                        'lot_number' => trim($item['lot_number']),
                        'price'      => (float)($item['price'] ?? 0),
                        'cartons'    => $cartons
                    ];
                }
            }

            $this->inboundService->updateReceipt(
                $id,
                trim($request->po_number ?? ''),
                $request->receipt_date,
                Auth::id(),
                trim($request->note ?? ''),
                $preparedItems
            );

            return redirect()->route('inbound.index')
                ->with('success', 'Cập nhật phiếu nhập kho thành công!')
                ->with('toast', [
                    'type'    => 'info',
                    'icon'    => 'fa-pen-to-square',
                    'title'   => 'Cập nhật phiếu nhập',
                    'message' => 'Phiếu nhập kho đã được cập nhật thông tin.',
                ]);
        } catch (Exception $e) {
            return back()->with('error', 'Lỗi cập nhật phiếu nhập: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $this->inboundService->deleteReceipt($id, Auth::id());
            return redirect()->route('inbound.index')
                ->with('success', 'Xóa phiếu nhập kho thành công!')
                ->with('toast', [
                    'type'    => 'warning',
                    'icon'    => 'fa-trash-can',
                    'title'   => 'Đã xóa phiếu nhập kho',
                    'message' => 'Phiếu nhập kho đã bị xóa và tồn kho đã được khôi phục.',
                ]);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Lỗi xóa phiếu nhập: ' . $e->getMessage());
        }
    }
}
