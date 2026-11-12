<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReminderController extends Controller
{
    public function index(Request $request): View
    {
        $reminders = Reminder::query()->where('user_id', $request->user()->id)
            ->with('tugas')->latest('scheduled_at')->paginate(25);
        return view('reminder.index', compact('reminders'));
    }

    public function cancel(Request $request, Reminder $reminder): RedirectResponse
    {
        $reminder = Reminder::query()->whereKey($reminder->id)->where('user_id', $request->user()->id)->firstOrFail();
        abort_unless($reminder->status === 'pending', 409);
        $reminder->forceFill(['status' => 'cancelled'])->save();
        return back()->with('status', 'Pengingat dibatalkan.');
    }
}
