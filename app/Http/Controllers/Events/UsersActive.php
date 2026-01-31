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
        // 1. Put the table WITH the 'code' column FIRST
        $query1 = DB::table('resources')
            ->select('fullname', 'code', 'role_code', 'status')
            ->where('role_code', 'DEF-PHOTOGRAPHER')
            ->where('recordstatus', 'active');

        // 2. Put the table WITHOUT the 'code' column SECOND
        // Use DB::raw to force a string value so it cannot be null
        $query2 = DB::table('users')
            ->select(
                'fullname', 
                DB::raw("'NOT-ASSIGNED' as code"), 
                'role_code', 
                'status'
            )
            ->where('role_code', 'DEF-PHOTOGRAPHER')
            ->where('recordstatus', 'active');

        // 3. Union them
        $photographers = $query1->union($query2)->get();

        return response()->json([
            'success' => true,
            'data' => $photographers,
            'count' => $photographers->count()
        ], 200);
    }
}
