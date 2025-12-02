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
        'type',
        'group',
        'description'
    ];

    // Méthodes utilitaires pour récupérer les paramètres
    public static function get($key, $default = null)
    {
        $setting = self::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return self::castValue($setting->value, $setting->type);
    }

    public static function set($key, $value, $type = 'string', $group = 'general')
    {
        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'group' => $group
            ]
        );
    }

    public static function getByGroup($group)
    {
        return self::where('group', $group)->get()->mapWithKeys(function ($setting) {
            return [$setting->key => self::castValue($setting->value, $setting->type)];
        });
    }

    protected static function castValue($value, $type)
    {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'float':
            case 'decimal':
                return (float) $value;
            case 'json':
            case 'array':
                return json_decode($value, true);
            default:
                return $value;
        }
    }
}
