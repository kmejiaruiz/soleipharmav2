<?php namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Order extends Model
{
    protected $fillable = [
        'user_id','total','status','payment_method',
        'amount_paid','change_given','branch','cash_session_id',
        'client_name',
    ];
    protected $casts = ['total'=>'decimal:2','amount_paid'=>'decimal:2','change_given'=>'decimal:2'];
    public function user()    { return $this->belongsTo(User::class); }
    public function items()   { return $this->hasMany(OrderItem::class); }
    public function session() { return $this->belongsTo(CashSession::class,'cash_session_id'); }
}
