@extends('layout', ['title' => 'Institusi · LearnBridge'])
@section('content')
<p><a href="/dashboard">← Dashboard</a></p>
<h1>Institusi</h1>
<p class="muted">Kelola data institusi. Pencarian mengikuti nama, dan daftar dapat disaring menurut status verifikasi.</p>
<p><a class="btn" href="/admin/institusi/tambah">Tambah institusi</a></p>
<div class="card">
    <form method="get" action="/admin/institusi">
        <div class="grid">
            <div>
                <label for="q">Cari nama</label>
                <input id="q" name="q" value="{{ $search }}" maxlength="160">
            </div>
            <div>
                <label for="verification_status">Status verifikasi</label>
                <select id="verification_status" name="verification_status">
                    <option value="" @selected($status === '')>Semua</option>
                    <option value="pending" @selected($status === 'pending')>Menunggu</option>
                    <option value="approved" @selected($status === 'approved')>Disetujui</option>
                    <option value="rejected" @selected($status === 'rejected')>Ditolak</option>
                </select>
            </div>
        </div>
        <button class="btn" type="submit">Terapkan</button>
    </form>
</div>
<div class="card">
    <h2>Daftar institusi</h2>
    @if($institutions->isEmpty())
        <p class="muted">Belum ada institusi yang sesuai.</p>
    @else
        <div style="overflow:auto">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Jenis</th>
                        <th>Verifikasi</th>
                        <th>Keaktifan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($institutions as $institution)
                        <tr>
                            <td>{{ $institution->name }}</td>
                            <td>{{ $institution->type }}</td>
                            <td><span class="pill">{{ ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'][$institution->verification_status] ?? $institution->verification_status }}</span></td>
                            <td><span class="pill">{{ ($institution->is_active ?? true) ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td class="actions">
                                <a class="btn secondary" href="/admin/institusi/{{ $institution->id }}">Detail</a>
                                <a class="btn secondary" href="/admin/institusi/{{ $institution->id }}/ubah">Ubah</a>
                                <form method="post" action="/admin/institusi/{{ $institution->id }}">
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
        @if($institutions->hasPages())
            <p class="muted">
                @if($institutions->onFirstPage())
                    Sebelumnya
                @else
                    <a href="{{ $institutions->previousPageUrl() }}">Sebelumnya</a>
                @endif
                · Halaman {{ $institutions->currentPage() }} dari {{ $institutions->lastPage() }} ·
                @if($institutions->hasMorePages())
                    <a href="{{ $institutions->nextPageUrl() }}">Berikutnya</a>
                @else
                    Berikutnya
                @endif
            </p>
        @endif
    @endif
</div>
@endsection
