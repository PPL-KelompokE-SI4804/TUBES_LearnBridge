@extends('layout', ['title' => 'Tambah institusi · LearnBridge'])
@section('content')
<p><a href="/admin/institusi">← Daftar institusi</a></p>
<h1>Tambah institusi</h1>
@php($ownerMode = old('owner_mode', $owners->isEmpty() ? 'new' : 'existing'))
<div class="card">
    <form method="post" action="/admin/institusi">
        @csrf
        <label for="owner_mode">Akun pemilik</label>
        <select id="owner_mode" name="owner_mode" onchange="toggleOwnerFields()" required>
            <option value="existing" @selected($ownerMode === 'existing') @disabled($owners->isEmpty())>Gunakan akun institusi yang sudah ada</option>
            <option value="new" @selected($ownerMode === 'new')>Buat akun institusi baru</option>
        </select>
        <div id="existing-owner-fields" @if($ownerMode !== 'existing') hidden @endif>
            <label for="user_id">Pemilik akun</label>
            <select id="user_id" name="user_id" @if($ownerMode !== 'existing') disabled @endif>
                <option value="">— Pilih akun institusi —</option>
                @foreach($owners as $owner)
                    <option value="{{ $owner->id }}" @selected((string) old('user_id') === (string) $owner->id)>{{ $owner->name }} · {{ $owner->email }}</option>
                @endforeach
            </select>
        </div>
        <div id="new-owner-fields" @if($ownerMode !== 'new') hidden @endif>
            <label for="owner_name">Nama pemilik akun</label>
            <input id="owner_name" name="owner_name" value="{{ old('owner_name') }}" maxlength="120" @if($ownerMode !== 'new') disabled @endif>
            <label for="owner_email">Email pemilik akun</label>
            <input id="owner_email" name="owner_email" type="email" value="{{ old('owner_email') }}" maxlength="255" @if($ownerMode !== 'new') disabled @endif>
            <label for="owner_password">Kata sandi awal (minimal 8 karakter)</label>
            <input id="owner_password" name="owner_password" type="password" minlength="8" @if($ownerMode !== 'new') disabled @endif>
        </div>
        @include('institutions._fields')
        <p class="muted">Institusi baru berstatus menunggu verifikasi dan aktif.</p>
        <button class="btn" type="submit">Simpan</button>
    </form>
</div>
<script>
    function toggleOwnerFields() {
        const mode = document.getElementById('owner_mode').value;
        const existingFields = document.getElementById('existing-owner-fields');
        const newFields = document.getElementById('new-owner-fields');
        existingFields.hidden = mode !== 'existing';
        newFields.hidden = mode !== 'new';
        document.getElementById('user_id').disabled = mode !== 'existing';
        for (const field of newFields.querySelectorAll('input')) field.disabled = mode !== 'new';
    }
</script>
@endsection
