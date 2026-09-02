<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryCarton extends Model
{
    protected $fillable = [
        'receipt_detail_id',
        'product_id',
        'carton_code',
        'original_pieces',
        'current_pieces',
        'location_id',
        'status',
        'received_at'
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    public function receiptDetail()
    {
        return $this->belongsTo(ReceiptDetail::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function issueAllocations()
    {
        return $this->hasMany(IssueAllocation::class);
    }
}
