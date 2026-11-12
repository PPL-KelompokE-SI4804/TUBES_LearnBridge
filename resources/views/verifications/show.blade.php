@extends('layout', ['title' => 'Detail verifikasi · LearnBridge'])
@section('content')
<p><a href="/admin/verifikasi-institusi">← Daftar pending</a></p>
<h1>{{ $institution->name }}</h1>
<p class="muted">Status saat ini: <span class="pill">{{ ucfirst($institution->verification_status) }}</span></p>
<div class="card">
    <h2>Data institusi</h2>
    <p><strong>Pemilik akun:</strong> {{ $owner?->name ?? '—' }} @if($owner) ({{ $owner->email }}) @endif </p>
    <p><strong>Jenis:</strong> {{ $institution->type ?: '—' }}</p>
    <p><strong>Situs web:</strong> {{ $institution->website ?: '—' }}</p>
    <p><strong>Deskripsi:</strong></p>
    <p>{{ $institution->description ?: '—' }}</p>
</div>
<div class="grid">
    <div class="card">
        <h2>Setujui</h2>
        <form method="post" action="/admin/verifikasi-institusi/{{ $institution->id }}/setujui">
            @csrf
            <label>Catatan (opsional)</label>
            <textarea name="notes" rows="3" maxlength="2000"></textarea>
            <button class="btn" type="submit">Setujui</button>
        </form>
    </div>
    <div class="card">
        <h2>Tolak</h2>
        <form method="post" action="/admin/verifikasi-institusi/{{ $institution->id }}/tolak">
            @csrf
            <label>Alasan penolakan</label>
            <textarea name="notes" rows="3" maxlength="2000" required>{{ old('notes') }}</textarea>
            <button class="btn secondary" type="submit">Tolak</button>
        </form>
    </div>
</div>
<div class="card">
    <h2>Riwayat verifikasi</h2>
    @if($history->isEmpty())
        <p class="muted">Belum ada keputusan.</p>
    @else
        <div style="overflow:auto">
            <table>
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Admin</th>
                        <th>Status</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($history as $row)
                        <tr>
                            <td>{{ $row->verified_at }}</td>
                            <td>{{ $row->admin_name ?: '—' }}</td>
                            <td><span class="pill">{{ $row->status === 'approved' ? 'Disetujui' : 'Ditolak' }}</span></td>
                            <td>{{ $row->notes ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
