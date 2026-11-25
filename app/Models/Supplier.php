<?php namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Supplier extends Model {
    protected $fillable=['name','contact_name','phone','email','address','notes'];
    public function products(){return $this->belongsToMany(Product::class,'supplier_catalog')->withPivot('cost_price')->withTimestamps();}
    public function productOrders(){return $this->hasMany(ProductOrder::class);}
}
