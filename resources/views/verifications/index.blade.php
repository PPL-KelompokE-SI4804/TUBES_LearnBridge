@extends('layout', ['title' => 'Verifikasi institusi · LearnBridge'])
@section('content')
<p><a href="/dashboard">← Dashboard</a></p>
<h1>Verifikasi institusi</h1>
<p class="muted">Daftar institusi yang masih menunggu keputusan admin.</p>
<div class="card">
    <h2>Menunggu verifikasi</h2>
    @if($institutions->isEmpty())
        <p class="muted">Tidak ada institusi berstatus pending.</p>
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
