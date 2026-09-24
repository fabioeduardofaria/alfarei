<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('key', 80)->unique();
            $table->timestamps();
        });

        $categories = [];
        foreach (['MDF', 'Acrílico'] as $name) {
            $key = Str::lower(Str::ascii($name));
            DB::table('material_categories')->insert(['name' => $name, 'key' => $key, 'created_at' => now(), 'updated_at' => now()]);
            $categories[$key] = $name;
        }

        foreach (DB::table('materials')->select(['id', 'category'])->orderBy('id')->get() as $material) {
            $name = preg_replace('/\s+/u', ' ', trim($material->category));
            $key = Str::lower(Str::ascii($name));
            if (! isset($categories[$key])) {
                DB::table('material_categories')->insert(['name' => $name, 'key' => $key, 'created_at' => now(), 'updated_at' => now()]);
                $categories[$key] = $name;
            }
            if ($material->category !== $categories[$key]) {
                DB::table('materials')->where('id', $material->id)->update(['category' => $categories[$key]]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('material_categories');
    }
};
