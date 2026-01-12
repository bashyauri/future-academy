<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'command',
        'status',
        'ip',
        'user_agent',
        'output',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
