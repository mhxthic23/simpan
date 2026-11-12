<?php

namespace App\Http\Controllers;

use App\Models\Disputasi;
use App\Models\PengumpulanTugas;
use App\Models\Penilaian;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): RedirectResponse
    {
        $route = match (Auth::user()?->role) {
            'siswa' => 'siswa.dashboard',
            'pengajar' => 'pengajar.dashboard',
            'admin' => 'admin.dashboard',
            default => null,
        };

        abort_if($route === null, 403);

        return redirect()->route($route);
    }

    public function siswa(Request $request): View
    {
        $nilaiTerbaru = Penilaian::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->whereHas('pengumpulanTugas', fn (Builder $query) => $query->where('siswa_id', $request->user()->id))
            ->with('pengumpulanTugas.tugas')
            ->latest('published_at')
            ->limit(5)
            ->get();
        $disputasiTerbaru = Disputasi::query()
            ->where('siswa_id', $request->user()->id)
            ->with('penilaian.pengumpulanTugas.tugas')
            ->latest('submitted_at')
            ->limit(5)
            ->get();

        return view('dashboards.siswa', ['user' => $request->user(), 'nilaiTerbaru' => $nilaiTerbaru, 'disputasiTerbaru' => $disputasiTerbaru]);
    }

    public function pengajar(Request $request): View
    {
        $submissionQuery = PengumpulanTugas::query()
            ->whereHas('tugas.kelas', fn (Builder $query) => $query->where('pengajar_id', $request->user()->id))
            ->whereDoesntHave('penilaian', fn (Builder $query) => $query->whereNotNull('published_at'));
        $menungguPenilaian = (clone $submissionQuery)->count();
        $pengumpulanTerbaru = (clone $submissionQuery)
            ->with(['siswa', 'tugas'])
            ->latest('submitted_at')
            ->limit(5)
            ->get();
        $disputasiQuery = Disputasi::query()
            ->whereIn('status', ['pending', 'reviewing'])
            ->whereHas('penilaian.pengumpulanTugas.tugas.kelas', fn (Builder $query) => $query->where('pengajar_id', $request->user()->id));
        $disputasiCount = (clone $disputasiQuery)->count();
        $disputasiPerluDitinjau = $disputasiQuery
            ->with(['siswa', 'penilaian.pengumpulanTugas.tugas'])
            ->latest('submitted_at')
            ->limit(5)
            ->get();

        return view('dashboards.pengajar', [
            'user' => $request->user(),
            'menungguPenilaian' => $menungguPenilaian,
            'pengumpulanTerbaru' => $pengumpulanTerbaru,
            'disputasiPerluDitinjau' => $disputasiPerluDitinjau,
            'disputasiCount' => $disputasiCount,
        ]);
    }

    public function admin(Request $request): View
    {
        return view('dashboards.admin', ['user' => $request->user()]);
    }
}
