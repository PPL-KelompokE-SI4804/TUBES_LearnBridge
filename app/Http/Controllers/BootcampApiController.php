<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BootcampApiController extends Controller
{
    public function catalog(): JsonResponse
    {
        return response()->json(['data' => $this->baseQuery()
            ->where('bootcamps.status', 'published')->latest('bootcamps.id')->get()]);
    }

    public function publicShow(int $id): JsonResponse
    {
        $bootcamp = $this->baseQuery()->where('bootcamps.id', $id)->where('bootcamps.status', 'published')->first();
        abort_unless($bootcamp, 404, 'Bootcamp tidak ditemukan.');
        return response()->json(['data' => $bootcamp]);
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->baseQuery()
            ->where('bootcamps.institution_id', $this->institutionId($request))
            ->latest('bootcamps.id')->get()]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return response()->json(['data' => $this->ownedBootcamp($request, $id)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->bootcampRules());
        $data['institution_id'] = $this->institutionId($request, true);
        $data['status'] = 'draft';
        $data['created_at'] = now();
        $data['updated_at'] = now();
        $id = DB::table('bootcamps')->insertGetId($data);

        return response()->json(['message' => 'Bootcamp berhasil dibuat sebagai draft.', 'data' => $this->bootcampById($id)], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->ownedBootcamp($request, $id);
        $data = $request->validate($this->bootcampRules(true));
        DB::table('bootcamps')->where('id', $id)->update($data + ['updated_at' => now()]);
        return response()->json(['message' => 'Informasi bootcamp berhasil diperbarui.', 'data' => $this->bootcampById($id)]);
    }

    public function publish(Request $request, int $id): JsonResponse
    {
        $this->ownedBootcamp($request, $id);
        DB::table('bootcamps')->where('id', $id)->update(['status' => 'published', 'updated_at' => now()]);
        return response()->json(['message' => 'Bootcamp berhasil dipublikasikan.', 'data' => $this->bootcampById($id)]);
    }

    public function archive(Request $request, int $id): JsonResponse
    {
        $this->ownedBootcamp($request, $id);
        DB::table('bootcamps')->where('id', $id)->update(['status' => 'archived', 'updated_at' => now()]);
        return response()->json(['message' => 'Bootcamp berhasil diarsipkan.', 'data' => $this->bootcampById($id)]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->ownedBootcamp($request, $id);
        abort_if(DB::table('enrollments')->where('program_type', 'bootcamp')->where('program_id', $id)->exists(), 422, 'Bootcamp tidak dapat dihapus karena memiliki peserta.');
        DB::table('bootcamps')->where('id', $id)->delete();
        return response()->json(['message' => 'Bootcamp berhasil dihapus.']);
    }

    public function adminIndex(): JsonResponse
    {
        return response()->json(['data' => $this->baseQuery()->latest('bootcamps.id')->get()]);
    }

    public function adminShow(int $id): JsonResponse
    {
        return response()->json(['data' => $this->bootcampById($id)]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $this->bootcampById($id);
        $data = $request->validate(['status' => ['required', 'in:draft,published,archived,hidden']]);
        DB::table('bootcamps')->where('id', $id)->update($data + ['updated_at' => now()]);
        return response()->json(['message' => 'Status administratif bootcamp berhasil diperbarui.', 'data' => $this->bootcampById($id)]);
    }

    private function bootcampRules(bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';
        return [
            'title' => [$required, 'string', 'max:180'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'schedule_at' => [$required, 'date'],
            'duration_minutes' => [$required, 'integer', 'min:1', 'max:10080'],
            'capacity' => [$required, 'integer', 'min:1', 'max:100000'],
            'passing_score' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }

    private function institutionId(Request $request, bool $mustBeApproved = false): int
    {
        $institution = DB::table('institutions')->where('user_id', $request->user()->id)->first();
        abort_unless($institution, 403, 'Akun institusi belum memiliki profil institusi.');
        if ($mustBeApproved) abort_unless($institution->verification_status === 'approved', 403, 'Institusi harus diverifikasi admin sebelum membuat bootcamp.');
        return $institution->id;
    }

    private function ownedBootcamp(Request $request, int $id): object
    {
        $bootcamp = $this->baseQuery()->where('bootcamps.id', $id)
            ->where('bootcamps.institution_id', $this->institutionId($request))->first();
        abort_unless($bootcamp, 404, 'Bootcamp tidak ditemukan.');
        return $bootcamp;
    }

    private function bootcampById(int $id): object
    {
        $bootcamp = $this->baseQuery()->where('bootcamps.id', $id)->first();
        abort_unless($bootcamp, 404, 'Bootcamp tidak ditemukan.');
        return $bootcamp;
    }

    private function baseQuery()
    {
        return DB::table('bootcamps')
            ->leftJoin('categories', 'categories.id', '=', 'bootcamps.category_id')
            ->join('institutions', 'institutions.id', '=', 'bootcamps.institution_id')
            ->select('bootcamps.*', 'categories.name as category_name', 'institutions.name as institution_name');
    }
}
