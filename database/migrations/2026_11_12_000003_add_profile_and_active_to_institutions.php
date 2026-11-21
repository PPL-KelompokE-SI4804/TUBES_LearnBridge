<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->string('address')->nullable()->after('type');
            $table->string('contact')->nullable()->after('address');
            $table->boolean('is_active')->default(true)->after('verification_note');
        });
    }

    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->dropColumn(['address', 'contact', 'is_active']);
        });
    }
};
