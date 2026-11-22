<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MaterialApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $institutionId = $this->institutionId($request);
        $courseIds = DB::table('courses')->where('institution_id', $institutionId)->pluck('id');
        $bootcampIds = DB::table('bootcamps')->where('institution_id', $institutionId)->pluck('id');

        $materials = $this->baseQuery()
            ->where(function ($query) use ($courseIds, $bootcampIds) {
                $query->where(function ($part) use ($courseIds) {
                    $part->where('materials.program_type', 'course')->whereIn('materials.program_id', $courseIds);
                })->orWhere(function ($part) use ($bootcampIds) {
                    $part->where('materials.program_type', 'bootcamp')->whereIn('materials.program_id', $bootcampIds);
                });
            })
            ->when($request->filled('program_type'), fn ($query) => $query->where('materials.program_type', $request->input('program_type')))
            ->when($request->filled('program_id'), fn ($query) => $query->where('materials.program_id', (int) $request->input('program_id')))
            ->orderBy('materials.program_type')->orderBy('materials.position')->latest('materials.id')
            ->get()->map(fn ($material) => $this->withFileUrl($material));

        return response()->json(['data' => $materials]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return response()->json(['data' => $this->withFileUrl($this->ownedMaterial($request, $id))]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->materialRules());
        $this->institutionId($request, true);
        $this->assertOwnedProgram($request, $data['program_type'], (int) $data['program_id']);
        $this->storeAttachment($request, $data);
        $data['created_at'] = now();
        $data['updated_at'] = now();
        $id = DB::table('materials')->insertGetId($data);

        return response()->json(['message' => 'Materi pembelajaran berhasil disimpan.', 'data' => $this->withFileUrl($this->materialById($id))], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $material = $this->ownedMaterial($request, $id);
        if ($request->anyFilled(['program_type', 'program_id'])) {
            $this->assertOwnedProgram($request, $request->input('program_type', $material->program_type), (int) $request->input('program_id', $material->program_id));
        }
        $data = $request->validate($this->materialRules(true));
        $this->storeAttachment($request, $data, $material);
        DB::table('materials')->where('id', $id)->update($data + ['updated_at' => now()]);
        return response()->json(['message' => 'Materi pembelajaran berhasil diperbarui.', 'data' => $this->withFileUrl($this->materialById($id))]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $material = $this->ownedMaterial($request, $id);
        $this->deleteAttachment($material);
        DB::table('materials')->where('id', $id)->delete();
        return response()->json(['message' => 'Materi pembelajaran berhasil dihapus.']);
    }

    public function adminIndex(): JsonResponse
    {
        return response()->json(['data' => $this->baseQuery()
            ->orderBy('materials.program_type')->orderBy('materials.position')->latest('materials.id')
            ->get()->map(fn ($material) => $this->withFileUrl($material))]);
    }

    public function adminShow(int $id): JsonResponse
    {
        return response()->json(['data' => $this->withFileUrl($this->materialById($id))]);
    }

    public function adminDestroy(int $id): JsonResponse
    {
        $material = $this->materialById($id);
        $this->deleteAttachment($material);
        DB::table('materials')->where('id', $id)->delete();
        return response()->json(['message' => 'Materi pembelajaran berhasil dihapus.']);
    }

    private function materialRules(bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';
        return [
            'title' => [$required, 'string', 'max:180'],
            'body' => [$required, 'string'],
            'program_type' => [$required, 'in:course,bootcamp'],
            'program_id' => [$required, 'integer'],
            'position' => ['nullable', 'integer', 'min:0'],
            'source_url' => ['nullable', 'url', 'max:2048'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip,mp4', 'max:10240'],
        ];
    }

    private function institutionId(Request $request, bool $mustBeApproved = false): int
    {
        $institution = DB::table('institutions')->where('user_id', $request->user()->id)->first();
        abort_unless($institution, 403, 'Akun institusi belum memiliki profil institusi.');
        if ($mustBeApproved) abort_unless($institution->verification_status === 'approved', 403, 'Institusi harus diverifikasi admin sebelum membuat materi.');
        return $institution->id;
    }

    private function programTable(string $programType): string
    {
        return $programType === 'bootcamp' ? 'bootcamps' : 'courses';
    }

    private function assertOwnedProgram(Request $request, string $programType, int $programId): void
    {
        $program = DB::table($this->programTable($programType))->where('id', $programId)->first();
        abort_unless($program, 404, 'Program pembelajaran tidak ditemukan.');
        abort_unless($program->institution_id === $this->institutionId($request), 403, 'Materi hanya dapat dikelola pada program milik institusi Anda.');
    }

    private function ownedMaterial(Request $request, int $id): object
    {
        $material = $this->baseQuery()->where('materials.id', $id)->first();
        abort_unless($material, 404, 'Materi tidak ditemukan.');
        $program = DB::table($this->programTable($material->program_type))->where('id', $material->program_id)->first();
        abort_unless($program && $program->institution_id === $this->institutionId($request), 404, 'Materi tidak ditemukan.');
        return $material;
    }

    private function materialById(int $id): object
    {
        $material = $this->baseQuery()->where('materials.id', $id)->first();
        abort_unless($material, 404, 'Materi tidak ditemukan.');
        return $material;
    }

    private function baseQuery()
    {
        return DB::table('materials')
            ->leftJoin('courses', fn ($join) => $join->on('courses.id', '=', 'materials.program_id')->where('materials.program_type', 'course'))
            ->leftJoin('bootcamps', fn ($join) => $join->on('bootcamps.id', '=', 'materials.program_id')->where('materials.program_type', 'bootcamp'))
            ->select('materials.*', DB::raw('COALESCE(courses.title, bootcamps.title) as program_title'));
    }

    private function withFileUrl(object $material): object
    {
        $material->file_url = ($material->file_path ?? null) ? route('material.file', $material->id) : null;
        return $material;
    }

    private function storeAttachment(Request $request, array &$data, ?object $oldMaterial = null): void
    {
        unset($data['attachment']);
        if (!$request->hasFile('attachment')) return;
        $file = $request->file('attachment');
        $data['file_path'] = $file->store('materials', 'local');
        $data['file_name'] = $file->getClientOriginalName();
        if ($oldMaterial) $this->deleteAttachment($oldMaterial);
    }

    private function deleteAttachment(object $material): void
    {
        if (!empty($material->file_path)) Storage::disk('local')->delete($material->file_path);
    }
}
