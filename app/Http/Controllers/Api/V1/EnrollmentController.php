<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateEnrollmentRequest;
use App\Models\Bootcamp;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EnrollmentController extends Controller
{
    public function storeCourse(int $courseId): JsonResponse
    {
        return $this->storeForProgram('course', $courseId);
    }

    public function storeBootcamp(int $bootcampId): JsonResponse
    {
        return $this->storeForProgram('bootcamp', $bootcampId);
    }

    public function indexMine(Request $request): JsonResponse
    {
        $validator = Validator::make($request->query(), [
            'status' => 'sometimes|in:active,cancelled,completed',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Filter tidak valid.', 'errors' => $validator->errors()], 422);
        }

        $query = Enrollment::where('user_id', $request->user()->id)->latest('id');
        if ($validator->validated('status')) {
            $query->where('status', $validator->validated('status'));
        }

        return response()->json(['data' => $query->get()->map(fn (Enrollment $enrollment) => $this->serializeEnrollment($enrollment))]);
    }

    public function showMine(Request $request, int $id): JsonResponse
    {
        $enrollment = Enrollment::where('user_id', $request->user()->id)->find($id);
        if (! $enrollment) {
            return response()->json(['message' => 'Pendaftaran tidak ditemukan.'], 404);
        }

        return response()->json(['data' => $this->serializeEnrollment($enrollment)]);
    }

    public function indexForInstitution(Request $request): JsonResponse
    {
        $institution = $this->institutionFor($request);
        if (! $institution) {
            return response()->json(['message' => 'Profil institusi tidak ditemukan.'], 404);
        }

        $enrollments = $this->institutionEnrollments($institution)->latest('id')->get();

        return response()->json(['data' => $enrollments->map(fn (Enrollment $enrollment) => $this->serializeEnrollment($enrollment, true))]);
    }

    public function showForInstitution(Request $request, int $id): JsonResponse
    {
        $institution = $this->institutionFor($request);
        if (! $institution) {
            return response()->json(['message' => 'Profil institusi tidak ditemukan.'], 404);
        }

        $enrollment = $this->institutionEnrollments($institution)->with('user')->find($id);
        if (! $enrollment) {
            return response()->json(['message' => 'Pendaftaran peserta tidak ditemukan.'], 404);
        }

        return response()->json(['data' => $this->serializeEnrollment($enrollment, true)]);
    }

    public function updateMine(UpdateEnrollmentRequest $request, int $id): JsonResponse
    {
        $enrollment = Enrollment::where('user_id', $request->user()->id)->find($id);
        if (! $enrollment) {
            return response()->json(['message' => 'Pendaftaran tidak ditemukan.'], 404);
        }

        if ($enrollment->status !== 'active') {
            return response()->json(['message' => 'Pendaftaran hanya dapat diperbarui saat berstatus aktif.'], 409);
        }

        $data = $request->validated();
        if (! array_key_exists('notes', $data)) {
            return response()->json(['message' => 'Tidak ada atribut pendaftaran yang dapat diperbarui.'], 422);
        }

        $enrollment->update($data);

        return response()->json([
            'message' => 'Info pendaftaran berhasil diperbarui.',
            'data' => $this->serializeEnrollment($enrollment->refresh()),
        ]);
    }

    public function cancelMine(Request $request, int $id): JsonResponse
    {
        $enrollment = Enrollment::where('user_id', $request->user()->id)->find($id);
        if (! $enrollment) {
            return response()->json(['message' => 'Pendaftaran tidak ditemukan.'], 404);
        }

        if (in_array($enrollment->status, ['cancelled', 'completed'], true)) {
            return response()->json(['message' => 'Pendaftaran yang sudah dibatalkan atau selesai tidak dapat dibatalkan kembali.'], 409);
        }

        $enrollment->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Pendaftaran berhasil dibatalkan.',
            'data' => $this->serializeEnrollment($enrollment->refresh()),
        ]);
    }

    private function storeForProgram(string $programType, int $programId): JsonResponse
    {
        $model = $programType === 'course' ? Course::class : Bootcamp::class;
        $program = $model::where('status', 'published')->find($programId);
        if (! $program) {
            return response()->json(['message' => 'Program tidak ditemukan atau belum diterbitkan.'], 404);
        }

        $userId = request()->user()->id;
        $existing = Enrollment::where('user_id', $userId)
            ->where('program_type', $programType)
            ->where('program_id', $programId)
            ->exists();
        if ($existing) {
            return response()->json(['message' => 'Anda sudah pernah mendaftar pada program ini.'], 409);
        }

        try {
            $enrollment = Enrollment::create([
                'user_id' => $userId,
                'program_type' => $programType,
                'program_id' => $programId,
                'status' => 'active',
                'enrolled_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return response()->json(['message' => 'Anda sudah pernah mendaftar pada program ini.'], 409);
        }

        return response()->json([
            'message' => 'Pendaftaran berhasil dibuat.',
            'data' => $this->serializeEnrollment($enrollment),
        ], 201);
    }

    private function institutionFor(Request $request): ?Institution
    {
        return Institution::where('user_id', $request->user()->id)->first();
    }

    private function institutionEnrollments(Institution $institution): Builder
    {
        $courseIds = $institution->courses()->select('id');
        $bootcampIds = $institution->bootcamps()->select('id');

        return Enrollment::with('user')->where(function (Builder $query) use ($courseIds, $bootcampIds) {
            $query->where(function (Builder $query) use ($courseIds) {
                $query->where('program_type', 'course')->whereIn('program_id', $courseIds);
            })->orWhere(function (Builder $query) use ($bootcampIds) {
                $query->where('program_type', 'bootcamp')->whereIn('program_id', $bootcampIds);
            });
        });
    }

    private function serializeEnrollment(Enrollment $enrollment, bool $includeUser = false): array
    {
        $program = match ($enrollment->program_type) {
            'course' => Course::find($enrollment->program_id),
            'bootcamp' => Bootcamp::find($enrollment->program_id),
            default => null,
        };

        $data = [
            'id' => $enrollment->id,
            'user_id' => $enrollment->user_id,
            'program_type' => $enrollment->program_type,
            'program_id' => $enrollment->program_id,
            'status' => $enrollment->status,
            'notes' => $enrollment->notes,
            'enrolled_at' => $enrollment->enrolled_at?->toISOString(),
            'created_at' => $enrollment->created_at?->toISOString(),
            'updated_at' => $enrollment->updated_at?->toISOString(),
            'program' => $program?->only(['id', 'title', 'description', 'status']),
        ];

        if ($includeUser) {
            $user = $enrollment->user;
            $data['participant'] = $user ? ['id' => $user->id, 'name' => $user->name] : null;
        }

        return $data;
    }
}
