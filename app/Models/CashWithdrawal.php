<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashWithdrawal extends Model
{
    protected $fillable = ['session_id', 'withdrawn_by', 'total_amount', 'denominations', 'reason'];

    protected $casts = [
        'total_amount'  => 'decimal:2',
        'denominations' => 'array',
    ];

    public function session()    { return $this->belongsTo(CashSession::class, 'session_id'); }
    public function cashSession(){ return $this->belongsTo(CashSession::class, 'session_id'); }
    public function withdrawer() { return $this->belongsTo(User::class, 'withdrawn_by'); }
}
