<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscardRequest extends Model
{
    use HasFactory;

    protected $table = 'discard_requests';

    protected $fillable = [
        'product_id',
        'requested_by',
        'branch',
        'quantity',
        'reason',
        'assigned_to',
        'status',
        'is_follow_up',
        'decision_by',
        'decision_reason',
        'decision_at',
    ];

    protected $casts = [
        'decision_at' => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decider()
    {
        return $this->belongsTo(User::class, 'decision_by');
    }
}
