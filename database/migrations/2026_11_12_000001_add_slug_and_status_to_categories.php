<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
            $table->string('status', 20)->default('active')->after('description');
        });

        $used = [];
        foreach (DB::table('categories')->orderBy('id')->get() as $row) {
            $base = Str::slug($row->name) ?: 'kategori';
            $slug = $base;
            $i = 2;
            while (isset($used[$slug])) {
                $slug = $base.'-'.$i;
                $i++;
            }
            $used[$slug] = true;
            DB::table('categories')->where('id', $row->id)->update(['slug' => $slug]);
        }

        DB::statement('ALTER TABLE categories MODIFY slug VARCHAR(255) NOT NULL');
        Schema::table('categories', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'status']);
        });
    }
};
