<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; 
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Userprofile;
use App\Models\Resource;



class ProfilepictureController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $imagePath = "https://exploredition/public/storage/app/public/uploads/702/cvphoto/d0bd7bb8-72f5-43ef-9f26-c382181982f9/HjL8tqsDplfNwrImxwf1YqANUilOt2KL5si1AVQ3.png";
        return view('testuploads',compact('imagePath'));
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
     public function store(Request $request)
    {
        $data = $request->all();
        
        // Validate file input before starting the transaction
        $validator = Validator::make($data, [
            'photo_pic' => 'required|file|image|max:3000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->all(),
            ]);
        }

        if (!$request->hasFile('photo_pic')) {
            return response()->json([
                'success' => false,
                'message' => 'No file was uploaded.',
            ]);
        }

        try {
            DB::beginTransaction(); // Start the transaction

            $file = $request->file('photo_pic');
            $userCode = Auth::user()->code;
            $uuid = Str::uuid();
            $folderPath = "uploads/{$userCode}/cvphoto/{$uuid}";

            // Store the file with a readable name
            $fileName = time() . '.' . $file->getClientOriginalExtension();
            $photoPath = $file->storeAs($folderPath, $fileName, 'public');

            // Construct the full file URL
            $photoUrl = asset(Storage::url($photoPath));
            
           

            // Check if the user profile exists
            $exists = UserProfile::where('code', $userCode)->first();

                   if ($exists) {
                UserProfile::where('code', $userCode)->update([
                     'photo_pic' => "https://exploredition.com/storage/app/public/".$folderPath."/".$fileName,
                ]);
            } else {
                $transNo = UserProfile::max('transNo');
                $newTrans = empty($transNo) ? 1 : $transNo + 1;

                UserProfile::insert([
                    'code' => $folderPath,
                    'transNo' => $newTrans,
                     'photo_pic' => "https://exploredition.com/storage/app/public/".$folderPath."/".$fileName,
                ]);
            }
            DB::commit(); // Commit the transaction
            return response()->json([
                'success' => true,
                'photo_path' => $photoUrl,
            ]);

        } catch (\Throwable $th) {
            DB::rollBack(); // Rollback the transaction on error

            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $th->getMessage(),
            ]);
        }
    }


    public function updateImage(Request $request)
    {
        // 🔥 Get authenticated user
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        if (empty($user->code)) {
            return response()->json([
                'success' => false,
                'message' => 'User code missing'
            ], 400);
        }

        $code = $user->code;
        $roleCode = $user->role_code;

        // Validate input
        $validated = $request->validate([
            'logo' => 'sometimes|nullable|string', // base64 string
            'profile_picture' => 'sometimes|nullable|string', // base64 string
        ]);

        // Handle logo upload (base64)
        if (!empty($validated['logo'])) {

            // Delete old logo
            if ($user->logo && Storage::disk('public')->exists($user->logo)) {
                Storage::disk('public')->delete($user->logo);
            }

            $imageData = preg_replace('#^data:image/\w+;base64,#i', '', $validated['logo']);
            $imageData = str_replace(' ', '+', $imageData);

            $fileName = 'logo-' . time() . '.png';
            $logoname = 'logo';
            $relativePath = "$roleCode/$code/$logoname/$fileName";

            // Make directory if not exists
            Storage::disk('public')->makeDirectory("$roleCode/$code/$logoname");

            Storage::disk('public')->put($relativePath, base64_decode($imageData));

            $user->logo = $relativePath;
        }

        // Handle profile_picture upload (base64)
        if (!empty($validated['profile_picture'])) {

            // Delete old profile picture
            if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
                Storage::disk('public')->delete($user->profile_picture);
            }

            $imageData = preg_replace('#^data:image/\w+;base64,#i', '', $validated['profile_picture']);
            $imageData = str_replace(' ', '+', $imageData);

            $fileName = 'profile-' . time() . '.png';
            $profilename = 'profilepic';
            $relativePath = "$roleCode/$code/$profilename/$fileName";

            // Make directory if not exists
            Storage::disk('public')->makeDirectory("$roleCode/$code/$profilename");

            Storage::disk('public')->put($relativePath, base64_decode($imageData));

            $user->profile_picture = $relativePath;
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Images updated successfully',
            'logo_url' => $user->logo ? asset('storage/' . $user->logo) : null,
            'profile_picture_url' => $user->profile_picture
                ? asset('storage/' . $user->profile_picture)
                : null
        ]);
    }



     public function updateProfile(Request $request)
    {
        // 🔥 Get authenticated user
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        if (empty($user->code)) {
            return response()->json([
                'success' => false,
                'message' => 'User code missing'
            ], 400);
        }

        $code = $user->code;
        $roleCode = $user->role_code;

        // Validate input
        $validated = $request->validate([
            'fname' => 'sometimes|required|string|max:50',
            'mname' => 'sometimes|nullable|string|max:50',
            'lname' => 'sometimes|required|string|max:50',
            'contact_no' => 'sometimes|nullable|string|max:20',
            'current_location' => 'sometimes|nullable|string|max:100',
            'date_birth' => 'sometimes|nullable|date',
            'gender' => 'sometimes|nullable|string|max:20',
            'textwatermak' => 'sometimes|nullable|string|max:50',
            'logo' => 'sometimes|nullable|string', // base64 or URL
            'profile_picture' => 'sometimes|nullable|string', // base64 or URL
        ]);

        // Handle logo upload (base64)
        if (!empty($validated['logo'])) {
            $imageData = preg_replace('#^data:image/\w+;base64,#i', '', $validated['logo']);
            $imageData = str_replace(' ', '+', $imageData);

            $fileName = 'logo-' . time() . '.png';
            $logoname = 'logo';
            $relativePath = $roleCode . '/' . $code . '/' . $logoname . '/' . $fileName;
            Storage::disk('public')->put($relativePath, base64_decode($imageData));
            $validated['logo'] = asset('storage/app/public/' . $relativePath);
        }

        // Handle profile_picture upload (base64)
        if (!empty($validated['profile_picture'])) {
            $imageData = preg_replace('#^data:image/\w+;base64,#i', '', $validated['profile_picture']);
            $imageData = str_replace(' ', '+', $imageData);

            $fileName = 'profile-' . time() . '.png';
             $profilename = 'profilepic';
            $relativePath = $roleCode . '/' . $code . '/' .  $profilename . '/' . $fileName;
            Storage::disk('public')->put($relativePath, base64_decode($imageData));
            $validated['profile_picture'] = asset('storage/app/public/' . $relativePath);
        }

        // Update User table
        $userFields = ['fname','mname','lname','contact_no','current_location','date_birth','gender','textwatermak','logo','profile_picture'];
        $userUpdate = array_intersect_key($validated, array_flip($userFields));
        $user->update($userUpdate);

        // Update Resource table by code
        $resource = Resource::where('code', $code)->first();
        if ($resource) {
            $resourceFields = ['fname','mname','lname','contact_no','current_location','date_birth','gender','textwatermak','logo','profile_picture'];
            $resourceUpdate = array_intersect_key($validated, array_flip($resourceFields));
            $resource->update($resourceUpdate);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => $user,
            'resource' => $resource ?? null
        ]);
    }


    public function getProfile()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }
        $code = $user->code;
        // Get resource by code
        $resource = Resource::where('code', $code)->first();

        return response()->json([
            'success' => true,
            'data' => $resource,      
        ]);
    }

    
}
