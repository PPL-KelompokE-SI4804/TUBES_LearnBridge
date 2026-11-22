<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class InstitutionController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('verification_status', '');
        if (! in_array($status, ['', 'pending', 'approved', 'rejected'], true)) {
            $status = '';
        }

        $institutions = DB::table('institutions')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$this->escapeLike($search).'%'))
            ->when(in_array($status, ['pending', 'approved', 'rejected'], true), fn ($query) => $query->where('verification_status', $status))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('institutions.index', compact('institutions', 'search', 'status'));
    }

    public function show(int $id)
    {
        $institution = $this->find($id);
        $owner = DB::table('users')->where('id', $institution->user_id)->first();
        $courseCount = DB::table('courses')->where('institution_id', $id)->count();
        $bootcampCount = DB::table('bootcamps')->where('institution_id', $id)->count();

        return view('institutions.show', compact('institution', 'owner', 'courseCount', 'bootcampCount'));
    }

    public function create()
    {
        $owners = $this->availableOwners();

        return view('institutions.create', compact('owners'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $ownerMode = $request->validate(['owner_mode' => ['required', Rule::in(['existing', 'new'])]])['owner_mode'];
        $ownerData = [];

        if ($ownerMode === 'existing') {
            $ownerId = $request->validate([
                'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'institusi'), Rule::unique('institutions', 'user_id')],
            ], [
                'user_id.required' => 'Pemilik akun wajib dipilih.',
                'user_id.exists' => 'Pemilik harus akun dengan peran institusi.',
                'user_id.unique' => 'Akun tersebut sudah memiliki institusi.',
            ])['user_id'];
        } else {
            $ownerData = $request->validate([
                'owner_name' => ['required', 'string', 'max:120'],
                'owner_email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'owner_password' => ['required', 'string', 'min:8'],
            ], [
                'owner_name.required' => 'Nama pemilik akun wajib diisi.',
                'owner_email.required' => 'Email pemilik akun wajib diisi.',
                'owner_email.unique' => 'Email sudah digunakan.',
                'owner_password.required' => 'Kata sandi pemilik akun wajib diisi.',
                'owner_password.min' => 'Kata sandi minimal 8 karakter.',
            ]);
            $ownerId = null;
        }

        DB::transaction(function () use ($data, $ownerMode, $ownerId, $ownerData) {
            if ($ownerMode === 'new') {
                $ownerId = User::create([
                    'name' => $ownerData['owner_name'],
                    'email' => strtolower($ownerData['owner_email']),
                    'password' => Hash::make($ownerData['owner_password']),
                    'role' => 'institusi',
                ])->id;
            }

            DB::table('institutions')->insert($data + [
                'user_id' => $ownerId,
                'verification_status' => 'pending',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return redirect('/admin/institusi')->with('success', 'Institusi berhasil disimpan.');
    }

    public function edit(int $id)
    {
        $institution = $this->find($id);

        return view('institutions.edit', compact('institution'));
    }

    public function update(Request $request, int $id)
    {
        $this->find($id);
        DB::table('institutions')->where('id', $id)->update($this->validated($request) + ['updated_at' => now()]);

        return redirect('/admin/institusi/'.$id)->with('success', 'Institusi berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $this->find($id);

        $hasPrograms = DB::table('courses')->where('institution_id', $id)->exists()
            || DB::table('bootcamps')->where('institution_id', $id)->exists();

        if ($hasPrograms) {
            DB::table('institutions')->where('id', $id)->update(['is_active' => false, 'updated_at' => now()]);

            return redirect('/admin/institusi')->with('success', 'Institusi dinonaktifkan karena masih memiliki kursus atau bootcamp.');
        }

        DB::transaction(function () use ($id) {
            DB::table('institution_verifications')->where('institution_id', $id)->delete();
            DB::table('institutions')->where('id', $id)->delete();
        });

        return redirect('/admin/institusi')->with('success', 'Institusi berhasil dihapus.');
    }

    private function find(int $id): object
    {
        $institution = DB::table('institutions')->where('id', $id)->first();
        abort_unless($institution, 404);

        return $institution;
    }

    private function availableOwners()
    {
        $usedIds = DB::table('institutions')->pluck('user_id');

        return DB::table('users')
            ->where('role', 'institusi')
            ->whereNotIn('id', $usedIds)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'type' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ], [
            'name.required' => 'Nama institusi wajib diisi.',
            'name.max' => 'Nama institusi maksimal 160 karakter.',
            'type.max' => 'Jenis institusi maksimal 100 karakter.',
            'address.max' => 'Alamat maksimal 255 karakter.',
            'contact.max' => 'Kontak maksimal 255 karakter.',
            'website.url' => 'Situs web harus berupa URL yang valid.',
            'website.max' => 'Situs web maksimal 255 karakter.',
            'description.max' => 'Deskripsi maksimal 5000 karakter.',
        ]);
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
};
