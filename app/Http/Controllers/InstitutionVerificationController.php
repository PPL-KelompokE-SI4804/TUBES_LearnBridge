<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InstitutionVerificationController extends Controller
{
    public function index()
    {
        $institutions = DB::table('institutions')
            ->where('verification_status', 'pending')
            ->orderBy('id')
            ->get();

        return view('verifications.index', compact('institutions'));
    }

    public function show(int $id)
    {
        $institution = DB::table('institutions')->where('id', $id)->first();
        abort_unless($institution, 404);

        $owner = DB::table('users')->where('id', $institution->user_id)->first();
        $history = DB::table('institution_verifications')
            ->leftJoin('users', 'users.id', '=', 'institution_verifications.admin_id')
            ->where('institution_verifications.institution_id', $id)
            ->orderByDesc('institution_verifications.id')
            ->get([
                'institution_verifications.id',
                'institution_verifications.status',
                'institution_verifications.notes',
                'institution_verifications.verified_at',
                'users.name as admin_name',
            ]);

        return view('verifications.show', compact('institution', 'owner', 'history'));
    }

    public function approve(Request $request, int $id)
    {
        $data = $request->validate([
            'notes' => 'nullable|string|max:2000',
        ]);

        $this->record($id, 'approved', $data['notes'] ?? null);

        return redirect('/admin/verifikasi-institusi')->with('success', 'Institusi disetujui.');
    }

    public function reject(Request $request, int $id)
    {
        $data = $request->validate([
            'notes' => 'required|string|max:2000',
        ]);

        $this->record($id, 'rejected', $data['notes']);

        return redirect('/admin/verifikasi-institusi')->with('success', 'Institusi ditolak.');
    }

    private function record(int $id, string $status, ?string $notes): void
    {
        abort_unless(DB::table('institutions')->where('id', $id)->exists(), 404);

        DB::transaction(function () use ($id, $status, $notes) {
            DB::table('institutions')->where('id', $id)->update([
                'verification_status' => $status,
                'verification_note' => $notes,
                'updated_at' => now(),
            ]);
            DB::table('institution_verifications')->insert([
                'institution_id' => $id,
                'admin_id' => auth()->id(),
                'status' => $status,
                'notes' => $notes,
                'verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }
}
