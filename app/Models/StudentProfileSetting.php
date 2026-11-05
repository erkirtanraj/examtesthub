<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentProfileSetting extends Model
{
    protected $fillable = [
        'allow_profile_updates',
        'allow_name_edit',
        'allow_email_edit',
        'allow_language_edit',
        'allow_password_change',
    ];

    protected function casts(): array
    {
        return [
            'allow_profile_updates' => 'boolean',
            'allow_name_edit' => 'boolean',
            'allow_email_edit' => 'boolean',
            'allow_language_edit' => 'boolean',
            'allow_password_change' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'allow_profile_updates' => true,
                'allow_name_edit' => true,
                'allow_email_edit' => true,
                'allow_language_edit' => true,
                'allow_password_change' => true,
            ],
        );
    }
}
