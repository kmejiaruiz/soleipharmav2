<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryCount extends Model
{
    use HasFactory;

    protected $fillable = [
        'count_number',
        'branch',
        'category_id',
        'status',
        'notes',
        'created_by',
        'applied_by',
        'total_items_counted',
        'total_discrepancies',
        'net_cost_difference',
        'applied_at',
    ];

    protected $casts = [
        'net_cost_difference' => 'decimal:2',
        'applied_at'          => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function appliedBy()
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function items()
    {
        return $this->hasMany(InventoryCountItem::class, 'inventory_count_id');
    }
}
