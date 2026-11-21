<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bootcamps', function (Blueprint $table) {
            $table->timestamp('schedule_at')->nullable()->after('description');
            $table->unsignedInteger('duration_minutes')->nullable()->after('schedule_at');
            $table->unsignedInteger('capacity')->nullable()->after('duration_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('bootcamps', function (Blueprint $table) {
            $table->dropColumn(['schedule_at', 'duration_minutes', 'capacity']);
        });
    }
};
