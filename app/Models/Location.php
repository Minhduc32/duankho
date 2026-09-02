<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Location extends Model
{
    public $timestamps = false;

    protected $fillable = ['zone', 'rack', 'level', 'barcode', 'is_active'];

    public function inventoryCartons()
    {
        return $this->hasMany(InventoryCarton::class);
    }

    // Thống kê sơ bộ 7 dãy kho (A-G)
    public static function getZoneStats()
    {
        return self::leftJoin('inventory_cartons as ic', function($join) {
            $join->on('locations.id', '=', 'ic.location_id')
                 ->where('ic.status', '=', 'IN_STOCK')
                 ->where('ic.current_pieces', '>', 0);
        })
        ->select(
            'locations.zone',
            DB::raw("COUNT(DISTINCT CASE WHEN ic.status = 'IN_STOCK' AND ic.current_pieces > 0 THEN ic.product_id END) as total_products"),
            DB::raw("COALESCE(COUNT(CASE WHEN ic.status = 'IN_STOCK' AND ic.current_pieces > 0 THEN 1 END), 0) as total_cartons"),
            DB::raw("COALESCE(SUM(CASE WHEN ic.status = 'IN_STOCK' AND ic.current_pieces > 0 THEN ic.current_pieces ELSE 0 END), 0) as total_pieces")
        )
        ->groupBy('locations.zone')
        ->orderBy('locations.zone', 'ASC')
        ->get();
    }

    // Chi tiết hàng hóa chứa tại dãy cụ thể
    public static function getInventoryByZone($zone)
    {
        return self::join('inventory_cartons as ic', 'locations.id', '=', 'ic.location_id')
            ->join('products as p', 'ic.product_id', '=', 'p.id')
            ->leftJoin('receipt_details as rd', 'ic.receipt_detail_id', '=', 'rd.id')
            ->select(
                'locations.zone', 'locations.rack', 'locations.level', 'locations.barcode as location_barcode',
                'p.sku', 'p.name as product_name', 'p.category',
                'ic.id as carton_id', 'ic.carton_code', 'ic.current_pieces', 'ic.original_pieces', 'ic.received_at',
                'rd.lot_number'
            )
            ->where('ic.status', '=', 'IN_STOCK')
            ->where('ic.current_pieces', '>', 0)
            ->where('locations.zone', '=', $zone)
            ->orderBy('locations.rack', 'ASC')
            ->orderBy('locations.level', 'ASC')
            ->orderBy('ic.received_at', 'ASC')
            ->get();
    }
}
