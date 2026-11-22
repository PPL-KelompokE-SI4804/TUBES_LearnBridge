@extends('layout', ['title' => 'Ubah institusi · LearnBridge'])
@section('content')
<p><a href="/admin/institusi/{{ $institution->id }}">← Detail institusi</a></p>
<h1>Ubah institusi</h1>
<div class="card">
    <form method="post" action="/admin/institusi/{{ $institution->id }}/ubah">
        @csrf
        @method('PUT')
        @include('institutions._fields', ['institution' => $institution])
        <button class="btn" type="submit">Simpan perubahan</button>
    </form>
</div>
@endsection
