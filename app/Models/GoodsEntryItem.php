<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsEntryItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'goods_entry_id', 'product_id',
        'quantity_ordered', 'quantity_received',
        'quantity_factured', 'unit_cost', 'item_iva_rate',
        'missing_reason',
    ];

    protected $casts = [
        'unit_cost'     => 'decimal:2',
        'item_iva_rate' => 'decimal:4',
    ];

    // FK real: goods_entry_id (no entry_id — esa columna no existe en la BD)
    public function entry()   { return $this->belongsTo(GoodsEntry::class, 'goods_entry_id'); }
    public function product() { return $this->belongsTo(Product::class); }
}


