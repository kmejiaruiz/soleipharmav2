<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryCountItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_count_id',
        'product_id',
        'system_stock',
        'counted_stock',
        'difference',
        'unit_cost',
        'total_cost_diff',
        'notes',
    ];

    protected $casts = [
        'system_stock'    => 'integer',
        'counted_stock'   => 'integer',
        'difference'      => 'integer',
        'unit_cost'       => 'decimal:2',
        'total_cost_diff' => 'decimal:2',
    ];

    public function inventoryCount()
    {
        return $this->belongsTo(InventoryCount::class, 'inventory_count_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
