<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    protected $fillable = ['receipt_code', 'po_number', 'receipt_date', 'creator_id', 'status', 'note'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function receiptDetails()
    {
        return $this->hasMany(ReceiptDetail::class);
    }

    // Báo cáo chi tiết nhập hàng trong ngày
    public static function getDailyInbound($date)
    {
        return self::join('receipt_details as rd', 'receipts.id', '=', 'rd.receipt_id')
            ->join('inventory_cartons as ic', 'rd.id', '=', 'ic.receipt_detail_id')
            ->join('products as p', 'rd.product_id', '=', 'p.id')
            ->join('locations as l', 'ic.location_id', '=', 'l.id')
            ->join('users as u', 'receipts.creator_id', '=', 'u.id')
            ->select(
                'receipts.receipt_code', 'receipts.receipt_date', 'u.full_name as creator_name',
                'p.sku', 'p.name as product_name', 'rd.lot_number',
                'ic.carton_code', 'ic.original_pieces', 'l.zone', 'l.rack', 'l.level'
            )
            ->whereDate('receipts.receipt_date', '=', $date)
            ->orderBy('receipts.created_at', 'DESC')
            ->orderBy('ic.carton_code', 'ASC')
            ->get();
    }
}
