@extends('layout', ['title' => 'Tambah kategori · LearnBridge'])
@section('content')
<p><a href="/admin/kategori">← Daftar kategori</a></p>
<h1>Tambah kategori</h1>
<div class="card">
    <form method="post" action="/admin/kategori">
        @csrf
        <label for="name">Nama kategori</label>
        <input id="name" name="name" value="{{ old('name') }}" maxlength="255" required>
        <label for="description">Deskripsi</label>
        <textarea id="description" name="description" rows="4">{{ old('description') }}</textarea>
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="active" @selected(old('status', 'active') === 'active')>Aktif</option>
            <option value="inactive" @selected(old('status') === 'inactive')>Nonaktif</option>
        </select>
        <button class="btn" type="submit">Simpan</button>
    </form>
</div>
@endsection
