<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    protected $fillable = ['sku', 'name', 'barcode', 'category', 'min_stock', 'max_stock'];

    public function inventoryCartons()
    {
        return $this->hasMany(InventoryCarton::class);
    }

    // Thống kê tồn kho chi tiết (theo Cái và Thùng) dùng Eloquent Query Builder
    public static function getStockStatus($productId = null)
    {
        $query = self::leftJoin('inventory_cartons as ic', function($join) {
            $join->on('products.id', '=', 'ic.product_id')
                 ->where('ic.status', '=', 'IN_STOCK')
                 ->where('ic.current_pieces', '>', 0);
        })
        ->select(
            'products.id',
            'products.id as product_id',
            'products.sku',
            'products.name',
            'products.category',
            'products.min_stock',
            'products.max_stock',
            DB::raw('COALESCE(SUM(ic.current_pieces), 0) as total_pieces'),
            DB::raw('COALESCE(COUNT(ic.id), 0) as total_cartons')
        )
        ->groupBy('products.id', 'products.sku', 'products.name', 'products.category', 'products.min_stock', 'products.max_stock');

        if ($productId) {
            return $query->where('products.id', $productId)->first();
        }

        return $query->orderBy('products.sku', 'ASC')->get();
    }
}
