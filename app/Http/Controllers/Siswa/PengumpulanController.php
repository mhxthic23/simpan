<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\KelasSiswa;
use App\Models\PengumpulanTugas;
use App\Models\Tugas;
use App\Models\User;
use App\Services\BlockchainTransactionService;
use App\Services\NotificationService;
use App\Services\ReputationLedgerService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PengumpulanController extends Controller
{
    private const DEFAULT_MAX_BYTES = 10 * 1024 * 1024;
    private const DEFAULT_EXTENSIONS = ['pdf', 'doc', 'docx', 'odt', 'txt', 'zip'];

    public function store(Request $request, Tugas $tugas): RedirectResponse
    {
        $tugas = $this->visibleTask($request->user(), $tugas);
        $maxBytes = $tugas->max_file_size ?: self::DEFAULT_MAX_BYTES;
        $maxKilobytes = max(1, (int) ceil($maxBytes / 1024));
        $extensions = $this->allowedExtensions($tugas);
        $extensionList = implode(',', $extensions);

        $request->validate([
            'file' => [
                'required',
                'file',
                File::types($extensions)->max($maxKilobytes),
                'mimes:'.$extensionList,
                'extensions:'.$extensionList,
            ],
        ], [
            'file.required' => 'Berkas pengumpulan wajib dipilih.',
            'file.file' => 'Berkas pengumpulan tidak valid.',
            'file.max' => 'Ukuran berkas melebihi batas yang ditentukan untuk tugas ini.',
            'file.mimes' => 'Isi berkas tidak sesuai dengan format yang diizinkan.',
            'file.extensions' => 'Ekstensi berkas tidak diizinkan untuk tugas ini.',
        ]);

        if (now()->greaterThan($tugas->deadline_at)) {
            return back()->withErrors(['file' => 'Tenggat tugas telah lewat. Pengumpulan dan pengiriman ulang sudah ditutup.']);
        }

        $file = $request->file('file');
        $serverExtension = strtolower($file->guessExtension() ?: 'bin');
        $directory = "assignments/{$tugas->id}/submissions/{$request->user()->id}";
        $storedPath = $file->storeAs($directory, Str::uuid().'.'.$serverExtension, 'local');

        if (! $storedPath || ! Storage::disk('local')->exists($storedPath)) {
            return back()->withErrors(['file' => 'Berkas gagal disimpan. Silakan coba kembali.']);
        }

        try {
            $submission = DB::transaction(function () use ($request, $tugas, $file, $storedPath): PengumpulanTugas {
                $lockedTask = Tugas::query()->whereKey($tugas->id)->lockForUpdate()->firstOrFail();
                $isStillPublished = $lockedTask->status === 'published' && $lockedTask->published_at !== null;
                $isStillEnrolled = KelasSiswa::query()
                    ->where('kelas_id', $lockedTask->kelas_id)
                    ->where('siswa_id', $request->user()->id)
                    ->lockForUpdate()
                    ->exists();

                if (! $isStillPublished || ! $isStillEnrolled) {
                    throw ValidationException::withMessages(['file' => 'Tugas tidak lagi tersedia untuk akun Anda.']);
                }

                if (now()->greaterThan($lockedTask->deadline_at)) {
                    throw ValidationException::withMessages(['file' => 'Tenggat tugas telah lewat. Pengumpulan dan pengiriman ulang sudah ditutup.']);
                }

                $lastAttempt = PengumpulanTugas::query()
                    ->where('tugas_id', $lockedTask->id)
                    ->where('siswa_id', $request->user()->id)
                    ->lockForUpdate()
                    ->max('attempt_no');

                if ((int) $lastAttempt >= 65535) {
                    throw ValidationException::withMessages(['file' => 'Batas jumlah pengiriman ulang untuk tugas ini telah tercapai.']);
                }

                return PengumpulanTugas::create([
                    'tugas_id' => $lockedTask->id,
                    'siswa_id' => $request->user()->id,
                    'attempt_no' => (int) $lastAttempt + 1,
                    'submitted_at' => now(),
                    'status' => 'submitted',
                    'original_filename' => $this->safeOriginalName($file->getClientOriginalName()),
                    'stored_path' => $storedPath,
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            });
        } catch (ValidationException $exception) {
            Storage::disk('local')->delete($storedPath);
            throw $exception;
        } catch (QueryException) {
            Storage::disk('local')->delete($storedPath);
            return back()->withErrors(['file' => 'Data pengumpulan gagal disimpan. Silakan coba kembali.']);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($storedPath);
            throw $exception;
        }

        try {
            $task = Tugas::query()->with('kelas.pengajar')->findOrFail($tugas->id);
            $notifications = app(NotificationService::class);
            $notifications->sendOnce($request->user(), 'Pengumpulan tersimpan', 'Attempt '.$submission->attempt_no.' untuk tugas "'.$task->judul.'" berhasil dikumpulkan.', 'submission_received', 'submission:'.$submission->id.':student', $submission);
            if ($task->kelas?->pengajar) $notifications->sendOnce($task->kelas->pengajar, 'Pengumpulan tugas baru', 'Ada pengumpulan baru untuk tugas "'.$task->judul.'".', 'submission_received', 'submission:'.$submission->id.':teacher', $submission);
            app(BlockchainTransactionService::class)->record('assignment_submitted', $submission, [
                'event' => 'assignment_submitted', 'attempt_no' => $submission->attempt_no,
                'submitted_at' => $submission->submitted_at?->toISOString(), 'file_hash' => $submission->file_hash,
            ], 'submission:'.$submission->id);
            $tokens = (int) config('services.simpan_reputation.submission_token', 0);
            if ($tokens !== 0) app(ReputationLedgerService::class)->record($request->user(), 'assignment_submitted', $tokens, 'submission:'.$submission->id, 'Pengumpulan tugas', $submission);
        } catch (\Throwable $exception) {
            report($exception);
        }

        return redirect()->route('siswa.tugas.show', $tugas)->with('status', 'Berkas berhasil disimpan dan tugas berhasil dikumpulkan.');
    }

    public function download(Request $request, PengumpulanTugas $submission): StreamedResponse
    {
        abort_unless($request->user()->role === 'siswa' && $submission->siswa_id === $request->user()->id, 404);
        abort_unless($submission->stored_path && Storage::disk('local')->exists($submission->stored_path), 404);

        return Storage::disk('local')->download(
            $submission->stored_path,
            $this->safeOriginalName($submission->original_filename)
        );
    }

    private function visibleTask(User $user, Tugas $tugas): Tugas
    {
        return Tugas::query()
            ->whereKey($tugas->id)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->whereHas('kelas.siswa', fn (Builder $query) => $query->where('users.id', $user->id))
            ->firstOrFail();
    }

    /** @return array<int, string> */
    private function allowedExtensions(Tugas $tugas): array
    {
        $extensions = $tugas->allowed_file_types ?: self::DEFAULT_EXTENSIONS;

        return array_values(array_intersect(
            self::DEFAULT_EXTENSIONS,
            array_map(fn ($extension) => strtolower((string) $extension), $extensions)
        ));
    }

    private function safeOriginalName(?string $name): string
    {
        $name = str_replace('\\', '/', $name ?? 'pengumpulan');
        $name = basename($name);
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?: 'pengumpulan';

        return mb_substr($name, 0, 255);
    }
}
