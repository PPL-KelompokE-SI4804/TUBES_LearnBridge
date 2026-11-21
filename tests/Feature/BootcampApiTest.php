<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BootcampApiTest extends TestCase
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

    public function test_institution_can_create_publish_and_list_a_bootcamp(): void
    {
        [$user, $institutionId] = $this->institutionUser();
        $created = $this->actingAs($user)->post('/api/v1/institution/bootcamps', [
            'title' => 'Bootcamp Laravel', 'description' => 'Kelas intensif',
            'schedule_at' => '2026-12-01 09:00:00', 'duration_minutes' => 480, 'capacity' => 30,
        ]);
        $created->assertCreated()->assertJsonPath('data.status', 'draft')->assertJsonPath('data.institution_id', $institutionId);
        $bootcampId = $created->json('data.id');

        $this->actingAs($user)->post("/api/v1/institution/bootcamps/{$bootcampId}/publish")
            ->assertOk()->assertJsonPath('data.status', 'published');
        $this->actingAs($user)->get('/api/v1/institution/bootcamps')
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_public_catalog_only_exposes_published_bootcamps(): void
    {
        [, $institutionId] = $this->institutionUser();
        DB::table('bootcamps')->insert([
            ['institution_id' => $institutionId, 'title' => 'Publik', 'status' => 'published', 'created_at' => now(), 'updated_at' => now()],
            ['institution_id' => $institutionId, 'title' => 'Draft', 'status' => 'draft', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->get('/api/v1/bootcamps')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Publik');
    }

    public function test_bootcamp_with_enrollment_cannot_be_deleted(): void
    {
        [$user, $institutionId] = $this->institutionUser();
        $bootcampId = DB::table('bootcamps')->insertGetId(['institution_id' => $institutionId, 'title' => 'Bootcamp', 'status' => 'draft', 'created_at' => now(), 'updated_at' => now()]);
        $learner = User::factory()->create(['role' => 'pelajar']);
        DB::table('enrollments')->insert(['user_id' => $learner->id, 'program_type' => 'bootcamp', 'program_id' => $bootcampId, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($user)->delete("/api/v1/institution/bootcamps/{$bootcampId}")
            ->assertStatus(422);
    }
}
