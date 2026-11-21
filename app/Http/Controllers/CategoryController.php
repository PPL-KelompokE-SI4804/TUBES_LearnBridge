<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = DB::table('categories')->orderBy('name')->get();

        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        return view('categories.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        DB::table('categories')->insert($data + ['created_at' => now(), 'updated_at' => now()]);

        return redirect('/admin/kategori')->with('success', 'Kategori berhasil disimpan.');
    }

    public function edit(int $id)
    {
        $category = DB::table('categories')->where('id', $id)->first();
        abort_unless($category, 404);

        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, int $id)
    {
        abort_unless(DB::table('categories')->where('id', $id)->exists(), 404);
        $data = $this->validated($request, $id);
        $data['slug'] = $this->uniqueSlug($data['name'], $id);
        DB::table('categories')->where('id', $id)->update($data + ['updated_at' => now()]);

        return redirect('/admin/kategori')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        abort_unless(DB::table('categories')->where('id', $id)->exists(), 404);

        $usage = [];
        $courses = DB::table('courses')->where('category_id', $id)->count();
        $bootcamps = DB::table('bootcamps')->where('category_id', $id)->count();
        $discussions = Schema::hasColumn('discussions', 'category_id')
            ? DB::table('discussions')->where('category_id', $id)->count()
            : 0;

        if ($courses > 0) {
            $usage[] = $courses.' kursus';
        }
        if ($bootcamps > 0) {
            $usage[] = $bootcamps.' bootcamp';
        }
        if ($discussions > 0) {
            $usage[] = $discussions.' diskusi';
        }

        if ($usage !== []) {
            return back()->withErrors([
                'delete' => 'Kategori tidak dapat dihapus karena masih dipakai oleh '.implode(', ', $usage).'.',
            ]);
        }

        DB::table('categories')->where('id', $id)->delete();

        return redirect('/admin/kategori')->with('success', 'Kategori berhasil dihapus.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($ignoreId)],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.unique' => 'Nama kategori sudah digunakan.',
            'name.max' => 'Nama kategori maksimal 255 karakter.',
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status harus aktif atau nonaktif.',
        ]);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'kategori';
        $slug = $base;
        $i = 2;

        while (DB::table('categories')->where('slug', $slug)->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
