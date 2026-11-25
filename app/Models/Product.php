<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'price', 'cost', 'purchase_tax_percent',
        'utility_percent', 'tax_percent',
        'image', 'stock', 'available', 'reason_unavailable',
        'branch', 'category_id', 'supplier_id', 'barcode',
    ];

    protected $casts = [
        'available'           => 'boolean',
        'price'               => 'decimal:2',
        'cost'                => 'decimal:2',
        'purchase_tax_percent'=> 'decimal:2',
        'utility_percent'     => 'decimal:2',
        'tax_percent'         => 'decimal:2',
    ];

    // ── Relaciones ─────────────────────────────────────────────────────────────
    public function inventoryLogs()    { return $this->hasMany(InventoryLog::class); }
    public function orderItems()       { return $this->hasMany(OrderItem::class); }
    public function discardRequests()  { return $this->hasMany(DiscardRequest::class); }
    public function category()        { return $this->belongsTo(Category::class); }
    public function suppliers()        { return $this->belongsToMany(Supplier::class, 'supplier_catalog'); }
    public function bodegaStock()      { return $this->hasMany(BodegaStock::class); }
    public function bodegaMovements()  { return $this->hasMany(BodegaMovement::class); }

    // ── Scopes ─────────────────────────────────────────────────────────────────
    public function scopeAvailable($q)           { return $q->where('available', true); }
    public function scopeByBranch($q, $branch)   { return $q->where('branch', $branch); }
    public function scopeLowStock($q, int $min=5) { return $q->where('stock', '<=', $min); }

    // ── Helpers ────────────────────────────────────────────────────────────────
    public function imageUrl(): string
    {
        if (filter_var($this->image, FILTER_VALIDATE_URL)) {
            return $this->image;
        }
        return asset('storage/products/' . $this->image);
    }
}
