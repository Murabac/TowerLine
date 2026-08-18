<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    protected $fillable = [
        'name_en',
        'name_so',
    ];

    public function localizedName(): string
    {
        return app()->getLocale() === 'so' ? $this->name_so : $this->name_en;
    }

    public function towers(): HasMany
    {
        return $this->hasMany(Tower::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
