<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MaterialCategory extends Model
{
    protected $fillable = ['name', 'key'];

    public static function cleanName(string $name): string
    {
        return preg_replace('/\s+/u', ' ', trim($name));
    }

    public static function keyFor(string $name): string
    {
        return Str::lower(Str::ascii(self::cleanName($name)));
    }
}
