<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Website extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'primary_domain',
        'business_id',
        'status',
        'seo_defaults',
        'logo',
        'settings',
        'published_at',
    ];

    protected $casts = [
        'seo_defaults' => 'array',
        'settings' => 'array',
        'published_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
