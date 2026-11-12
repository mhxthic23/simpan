<nav class="nav nav-pills flex-wrap gap-2 mb-4" aria-label="Navigasi manajemen akademik">
    <a class="nav-link @if(request()->routeIs('admin.fakultas.*')) active @endif" href="{{ route('admin.fakultas.index') }}">Fakultas</a>
    <a class="nav-link @if(request()->routeIs('admin.jurusan.*')) active @endif" href="{{ route('admin.jurusan.index') }}">Jurusan</a>
    <a class="nav-link @if(request()->routeIs('admin.kelas.*')) active @endif" href="{{ route('admin.kelas.index') }}">Kelas</a>
</nav>
