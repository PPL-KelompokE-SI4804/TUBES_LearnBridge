@extends('layout', ['title' => 'Ubah kategori · LearnBridge'])
@section('content')
<p><a href="/admin/kategori">← Daftar kategori</a></p>
<h1>Ubah kategori</h1>
<div class="card">
    <form method="post" action="/admin/kategori/{{ $category->id }}">
        @csrf
        @method('PUT')
        <label for="name">Nama kategori</label>
        <input id="name" name="name" value="{{ old('name', $category->name) }}" maxlength="255" required>
        <label for="description">Deskripsi</label>
        <textarea id="description" name="description" rows="4">{{ old('description', $category->description) }}</textarea>
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="active" @selected(old('status', $category->status) === 'active')>Aktif</option>
            <option value="inactive" @selected(old('status', $category->status) === 'inactive')>Nonaktif</option>
        </select>
        <p class="muted">Slug saat ini: {{ $category->slug }}. Slug diperbarui otomatis dari nama saat disimpan.</p>
        <button class="btn" type="submit">Simpan perubahan</button>
    </form>
</div>
@endsection
