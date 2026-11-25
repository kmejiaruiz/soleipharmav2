<?php namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class BranchTransfer extends Model { public $timestamps=false; protected $fillable=['from_branch','to_branch','status','notes','created_by','received_by','cancelled_by']; public function creator(){return $this->belongsTo(User::class,'created_by');} public function receiver(){return $this->belongsTo(User::class,'received_by');} public function items(){return $this->hasMany(BranchTransferItem::class,'transfer_id');}  public function scopePendiente($q){return $q->where('status','pendiente');}}
