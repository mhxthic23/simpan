<?php

namespace App\Http\Controllers;

use App\Models\ReminderPreferences;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class ReminderPreferencesController extends Controller
{
    public function edit(Request $request): View
    {
        $preferences = ReminderPreferences::query()->firstOrNew(['user_id' => $request->user()->id]);
        return view('reminder.preferences', compact('preferences'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'channel' => ['required', Rule::in(['in_app', 'email', 'both'])],
            'reminder_before_minutes' => ['nullable', 'array', 'max:5'],
            'reminder_before_minutes.*' => ['required', 'integer', 'min:1', 'max:10080', 'distinct'],
            'quiet_hours_start' => ['nullable', 'date_format:H:i'],
            'quiet_hours_end' => ['nullable', 'date_format:H:i'],
        ], [
            'enabled.required' => 'Pilih apakah pengingat diaktifkan.',
            'channel.in' => 'Saluran pengingat tidak valid.',
            'reminder_before_minutes.*.integer' => 'Waktu pengingat harus berupa menit.',
            'reminder_before_minutes.*.min' => 'Waktu pengingat minimal satu menit.',
            'reminder_before_minutes.*.max' => 'Waktu pengingat maksimal tujuh hari.',
            'quiet_hours_start.date_format' => 'Format jam mulai harus HH:MM.',
            'quiet_hours_end.date_format' => 'Format jam selesai harus HH:MM.',
        ]);
        $data['reminder_before_minutes'] = array_values(array_unique(array_map('intval', $data['reminder_before_minutes'] ?? [])));
        ReminderPreferences::query()->updateOrCreate(['user_id' => $request->user()->id], $data);
        return back()->with('status', 'Preferensi pengingat berhasil disimpan.');
    }
}
