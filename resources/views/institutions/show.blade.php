@extends('layout', ['title' => $institution->name.' · LearnBridge'])
@section('content')
<p><a href="/admin/institusi">← Daftar institusi</a></p>
<h1>{{ $institution->name }}</h1>
<p>
    <span class="pill">{{ ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'][$institution->verification_status] ?? $institution->verification_status }}</span>
    <span class="pill">{{ $institution->is_active ? 'Aktif' : 'Nonaktif' }}</span>
</p>
<div class="card">
    <p><strong>Jenis:</strong> {{ $institution->type ?: '—' }}</p>
    <p><strong>Alamat:</strong> {{ $institution->address ?: '—' }}</p>
    <p><strong>Kontak:</strong> {{ $institution->contact ?: '—' }}</p>
    <p><strong>Situs web:</strong> @if($institution->website)<a href="{{ $institution->website }}">{{ $institution->website }}</a>@else — @endif</p>
    <p><strong>Deskripsi:</strong> {{ $institution->description ?: '—' }}</p>
    <p><strong>Pemilik:</strong> {{ $owner->name ?? '—' }}@if($owner) · {{ $owner->email }}@endif</p>
    <p><strong>Kursus:</strong> {{ $courseCount }} · <strong>Bootcamp:</strong> {{ $bootcampCount }}</p>
    <p class="actions">
        <a class="btn" href="/admin/institusi/{{ $institution->id }}/ubah">Ubah</a>
        <a class="btn secondary" href="/admin/verifikasi-institusi/{{ $institution->id }}">Verifikasi</a>
    </p>
</div>
@endsection
