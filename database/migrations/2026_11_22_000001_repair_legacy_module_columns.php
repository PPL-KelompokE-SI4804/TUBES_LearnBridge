<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        // Older GitHub databases may have run the original migration before
        // these columns were added to the application. Add only what is absent.
        foreach (['courses', 'bootcamps'] as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'status')) {
                Schema::table($tableName, function (Blueprint $table) {
                    // Rows created before status existed were visible programs.
                    $table->string('status')->default('published');
                });
            }
        }

        if (Schema::hasTable('materials')) {
            Schema::table('materials', function (Blueprint $table) {
                if (!Schema::hasColumn('materials', 'source_url')) {
                    $table->string('source_url', 2048)->nullable();
                }
                if (!Schema::hasColumn('materials', 'file_path')) {
                    $table->string('file_path')->nullable();
                }
                if (!Schema::hasColumn('materials', 'file_name')) {
                    $table->string('file_name')->nullable();
                }
            });
        }

        $slugAdded = false;
        if (Schema::hasTable('categories')) {
            if (!Schema::hasColumn('categories', 'slug')) {
                Schema::table('categories', function (Blueprint $table) {
                    $table->string('slug')->nullable();
                });
                $slugAdded = true;
            }
            if (!Schema::hasColumn('categories', 'status')) {
                Schema::table('categories', function (Blueprint $table) {
                    $table->string('status', 20)->default('active');
                });
            }

            $used = DB::table('categories')->whereNotNull('slug')->pluck('slug')->all();
            foreach (DB::table('categories')->where(fn ($query) => $query->whereNull('slug')->orWhere('slug', ''))->orderBy('id')->get(['id', 'name']) as $category) {
                $base = Str::slug($category->name) ?: 'kategori';
                $slug = $base;
                $suffix = 2;
                while (in_array($slug, $used, true)) {
                    $slug = $base.'-'.$suffix++;
                }
                $used[] = $slug;
                DB::table('categories')->where('id', $category->id)->update(['slug' => $slug]);
            }

            if ($slugAdded) {
                Schema::table('categories', function (Blueprint $table) {
                    $table->unique('slug');
                });
            }
        }

        if (Schema::hasTable('institutions') && !Schema::hasColumn('institutions', 'is_active')) {
            Schema::table('institutions', function (Blueprint $table) {
                $table->boolean('is_active')->default(true);
            });
        }
    }

    public function down(): void
    {
        // Compatibility-only: keep repaired columns so rollback of application
        // code cannot silently remove data written after this migration.
    }
};
