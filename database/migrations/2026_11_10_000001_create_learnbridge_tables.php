<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('pelajar')->index();
            $table->string('headline')->nullable();
            $table->text('bio')->nullable();
            $table->boolean('profile_public')->default(false);
            $table->boolean('certificates_public')->default(false);
        });

        Schema::create('institutions', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name'); $table->string('type')->nullable(); $table->text('description')->nullable();
            $table->string('website')->nullable(); $table->string('verification_status')->default('pending')->index();
            $table->boolean('profile_public')->default(false); $table->boolean('certificates_public')->default(false);
            $table->text('verification_note')->nullable(); $table->timestamps();
        });
        Schema::create('skills', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->string('name'); $table->timestamps();
            $table->unique(['user_id', 'name']);
        });
        Schema::create('categories', function (Blueprint $table) { $table->id(); $table->string('name')->unique(); $table->text('description')->nullable(); $table->timestamps(); });
        foreach (['courses', 'bootcamps'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id(); $table->foreignId('institution_id')->constrained()->cascadeOnDelete(); $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
                $table->string('title'); $table->text('description')->nullable(); $table->string('status')->default('draft')->index();
                $table->unsignedInteger('passing_score')->default(70); $table->timestamps();
            });
        }
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->string('program_type'); $table->unsignedBigInteger('program_id');
            $table->string('status')->default('active'); $table->timestamp('enrolled_at')->nullable(); $table->timestamps(); $table->unique(['user_id','program_type','program_id']);
        });
        Schema::create('materials', function (Blueprint $table) {
            $table->id(); $table->string('program_type'); $table->unsignedBigInteger('program_id'); $table->string('title'); $table->longText('body')->nullable(); $table->unsignedInteger('position')->default(0); $table->timestamps();
            $table->index(['program_type','program_id']);
        });
        Schema::create('material_progress', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->boolean('completed')->default(false); $table->unsignedTinyInteger('percent')->default(0); $table->timestamps(); $table->unique(['user_id','material_id']);
        });
        Schema::create('material_notes', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->foreignId('material_id')->constrained()->cascadeOnDelete(); $table->text('body'); $table->timestamps();
        });
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id(); $table->foreignId('material_id')->constrained()->cascadeOnDelete(); $table->string('title'); $table->unsignedInteger('passing_score')->default(70); $table->timestamps();
        });
        Schema::create('questions', function (Blueprint $table) {
            $table->id(); $table->foreignId('quiz_id')->constrained()->cascadeOnDelete(); $table->text('prompt'); $table->json('options'); $table->unsignedTinyInteger('correct_option'); $table->unsignedInteger('points')->default(1); $table->timestamps();
        });
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id(); $table->foreignId('quiz_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->unsignedInteger('score'); $table->json('answers'); $table->timestamp('submitted_at'); $table->timestamps();
        });
        Schema::create('certificates', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->string('program_type'); $table->unsignedBigInteger('program_id');
            $table->string('verification_code')->unique(); $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('issued_at'); $table->timestamp('revoked_at')->nullable(); $table->text('revocation_reason')->nullable(); $table->timestamps();
        });
        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->string('program_type'); $table->unsignedBigInteger('program_id'); $table->timestamps(); $table->unique(['user_id','program_type','program_id']);
        });
        Schema::create('discussions', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->string('title'); $table->longText('body'); $table->string('status')->default('visible')->index(); $table->text('moderation_note')->nullable(); $table->timestamps();
        });
        Schema::create('comments', function (Blueprint $table) {
            $table->id(); $table->foreignId('discussion_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->text('body'); $table->timestamps();
        });
        Schema::create('reviews', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->string('program_type'); $table->unsignedBigInteger('program_id'); $table->unsignedTinyInteger('rating'); $table->text('body')->nullable(); $table->timestamps(); $table->unique(['user_id','program_type','program_id']);
        });
        Schema::create('announcements', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->string('title'); $table->longText('body'); $table->string('audience')->default('all'); $table->timestamp('published_at')->nullable(); $table->timestamps();
        });
        Schema::create('reports', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->string('content_type'); $table->unsignedBigInteger('content_id'); $table->string('category'); $table->text('description');
            $table->string('status')->default('open')->index(); $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete(); $table->text('resolution_note')->nullable(); $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['reports','announcements','reviews','comments','discussions','bookmarks','certificates','quiz_attempts','questions','quizzes','material_notes','material_progress','materials','enrollments','bootcamps','courses','categories','skills','institutions'] as $table) Schema::dropIfExists($table);
        Schema::table('users', function (Blueprint $table) { $table->dropColumn(['role','headline','bio','profile_public','certificates_public']); });
    }
};
