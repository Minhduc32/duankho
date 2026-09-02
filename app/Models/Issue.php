<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Issue extends Model
{
    protected $fillable = ['issue_code', 'order_number', 'issue_date', 'creator_id', 'status', 'note'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function issueDetails()
    {
        return $this->hasMany(IssueDetail::class);
    }

    // Báo cáo chi tiết xuất hàng trong ngày
    public static function getDailyOutbound($date)
    {
        return self::join('issue_details as idt', 'issues.id', '=', 'idt.issue_id')
            ->join('issue_allocations as ia', 'idt.id', '=', 'ia.issue_detail_id')
            ->join('inventory_cartons as ic', 'ia.inventory_carton_id', '=', 'ic.id')
            ->join('products as p', 'idt.product_id', '=', 'p.id')
            ->join('locations as l', 'ic.location_id', '=', 'l.id')
            ->leftJoin('receipt_details as rd', 'ic.receipt_detail_id', '=', 'rd.id')
            ->join('users as u', 'issues.creator_id', '=', 'u.id')
            ->select(
                'issues.issue_code', 'issues.issue_date', 'u.full_name as creator_name',
                'p.sku', 'p.name as product_name', 'rd.lot_number',
                'ic.carton_code', 'ia.pieces_issued', 'l.zone', 'l.rack', 'l.level'
            )
            ->whereDate('issues.issue_date', '=', $date)
            ->orderBy('issues.created_at', 'DESC')
            ->orderBy('ic.carton_code', 'ASC')
            ->get();
    }
}
