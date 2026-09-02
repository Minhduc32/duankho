<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IssueAllocation extends Model
{
    protected $fillable = ['issue_detail_id', 'inventory_carton_id', 'pieces_issued'];

    public function issueDetail()
    {
        return $this->belongsTo(IssueDetail::class);
    }

    public function inventoryCarton()
    {
        return $this->belongsTo(InventoryCarton::class);
    }
}
