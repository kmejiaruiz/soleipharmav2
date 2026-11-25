<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsEntry extends Model
{
    protected $fillable = [
        'order_id', 'supplier_id', 'invoice_number',
        'invoice_subtotal', 'invoice_tax',
        'system_subtotal',  'system_tax', 'system_total',
        'iva_rate', 'notes', 'created_by', 'received_by', 'branch',
    ];

    protected $casts = [
        'invoice_subtotal' => 'decimal:2',
        'invoice_tax'      => 'decimal:2',
        'system_subtotal'  => 'decimal:2',
        'system_tax'       => 'decimal:2',
        'system_total'     => 'decimal:2',
        'iva_rate'         => 'decimal:4',
    ];

    public function order()      { return $this->belongsTo(ProductOrder::class, 'order_id'); }
    public function supplier()   { return $this->belongsTo(Supplier::class); }
    // FK real en BD: goods_entry_id (la migración original usa ese nombre)
    public function items()      { return $this->hasMany(GoodsEntryItem::class, 'goods_entry_id'); }
    public function creator()    { return $this->belongsTo(User::class, 'created_by'); }
    public function debitNote()  { return $this->hasOne(DebitNote::class); }
}
