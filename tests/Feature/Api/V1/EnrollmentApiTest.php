<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EnrollmentApiTest extends TestCase
{
    use RefreshDatabase;

    private function basicAuth(User $user): array
    {
        return [
            'Authorization' => 'Basic '.base64_encode($user->email.':password'),
            'Accept' => 'application/json',
        ];
    }

    private function institutionId(User $user): int
    {
        return DB::table('institutions')->insertGetId([
            'user_id' => $user->id,
            'name' => 'Institusi Pengujian',
            'verification_status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function program(string $type, int $institutionId, string $status = 'published'): int
    {
        return DB::table($type === 'course' ? 'courses' : 'bootcamps')->insertGetId([
            'institution_id' => $institutionId,
            'title' => $type === 'course' ? 'Kursus API' : 'Bootcamp API',
            'status' => $status,
            'passing_score' => 70,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_peserta_can_enroll_once_in_published_courses_and_bootcamps(): void
    {
        $participant = User::factory()->create(['role' => 'pelajar']);
        $institutionId = $this->institutionId(User::factory()->create(['role' => 'institusi']));
        $courseId = $this->program('course', $institutionId);
        $bootcampId = $this->program('bootcamp', $institutionId);

        $this->withHeaders($this->basicAuth($participant))->postJson("/api/v1/courses/{$courseId}/enrollments")
            ->assertCreated()->assertJsonPath('data.program_type', 'course')->assertJsonPath('data.status', 'active');
        $this->withHeaders($this->basicAuth($participant))->postJson("/api/v1/courses/{$courseId}/enrollments")
            ->assertConflict();
        $this->withHeaders($this->basicAuth($participant))->postJson("/api/v1/bootcamps/{$bootcampId}/enrollments")
            ->assertCreated()->assertJsonPath('data.program_type', 'bootcamp');

        $draftId = $this->program('course', $institutionId, 'draft');
        $this->withHeaders($this->basicAuth($participant))->postJson("/api/v1/courses/{$draftId}/enrollments")
            ->assertNotFound();
        $this->assertDatabaseCount('enrollments', 2);
    }

    public function test_peserta_can_read_only_their_own_enrollments_and_filter_by_status(): void
    {
        $participant = User::factory()->create(['role' => 'pelajar']);
        $otherParticipant = User::factory()->create(['role' => 'pelajar']);
        $institutionId = $this->institutionId(User::factory()->create(['role' => 'institusi']));
        $courseId = $this->program('course', $institutionId);
        $enrollmentId = DB::table('enrollments')->insertGetId([
            'user_id' => $participant->id,
            'program_type' => 'course',
            'program_id' => $courseId,
            'status' => 'active',
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withHeaders($this->basicAuth($participant))->getJson('/api/v1/users/me/enrollments')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $enrollmentId);
        $this->withHeaders($this->basicAuth($participant))->getJson('/api/v1/users/me/enrollments?status=cancelled')
            ->assertOk()->assertJsonCount(0, 'data');
        $this->withHeaders($this->basicAuth($participant))->getJson('/api/v1/users/me/enrollments?status=invalid')
            ->assertUnprocessable();
        $this->withHeaders($this->basicAuth($participant))->getJson("/api/v1/users/me/enrollments/{$enrollmentId}")
            ->assertOk()->assertJsonPath('data.program.title', 'Kursus API');
        $this->withHeaders($this->basicAuth($otherParticipant))->getJson("/api/v1/users/me/enrollments/{$enrollmentId}")
            ->assertNotFound();
    }

    public function test_institution_can_read_enrollments_only_for_its_programs(): void
    {
        $institutionUser = User::factory()->create(['role' => 'institusi']);
        $institutionId = $this->institutionId($institutionUser);
        $otherInstitutionId = $this->institutionId(User::factory()->create(['role' => 'institusi']));
        $ownedCourseId = $this->program('course', $institutionId);
        $otherCourseId = $this->program('course', $otherInstitutionId);
        $participant = User::factory()->create(['role' => 'pelajar']);
        $ownedEnrollmentId = DB::table('enrollments')->insertGetId([
            'user_id' => $participant->id, 'program_type' => 'course', 'program_id' => $ownedCourseId,
            'status' => 'active', 'enrolled_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $otherEnrollmentId = DB::table('enrollments')->insertGetId([
            'user_id' => $participant->id, 'program_type' => 'course', 'program_id' => $otherCourseId,
            'status' => 'active', 'enrolled_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->withHeaders($this->basicAuth($institutionUser))->getJson('/api/v1/institution/enrollments')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $ownedEnrollmentId)
            ->assertJsonPath('data.0.participant.id', $participant->id);
        $this->withHeaders($this->basicAuth($institutionUser))->getJson("/api/v1/institution/enrollments/{$ownedEnrollmentId}")
            ->assertOk();
        $this->withHeaders($this->basicAuth($institutionUser))->getJson("/api/v1/institution/enrollments/{$otherEnrollmentId}")
            ->assertNotFound();
    }

    public function test_peserta_can_update_notes_and_cancel_once_without_deleting_record(): void
    {
        $participant = User::factory()->create(['role' => 'pelajar']);
        $institutionId = $this->institutionId(User::factory()->create(['role' => 'institusi']));
        $courseId = $this->program('course', $institutionId);
        $enrollmentId = DB::table('enrollments')->insertGetId([
            'user_id' => $participant->id, 'program_type' => 'course', 'program_id' => $courseId,
            'status' => 'active', 'enrolled_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->withHeaders($this->basicAuth($participant))->patchJson("/api/v1/users/me/enrollments/{$enrollmentId}", ['notes' => 'Perlu akses malam'])
            ->assertOk()->assertJsonPath('data.notes', 'Perlu akses malam');
        $this->withHeaders($this->basicAuth($participant))->patchJson("/api/v1/users/me/enrollments/{$enrollmentId}", ['status' => 'cancelled'])
            ->assertUnprocessable();
        $this->withHeaders($this->basicAuth($participant))->postJson("/api/v1/users/me/enrollments/{$enrollmentId}/cancel")
            ->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->withHeaders($this->basicAuth($participant))->postJson("/api/v1/users/me/enrollments/{$enrollmentId}/cancel")
            ->assertConflict();
        $this->assertDatabaseHas('enrollments', ['id' => $enrollmentId, 'status' => 'cancelled']);
        $this->withHeaders($this->basicAuth($participant))->deleteJson("/api/v1/users/me/enrollments/{$enrollmentId}")
            ->assertMethodNotAllowed();
    }

    public function test_endpoints_require_basic_authentication_and_the_expected_role(): void
    {
        $participant = User::factory()->create(['role' => 'pelajar']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->getJson('/api/v1/users/me/enrollments')->assertUnauthorized();
        $this->withHeaders($this->basicAuth($admin))->getJson('/api/v1/users/me/enrollments')->assertForbidden();
        $this->withHeaders(['Authorization' => 'Basic '.base64_encode($participant->email.':wrong'), 'Accept' => 'application/json'])
            ->getJson('/api/v1/users/me/enrollments')->assertUnauthorized();
    }
}
