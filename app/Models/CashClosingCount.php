<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashClosingCount extends Model
{
    protected $fillable = ['session_id', 'counted_by', 'counted_amount', 'denominations', 'expected_amount', 'difference'];

    protected $casts = [
        'counted_amount'  => 'decimal:2',
        'expected_amount' => 'decimal:2',
        'difference'      => 'decimal:2',
        'denominations'   => 'array',
    ];

    public function session() { return $this->belongsTo(CashSession::class, 'session_id'); }
    public function counter() { return $this->belongsTo(User::class, 'counted_by'); }
}
