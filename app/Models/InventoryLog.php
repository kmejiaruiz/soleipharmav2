<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryLog extends Model
{
    public $timestamps = false;

    protected $table = 'inventory_log';

    protected $fillable = [
        'product_id', 'admin_id', 'admin_name', 'branch', 'change_type',
        'previous_stock', 'new_stock', 'description',
    ];

    public function product() { return $this->belongsTo(Product::class); }
    public function admin()   { return $this->belongsTo(User::class, 'admin_id'); }
}
