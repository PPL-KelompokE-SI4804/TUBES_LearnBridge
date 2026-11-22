<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CourseApiController extends Controller
{
    public function catalog(): JsonResponse
    {
        return response()->json(['data' => $this->baseQuery()
            ->where('courses.status', 'published')->latest('courses.id')->get()]);
    }

    public function publicShow(int $id): JsonResponse
    {
        $course = $this->baseQuery()->where('courses.id', $id)->where('courses.status', 'published')->first();
        abort_unless($course, 404, 'Kursus tidak ditemukan.');
        return response()->json(['data' => $course]);
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->baseQuery()
            ->where('courses.institution_id', $this->institutionId($request))
            ->latest('courses.id')->get()]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return response()->json(['data' => $this->ownedCourse($request, $id)]);
    }

    public function store(Request $request): JsonResponse
    {
        $institutionId = $this->institutionId($request, true);
        $data = $request->validate($this->courseRules());
        $data['institution_id'] = $institutionId;
        $data['status'] = 'draft';
        $data['thumbnail'] = $this->storeThumbnail($request);
        $data['created_at'] = now();
        $data['updated_at'] = now();
        $id = DB::table('courses')->insertGetId($data);

        return response()->json(['message' => 'Kursus berhasil dibuat sebagai draft.', 'data' => $this->courseById($id)], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $course = $this->ownedCourse($request, $id);
        $data = $request->validate($this->courseRules(true));
        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $this->storeThumbnail($request);
            $this->deleteThumbnail($course->thumbnail);
        }
        DB::table('courses')->where('id', $id)->update($data + ['updated_at' => now()]);
        return response()->json(['message' => 'Informasi kursus berhasil diperbarui.', 'data' => $this->courseById($id)]);
    }

    public function publish(Request $request, int $id): JsonResponse
    {
        $this->ownedCourse($request, $id);
        DB::table('courses')->where('id', $id)->update(['status' => 'published', 'updated_at' => now()]);
        return response()->json(['message' => 'Kursus berhasil diterbitkan.', 'data' => $this->courseById($id)]);
    }

    public function archive(Request $request, int $id): JsonResponse
    {
        $this->ownedCourse($request, $id);
        DB::table('courses')->where('id', $id)->update(['status' => 'archived', 'updated_at' => now()]);
        return response()->json(['message' => 'Kursus berhasil diarsipkan.', 'data' => $this->courseById($id)]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $course = $this->ownedCourse($request, $id);
        abort_if(DB::table('enrollments')->where('program_type', 'course')->where('program_id', $id)->exists(), 422, 'Kursus tidak dapat dihapus karena memiliki riwayat peserta.');
        DB::table('courses')->where('id', $id)->delete();
        $this->deleteThumbnail($course->thumbnail);
        return response()->json(['message' => 'Kursus berhasil dihapus.']);
    }

    public function adminIndex(): JsonResponse
    {
        return response()->json(['data' => $this->baseQuery()->latest('courses.id')->get()]);
    }

    public function adminShow(int $id): JsonResponse
    {
        return response()->json(['data' => $this->courseById($id)]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $this->courseById($id);
        $data = $request->validate(['status' => ['required', 'in:draft,published,archived,hidden']]);
        DB::table('courses')->where('id', $id)->update($data + ['updated_at' => now()]);
        return response()->json(['message' => 'Status administratif kursus berhasil diperbarui.', 'data' => $this->courseById($id)]);
    }

    private function courseRules(bool $partial = false): array
    {
        return [
            'title' => [$partial ? 'sometimes' : 'required', 'string', 'max:180'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'thumbnail' => ['nullable', 'image', 'max:2048'],
            'passing_score' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }

    private function institutionId(Request $request, bool $mustBeApproved = false): int
    {
        $institution = DB::table('institutions')->where('user_id', $request->user()->id)->first();
        abort_unless($institution, 403, 'Akun institusi belum memiliki profil institusi.');
        if ($mustBeApproved) abort_unless($institution->verification_status === 'approved', 403, 'Institusi harus diverifikasi admin sebelum membuat kursus.');
        return $institution->id;
    }

    private function ownedCourse(Request $request, int $id): object
    {
        $course = $this->baseQuery()->where('courses.id', $id)
            ->where('courses.institution_id', $this->institutionId($request))->first();
        abort_unless($course, 404, 'Kursus tidak ditemukan.');
        return $course;
    }

    private function courseById(int $id): object
    {
        $course = $this->baseQuery()->where('courses.id', $id)->first();
        abort_unless($course, 404, 'Kursus tidak ditemukan.');
        return $course;
    }

    private function baseQuery()
    {
        return DB::table('courses')
            ->leftJoin('categories', 'categories.id', '=', 'courses.category_id')
            ->join('institutions', 'institutions.id', '=', 'courses.institution_id')
            ->select('courses.*', 'categories.name as category_name', 'institutions.name as institution_name');
    }

    private function storeThumbnail(Request $request): ?string
    {
        return $request->hasFile('thumbnail') ? $request->file('thumbnail')->store('course-thumbnails', 'public') : null;
    }

    private function deleteThumbnail(?string $path): void
    {
        if ($path) Storage::disk('public')->delete($path);
    }
}
