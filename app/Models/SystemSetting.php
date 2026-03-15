<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'data_type'
    ];

    /**
     * Helper to quickly get a setting value based on key.
     */
    public static function getValue($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Helper to quickly set or update a setting value.
     */
    public static function setValue($key, $value, $type = 'string')
    {
        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'data_type' => $type
            ]
        );
    }
}
