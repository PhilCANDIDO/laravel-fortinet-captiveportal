<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AdminSession extends Model
{
    protected $keyType = 'string';
    
    public $incrementing = false;
    
    protected $fillable = [
        'id',
        'admin_user_id',
        'ip_address',
        'user_agent',
        'payload',
        'last_activity',
        'is_active',
        'expires_at',
    ];

    protected $casts = [
        'last_activity' => 'integer',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function adminUser()
    {
        return $this->belongsTo(AdminUser::class);
    }

    public function isExpired(): bool
    {
        $fifteenMinutesAgo = Carbon::now()->subMinutes(15)->timestamp;
        return $this->last_activity < $fifteenMinutesAgo;
    }

    public function touch($attribute = null): bool
    {
        if ($attribute) {
            return parent::touch($attribute);
        }
        
        return $this->update([
            'last_activity' => Carbon::now()->timestamp,
        ]);
    }

    public function deactivate(): void
    {
        $this->update([
            'is_active' => false,
        ]);
    }
}