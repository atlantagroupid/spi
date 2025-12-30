<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Ambil role user yang sedang login
        $userRole = Auth::user()->role;

        // Cek apakah role user ada di dalam daftar yang dibolehkan
        if (in_array($userRole, $roles)) {
            return $next($request);
        }

        // Kalau tidak boleh, tendang keluar
        abort(403, 'Akses Ditolak. Anda tidak memiliki izin untuk halaman ini.');
    }
}
