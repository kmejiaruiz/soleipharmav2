<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductOrderItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['order_id', 'product_id', 'quantity', 'price'];

    protected $casts = ['price' => 'decimal:2'];

    public function order()   { return $this->belongsTo(ProductOrder::class, 'order_id'); }
    public function product() { return $this->belongsTo(Product::class); }
}
