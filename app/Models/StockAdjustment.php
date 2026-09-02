<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    protected $fillable = ['product_id', 'inventory_carton_id', 'user_id', 'type', 'pieces_delta', 'reason'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryCarton()
    {
        return $this->belongsTo(InventoryCarton::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
