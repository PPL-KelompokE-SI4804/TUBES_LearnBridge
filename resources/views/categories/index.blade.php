@extends('layout', ['title' => 'Kategori · LearnBridge'])
@section('content')
<p><a href="/dashboard">← Dashboard</a></p>
<h1>Kategori</h1>
<p class="muted">Susun kategori pembelajaran. Slug dibuat otomatis dari nama.</p>
<p><a class="btn" href="/admin/kategori/tambah">Tambah kategori</a></p>
<div class="card">
    <h2>Daftar kategori</h2>
    @if($categories->isEmpty())
        <p class="muted">Belum ada kategori.</p>
    @else
        <div style="overflow:auto">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Slug</th>
                        <th>Status</th>
                        <th>Deskripsi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categories as $category)
                        <tr>
                            <td>{{ $category->name }}</td>
                            <td>{{ $category->slug }}</td>
                            <td><span class="pill">{{ $category->status === 'active' ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td>{{ $category->description }}</td>
                            <td class="actions">
                                <a class="btn secondary" href="/admin/kategori/{{ $category->id }}/ubah">Ubah</a>
                                <form method="post" action="/admin/kategori/{{ $category->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn secondary" type="submit">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
