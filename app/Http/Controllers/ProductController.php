<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\AuditLog;
use App\Models\ReceiptDetail;
use App\Models\IssueDetail;
use Illuminate\Support\Facades\Auth;
use Exception;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::getStockStatus();
        return view('products.index', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'sku' => 'required|string|unique:products,sku',
            'name' => 'required|string',
            'barcode' => 'nullable|string',
            'category' => 'nullable|string',
            'min_stock' => 'integer|min:0',
            'max_stock' => 'integer|min:0',
        ]);

        $sku = strtoupper(trim($request->sku));
        $product = Product::create([
            'sku' => $sku,
            'name' => trim($request->name),
            'barcode' => trim($request->barcode),
            'category' => trim($request->category),
            'min_stock' => (int)$request->min_stock,
            'max_stock' => (int)$request->max_stock,
        ]);

        AuditLog::logAction(Auth::id(), 'CREATE', 'products', $product->id, null, $product->toArray());

        return redirect()->route('products.index')->with('success', 'Thêm sản phẩm thành công!');
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'sku' => 'required|string|unique:products,sku,' . $id,
            'name' => 'required|string',
            'barcode' => 'nullable|string',
            'category' => 'nullable|string',
            'min_stock' => 'integer|min:0',
            'max_stock' => 'integer|min:0',
        ]);

        $oldValues = $product->toArray();

        $product->update([
            'sku' => strtoupper(trim($request->sku)),
            'name' => trim($request->name),
            'barcode' => trim($request->barcode),
            'category' => trim($request->category),
            'min_stock' => (int)$request->min_stock,
            'max_stock' => (int)$request->max_stock,
        ]);

        AuditLog::logAction(Auth::id(), 'UPDATE', 'products', $product->id, $oldValues, $product->toArray());

        return redirect()->route('products.index')->with('success', 'Cập nhật sản phẩm thành công!');
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        // Kiểm tra xem đã có giao dịch nào chưa
        if (ReceiptDetail::where('product_id', $id)->exists() || IssueDetail::where('product_id', $id)->exists()) {
            return redirect()->route('products.index')->with('error', 'Không thể xóa sản phẩm đã có lịch sử giao dịch kho!');
        }

        $oldValues = $product->toArray();
        $product->delete();

        AuditLog::logAction(Auth::id(), 'DELETE', 'products', $id, $oldValues, null);

        return redirect()->route('products.index')->with('success', 'Xóa sản phẩm thành công!');
    }
}
