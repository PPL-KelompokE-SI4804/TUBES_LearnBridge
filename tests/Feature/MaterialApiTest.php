<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MaterialApiTest extends TestCase
{
    use RefreshDatabase;

    private function institutionUser(string $verificationStatus = 'approved'): array
    {
        $user = User::factory()->create(['role' => 'institusi']);
        $institutionId = DB::table('institutions')->insertGetId([
            'user_id' => $user->id, 'name' => 'Institusi Uji', 'verification_status' => $verificationStatus,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return [$user, $institutionId];
    }

    private function ownedProgram(int $institutionId, string $title = 'Program Uji'): int
    {
        return DB::table('courses')->insertGetId([
            'institution_id' => $institutionId, 'title' => $title, 'status' => 'published',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_institution_can_create_update_and_list_materials(): void
    {
        Storage::fake('local');
        [$user, $institutionId] = $this->institutionUser();
        $programId = $this->ownedProgram($institutionId);

        $created = $this->actingAs($user)->post('/api/v1/institution/materials', [
            'program_type' => 'course', 'program_id' => $programId,
            'title' => 'Pengenalan Laravel', 'body' => 'Materi awal pengenalan framework.',
            'source_url' => 'https://contoh.test/materi', 'position' => 1,
            'attachment' => UploadedFile::fake()->create('materi.pdf', 200, 'application/pdf'),
        ]);
        $created->assertCreated()
            ->assertJsonPath('data.title', 'Pengenalan Laravel')
            ->assertJsonPath('data.program_title', 'Program Uji')
            ->assertJsonPath('data.file_name', 'materi.pdf')
            ->assertJsonPath('data.file_url', route('material.file', $created->json('data.id')));
        Storage::disk('local')->assertExists($created->json('data.file_path'));

        $this->actingAs($user)->patch("/api/v1/institution/materials/{$created->json('data.id')}", ['title' => 'Pengenalan Laravel (Revisi)'])
            ->assertOk()->assertJsonPath('data.title', 'Pengenalan Laravel (Revisi)');

        $this->actingAs($user)->get('/api/v1/institution/materials')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Pengenalan Laravel (Revisi)');
    }

    public function test_validation_rejects_missing_fields_and_unsupported_file_format(): void
    {
        Storage::fake('local');
        [$user, $institutionId] = $this->institutionUser();
        $programId = $this->ownedProgram($institutionId);

        // Klien API mengirim header Accept: application/json agar galat
        // validasi dikembalikan sebagai JSON 422, bukan redirect formulir web.
        $this->actingAs($user)->post('/api/v1/institution/materials', ['program_type' => 'course', 'program_id' => $programId], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors(['title', 'body']);

        $this->actingAs($user)->post('/api/v1/institution/materials', [
            'program_type' => 'webinar', 'program_id' => $programId,
            'title' => 'Judul', 'body' => 'Deskripsi',
        ], ['Accept' => 'application/json'])->assertStatus(422)->assertJsonValidationErrors(['program_type']);

        $this->actingAs($user)->post('/api/v1/institution/materials', [
            'program_type' => 'course', 'program_id' => $programId,
            'title' => 'Judul', 'body' => 'Deskripsi',
            'attachment' => UploadedFile::fake()->create('skrip.exe', 10, 'application/octet-stream'),
        ], ['Accept' => 'application/json'])->assertStatus(422)->assertJsonValidationErrors(['attachment']);
    }

    public function test_institution_cannot_manage_material_of_another_institution(): void
    {
        [$owner, $ownerInstitutionId] = $this->institutionUser();
        $programId = $this->ownedProgram($ownerInstitutionId);
        $materialId = DB::table('materials')->insertGetId([
            'program_type' => 'course', 'program_id' => $programId, 'title' => 'Materi milik lain', 'body' => 'Deskripsi',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        [$intruder] = $this->institutionUser();

        $this->actingAs($intruder)->get("/api/v1/institution/materials/{$materialId}")->assertStatus(404);
        $this->actingAs($intruder)->patch("/api/v1/institution/materials/{$materialId}", ['title' => 'Perubahan'])->assertStatus(404);
        $this->actingAs($intruder)->delete("/api/v1/institution/materials/{$materialId}")->assertStatus(404);
        $this->assertDatabaseHas('materials', ['id' => $materialId]);
    }

    public function test_unapproved_institution_cannot_create_material(): void
    {
        [$user, $institutionId] = $this->institutionUser('pending');
        $programId = $this->ownedProgram($institutionId);

        $this->actingAs($user)->post('/api/v1/institution/materials', [
            'program_type' => 'course', 'program_id' => $programId, 'title' => 'Judul', 'body' => 'Deskripsi',
        ])->assertStatus(403);
    }

    public function test_updating_attachment_replaces_the_old_file(): void
    {
        Storage::fake('local');
        [$user, $institutionId] = $this->institutionUser();
        $programId = $this->ownedProgram($institutionId);
        $materialId = DB::table('materials')->insertGetId([
            'program_type' => 'course', 'program_id' => $programId, 'title' => 'Materi', 'body' => 'Deskripsi',
            'file_path' => 'materials/lama.pdf', 'file_name' => 'lama.pdf',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        Storage::disk('local')->put('materials/lama.pdf', 'isi lama');

        $this->actingAs($user)->patch("/api/v1/institution/materials/{$materialId}", [
            'attachment' => UploadedFile::fake()->create('baru.pdf', 100, 'application/pdf'),
        ])->assertOk()->assertJsonPath('data.file_name', 'baru.pdf');

        Storage::disk('local')->assertMissing('materials/lama.pdf');
        Storage::disk('local')->assertExists($this->actingAs($user)
            ->get("/api/v1/institution/materials/{$materialId}")->json('data.file_path'));
    }

    public function test_institution_can_delete_own_material_with_its_file(): void
    {
        Storage::fake('local');
        [$user, $institutionId] = $this->institutionUser();
        $programId = $this->ownedProgram($institutionId);
        $materialId = DB::table('materials')->insertGetId([
            'program_type' => 'course', 'program_id' => $programId, 'title' => 'Materi', 'body' => 'Deskripsi',
            'file_path' => 'materials/hapus.pdf', 'file_name' => 'hapus.pdf',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        Storage::disk('local')->put('materials/hapus.pdf', 'isi');

        $this->actingAs($user)->delete("/api/v1/institution/materials/{$materialId}")->assertOk();
        $this->assertDatabaseMissing('materials', ['id' => $materialId]);
        Storage::disk('local')->assertMissing('materials/hapus.pdf');
    }

    public function test_admin_can_delete_any_material_but_learner_cannot_manage(): void
    {
        [$institutionUser, $institutionId] = $this->institutionUser();
        $programId = $this->ownedProgram($institutionId);
        $materialId = DB::table('materials')->insertGetId([
            'program_type' => 'course', 'program_id' => $programId, 'title' => 'Materi', 'body' => 'Deskripsi',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $admin = User::factory()->create(['role' => 'admin']);
        $learner = User::factory()->create(['role' => 'pelajar']);

        $this->actingAs($learner)->post('/api/v1/institution/materials', [])->assertStatus(403);
        $this->actingAs($learner)->delete("/api/v1/admin/materials/{$materialId}")->assertStatus(403);

        $this->actingAs($admin)->get('/api/v1/admin/materials')->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($admin)->delete("/api/v1/admin/materials/{$materialId}")->assertOk();
        $this->assertDatabaseMissing('materials', ['id' => $materialId]);
    }

    public function test_index_only_lists_materials_of_own_programs(): void
    {
        [$userA, $institutionA] = $this->institutionUser();
        $programA = $this->ownedProgram($institutionA, 'Program A');
        [$userB, $institutionB] = $this->institutionUser();
        $programB = $this->ownedProgram($institutionB, 'Program B');
        DB::table('materials')->insert([
            ['program_type' => 'course', 'program_id' => $programA, 'title' => 'Materi A', 'body' => 'Deskripsi', 'created_at' => now(), 'updated_at' => now()],
            ['program_type' => 'course', 'program_id' => $programB, 'title' => 'Materi B', 'body' => 'Deskripsi', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->actingAs($userA)->get('/api/v1/institution/materials')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Materi A');
    }
}
