<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use App\Models\BlockchainTransaction;
use App\Models\Disputasi;
use App\Models\Penilaian;
use App\Models\Tugas;
use App\Models\VotingDao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotifikasiController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = Notifikasi::query()->where('user_id', $request->user()->id)
            ->latest()->paginate(25);
        $unreadCount = Notifikasi::query()->where('user_id', $request->user()->id)->where('is_read', false)->count();
        return view('notifikasi.index', compact('notifications', 'unreadCount'));
    }

    public function read(Request $request, Notifikasi $notifikasi): RedirectResponse
    {
        $notification = Notifikasi::query()->whereKey($notifikasi->id)->where('user_id', $request->user()->id)->firstOrFail();
        if (! $notification->is_read) $notification->forceFill(['is_read' => true, 'read_at' => now()])->save();
        $url = $notification->reference_type ? $this->referenceUrl($notification) : null;
        return $url ? redirect($url) : back()->with('status', 'Notifikasi ditandai telah dibaca.');
    }

    public function readAll(Request $request): RedirectResponse
    {
        Notifikasi::query()->where('user_id', $request->user()->id)->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
        return back()->with('status', 'Semua notifikasi ditandai telah dibaca.');
    }

    private function referenceUrl(Notifikasi $notification): ?string
    {
        $role = request()->user()?->role;
        $reference = $notification->reference;
        if ($reference instanceof Tugas) $route = $role === 'pengajar' ? 'pengajar.tugas.show' : 'siswa.tugas.show';
        elseif ($reference instanceof Penilaian) $route = $role === 'siswa' ? 'siswa.nilai.show' : null;
        elseif ($reference instanceof Disputasi) $route = $role === 'pengajar' ? 'pengajar.disputasi.show' : ($role === 'siswa' ? 'siswa.nilai.show' : null);
        elseif ($reference instanceof VotingDao) $route = $role === 'pengajar' ? 'pengajar.voting.show' : 'siswa.voting.show';
        elseif ($reference instanceof BlockchainTransaction) $route = 'blockchain.show';
        else $route = null;

        if (! $route || ! \Illuminate\Support\Facades\Route::has($route)) return null;
        if ($reference instanceof Disputasi && $role === 'siswa') return route($route, $reference->penilaian_id);
        return route($route, $reference);
    }
}
