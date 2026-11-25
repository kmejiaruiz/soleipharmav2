<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductOrder extends Model
{
    protected $fillable = [
        'admin_id',
        'admin_name',
        'branch',
        'supplier_id',
        'status',
        'applied_by',
        'applied_at',
        'order_date',
        'total',
        'invoice_subtotal',
        'invoice_tax',
    ];

    protected $casts = [
        'applied_at' => 'datetime',
        'order_date' => 'datetime',
        'total'      => 'decimal:2',
    ];

    public function admin()      { return $this->belongsTo(User::class, 'admin_id'); }
    public function appliedBy()  { return $this->belongsTo(User::class, 'applied_by'); }
    public function supplier()   { return $this->belongsTo(Supplier::class); }
    public function items()      { return $this->hasMany(ProductOrderItem::class, 'order_id'); }
    public function goodsEntry() { return $this->hasOne(GoodsEntry::class, 'order_id'); }
}
