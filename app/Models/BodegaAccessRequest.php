<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BodegaAccessRequest extends Model
{
    protected $table = 'bodega_access_requests';

    protected $fillable = [
        'user_id',
        'branch',
        'status',
        'responded_by_id',
        'responded_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function respondedBy()
    {
        return $this->belongsTo(User::class, 'responded_by_id');
    }
}
