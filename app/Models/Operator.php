<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Operator extends Model
{
    protected $fillable = [
        'name',
        'category',
        'color',
        'logo',
        'contact_info',
    ];

    public function towers(): HasMany
    {
        return $this->hasMany(Tower::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    public function frequencyAllocations(): HasMany
    {
        return $this->hasMany(FrequencyAllocation::class);
    }
}
