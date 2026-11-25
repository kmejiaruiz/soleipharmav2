<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DebitNote extends Model
{
    protected $fillable = [
        'note_number', 'goods_entry_id', 'order_id', 'supplier_id',
        'type', 'cost_difference', 'qty_extra_cost',
        'total_amount', 'total_debit', 'details', 'created_by',
    ];

    protected $casts = [
        'type'            => 'string',
        'cost_difference' => 'decimal:2',
        'qty_extra_cost'  => 'decimal:2',
        'total_amount'    => 'decimal:2',
        'details'         => 'array',
    ];

    // Etiqueta legible del tipo de nota
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'costs'      => 'Diferencia de Costos',
            'quantities' => 'Diferencia de Cantidades',
            'both'       => 'Diferencias de Costos y Cantidades',
            default      => 'Nota de Débito',
        };
    }

    public function goodsEntry() { return $this->belongsTo(GoodsEntry::class); }
    public function order()      { return $this->belongsTo(ProductOrder::class, 'order_id'); }
    public function supplier()   { return $this->belongsTo(Supplier::class); }
    public function creator()    { return $this->belongsTo(User::class, 'created_by'); }
}
