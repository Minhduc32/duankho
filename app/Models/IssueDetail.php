<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IssueDetail extends Model
{
    protected $fillable = ['issue_id', 'product_id', 'requested_pieces', 'actual_pieces'];

    public function issue()
    {
        return $this->belongsTo(Issue::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function issueAllocations()
    {
        return $this->hasMany(IssueAllocation::class);
    }
}
