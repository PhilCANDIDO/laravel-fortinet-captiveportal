<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminPasswordHistory extends Model
{
    protected $fillable = [
        'admin_user_id',
        'password',
    ];

    public function adminUser()
    {
        return $this->belongsTo(AdminUser::class);
    }
}