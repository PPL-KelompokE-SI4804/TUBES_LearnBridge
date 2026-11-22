<label for="name">Nama institusi</label>
<input id="name" name="name" value="{{ old('name', $institution->name ?? '') }}" maxlength="160" required>
<label for="type">Jenis</label>
<input id="type" name="type" value="{{ old('type', $institution->type ?? '') }}" maxlength="100">
<label for="address">Alamat</label>
<input id="address" name="address" value="{{ old('address', $institution->address ?? '') }}" maxlength="255">
<label for="contact">Kontak</label>
<input id="contact" name="contact" value="{{ old('contact', $institution->contact ?? '') }}" maxlength="255">
<label for="website">Situs web</label>
<input id="website" name="website" type="url" value="{{ old('website', $institution->website ?? '') }}" maxlength="255">
<label for="description">Deskripsi</label>
<textarea id="description" name="description" rows="4" maxlength="5000">{{ old('description', $institution->description ?? '') }}</textarea>
