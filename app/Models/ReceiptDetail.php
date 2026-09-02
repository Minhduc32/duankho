<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiptDetail extends Model
{
    protected $fillable = ['receipt_id', 'product_id', 'lot_number', 'total_cartons', 'total_pieces', 'price'];

    public function receipt()
    {
        return $this->belongsTo(Receipt::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryCartons()
    {
        return $this->hasMany(InventoryCarton::class);
    }
}
