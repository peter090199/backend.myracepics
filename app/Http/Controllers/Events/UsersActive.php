<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class UsersActive extends Controller
{
   /**
     * Get all active photographers from users and resources.
     */
    public function getPhotographers(): JsonResponse
    {
        // Get the currently authenticated user's code
        $ownCode = auth()->user()->code ?? null;

        $photographers = DB::table('users')
            ->select('fullname', 'code', 'role_code', 'recordstatus')
            ->where('role_code', 'LIKE', '%PHOTOGRAPHER%')
            ->where('recordstatus', 'active')
            // Order by "is it me?" first (1 for true, 0 for false), then by name
            ->orderByRaw("CASE WHEN code = ? THEN 0 ELSE 1 END ASC", [$ownCode])
            ->orderBy('fullname', 'ASC')
            ->get();

        return response()->json([
            'success' => true,
            'own_code_prioritized' => $ownCode,
            'data' => $photographers,
            'count' => $photographers->count()
        ], 200);
    }
}
