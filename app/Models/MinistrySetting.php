<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MinistrySetting extends Model
{
    protected $fillable = [
        'approval_director_name',
        'approval_director_title_so',
        'approval_director_title_en',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'approval_director_name' => config('ministry.approval_letter.director_name'),
            'approval_director_title_so' => config('ministry.approval_letter.director_title_so'),
            'approval_director_title_en' => config('ministry.approval_letter.director_title_en'),
        ]);
    }
}
