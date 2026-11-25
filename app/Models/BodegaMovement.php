<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BodegaMovement extends Model
{
    public $timestamps = false;

    protected $fillable = ['product_id', 'from_bodega', 'to_bodega', 'quantity', 'reason', 'user_id'];

    public function product() { return $this->belongsTo(Product::class); }
    public function user()    { return $this->belongsTo(User::class); }
}
