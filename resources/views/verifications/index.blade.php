@extends('layout', ['title' => 'Verifikasi institusi · LearnBridge'])
@section('content')
<p><a href="/dashboard">← Dashboard</a></p>
<h1>Verifikasi institusi</h1>
<p class="muted">Daftar institusi menurut status verifikasinya.</p>
<div class="card">
    <form method="get" action="/admin/verifikasi-institusi">
        <label for="status">Status verifikasi</label>
        <select id="status" name="status">
            <option value="pending" @selected($status === 'pending')>Menunggu</option>
            <option value="approved" @selected($status === 'approved')>Disetujui</option>
            <option value="rejected" @selected($status === 'rejected')>Ditolak</option>
        </select>
        <button class="btn" type="submit">Terapkan</button>
    </form>
    <h2>{{ ['pending' => 'Menunggu verifikasi', 'approved' => 'Institusi disetujui', 'rejected' => 'Institusi ditolak'][$status] }}</h2>
    @if($institutions->isEmpty())
        <p class="muted">Tidak ada institusi dengan status ini.</p>
    @else
        <div style="overflow:auto">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Jenis</th>
                        <th>Situs web</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($institutions as $institution)
                        <tr>
                            <td>{{ $institution->id }}</td>
                            <td>{{ $institution->name }}</td>
                            <td>{{ $institution->type }}</td>
                            <td>{{ $institution->website }}</td>
                            <td>
                                <a class="btn" href="/admin/verifikasi-institusi/{{ $institution->id }}">Detail</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
