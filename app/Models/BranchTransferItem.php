<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchTransferItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['transfer_id', 'product_id', 'quantity_sent', 'quantity_received'];

    public function transfer() { return $this->belongsTo(BranchTransfer::class, 'transfer_id'); }
    public function product()  { return $this->belongsTo(Product::class); }
}
