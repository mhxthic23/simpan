@extends('layouts.app')
@section('title', 'Preferensi Pengingat · SIMPAN')
@section('content')
<h1 class="h2 mb-1">Preferensi pengingat</h1><p class="text-secondary mb-4">Pengingat tenggat dibuat untuk tugas yang belum dikumpulkan.</p>
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('reminder.preferences.update') }}" class="card border-0 shadow-sm"><div class="card-body p-4">@csrf @method('PUT')
<div class="form-check form-switch mb-3"><input type="hidden" name="enabled" value="0"><input class="form-check-input" type="checkbox" role="switch" name="enabled" value="1" id="enabled" @checked(old('enabled', $preferences->enabled ?? true))><label class="form-check-label" for="enabled">Aktifkan pengingat</label></div>
<div class="mb-3"><label for="channel" class="form-label">Saluran</label><select id="channel" name="channel" class="form-select"><option value="in_app" @selected(old('channel', $preferences->channel ?? 'in_app') === 'in_app')>Dalam aplikasi</option><option value="email" @selected(old('channel', $preferences->channel) === 'email')>Email</option><option value="both" @selected(old('channel', $preferences->channel) === 'both')>Aplikasi dan email</option></select></div>
<fieldset class="mb-3"><legend class="form-label">Waktu sebelum tenggat (menit)</legend>@foreach([1440 => '24 jam', 60 => '1 jam', 15 => '15 menit'] as $minutes => $label)<div class="form-check"><input class="form-check-input" type="checkbox" name="reminder_before_minutes[]" value="{{ $minutes }}" id="minutes-{{ $minutes }}" @checked(in_array($minutes, old('reminder_before_minutes', $preferences->reminder_before_minutes ?? [1440, 60])))><label class="form-check-label" for="minutes-{{ $minutes }}">{{ $label }}</label></div>@endforeach</fieldset>
<div class="row g-3 mb-3"><div class="col-md-6"><label class="form-label" for="quiet-start">Mulai jam tenang</label><input class="form-control" type="time" name="quiet_hours_start" id="quiet-start" value="{{ old('quiet_hours_start', $preferences->quiet_hours_start) }}"></div><div class="col-md-6"><label class="form-label" for="quiet-end">Selesai jam tenang</label><input class="form-control" type="time" name="quiet_hours_end" id="quiet-end" value="{{ old('quiet_hours_end', $preferences->quiet_hours_end) }}"></div></div>
<button class="btn btn-primary">Simpan preferensi</button></div></form>
@endsection
