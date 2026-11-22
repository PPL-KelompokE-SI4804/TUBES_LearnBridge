<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CourseApiTest extends TestCase
{
    use RefreshDatabase;

    private function institutionUser(): array
    {
        $user = User::factory()->create(['role' => 'institusi']);
        $institutionId = DB::table('institutions')->insertGetId([
            'user_id' => $user->id, 'name' => 'Institusi Uji', 'verification_status' => 'approved',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return [$user, $institutionId];
    }

    public function test_institution_can_create_publish_and_list_its_course(): void
    {
        [$user, $institutionId] = $this->institutionUser();
        $categoryId = DB::table('categories')->insertGetId(['name' => 'Teknologi', 'created_at' => now(), 'updated_at' => now()]);

        $created = $this->actingAs($user)->post('/api/v1/institution/courses', [
            'title' => 'Laravel Dasar', 'description' => 'Belajar Laravel', 'category_id' => $categoryId,
        ]);
        $created->assertCreated()->assertJsonPath('data.status', 'draft')->assertJsonPath('data.institution_id', $institutionId);
        $courseId = $created->json('data.id');

        $this->actingAs($user)->post("/api/v1/institution/courses/{$courseId}/publish")
            ->assertOk()->assertJsonPath('data.status', 'published');
        $this->actingAs($user)->get('/api/v1/institution/courses')
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_public_catalog_only_exposes_published_courses(): void
    {
        [, $institutionId] = $this->institutionUser();
        DB::table('courses')->insert([
            ['institution_id' => $institutionId, 'title' => 'Publik', 'status' => 'published', 'created_at' => now(), 'updated_at' => now()],
            ['institution_id' => $institutionId, 'title' => 'Draft', 'status' => 'draft', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->get('/api/v1/courses')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Publik');
    }

    public function test_course_with_enrollment_cannot_be_deleted(): void
    {
        [$user, $institutionId] = $this->institutionUser();
        $courseId = DB::table('courses')->insertGetId(['institution_id' => $institutionId, 'title' => 'Kursus', 'status' => 'draft', 'created_at' => now(), 'updated_at' => now()]);
        $learner = User::factory()->create(['role' => 'pelajar']);
        DB::table('enrollments')->insert(['user_id' => $learner->id, 'program_type' => 'course', 'program_id' => $courseId, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($user)->delete("/api/v1/institution/courses/{$courseId}")
            ->assertStatus(422);
    }
}
