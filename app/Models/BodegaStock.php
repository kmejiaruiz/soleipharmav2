<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BodegaStock extends Model
{
    public $timestamps = false;

    protected $table = 'bodega_stock';

    protected $fillable = ['product_id', 'bodega', 'stock'];

    public function product() { return $this->belongsTo(Product::class); }
}
