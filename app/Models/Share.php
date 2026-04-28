<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Share extends Model
{
    protected $fillable = [
        'service_id',
        'from_user_id',
        'to_user_id',
        'shared_at',
        'accepted_at',
        'rejected',
        'shared_data',
    ];

    protected $casts = [
        'shared_at'    => 'datetime',
        'accepted_at'  => 'datetime',
        'rejected'     => 'boolean',
    ];

    public function service() {
        return $this->belongsTo(Service::class);
    }

    public function sharedService() {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function fromUser() { return $this->belongsTo(User::class, 'from_user_id'); }

    public function toUser() { return $this->belongsTo(User::class, 'to_user_id'); }
}
