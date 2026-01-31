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
        // Fetch only the active photographers
        $photographers = DB::table('users')
            ->select('fullname','code', 'role_code', 'recordstatus')
            ->where('role_code', 'LIKE', '%PHOTOGRAPHER%')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $photographers,
            'count' => $photographers->count()
        ], 200);
    }
}
