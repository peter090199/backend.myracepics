<?php

namespace App\Models\Event;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class Events extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'events'; 
    public $timestamps = true;

    protected $fillable = [
        'title',
        'location',
        'date',
        'category',
        'image',
        'uuid', // add uuid to fillable
        'role',
        'role_code'
    ];

    /**
     * Boot method to auto-generate UUID on creating event
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
