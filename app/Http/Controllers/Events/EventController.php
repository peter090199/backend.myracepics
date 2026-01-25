<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event\Events;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Facades\Image;

class EventController extends Controller
{
    public function save(Request $request)
    {
        $validated = $request->validate([
            'title'    => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'date'     => 'required|date',
            'category' => 'required|string|max:100',
            'image'    => 'nullable|string',
        ]);
        // 🔥 Get authenticated user
        $user = Auth::user();

        $code = $user->code;           // ex: Photographer
        $roleCode = $user->role_code;  // ex: PH

        $imagePath = null;

        if (!empty($validated['image'])) {

            $imageData = preg_replace('#^data:image/\w+;base64,#i', '', $validated['image']);
            $imageData = str_replace(' ', '+', $imageData);

            // Folder by role_code
            $fileName = 'event-' . time() . '.png';
            $relativePath = $roleCode . '/' . $code . '/' . $fileName;
            Storage::disk('public')->put($relativePath, base64_decode($imageData));
            $imagePath = asset('storage/app/public/' . $relativePath);
        }

        $event = Events::create([
            'title'     => $validated['title'],
            'location'  => $validated['location'],
            'date'      => $validated['date'],
            'category'  => $validated['category'],
            'code'      => $code,
            'role_code' => $roleCode,
            'image'     => json_encode($imagePath ? [$imagePath] : []),
            'user_id'   => $user->id, // recommended
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Event saved successfully',
            'event'   => $event
        ]);
    }


    public function save1(Request $request)
    {
        $validated = $request->validate([
            'title'    => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'date'     => 'required|date',
            'category' => 'required|string|max:100',
            'image'    => 'nullable|string',
        ]);

        $imagePath = null;

        if (!empty($validated['image'])) {
            $imageData = preg_replace('#^data:image/\w+;base64,#i', '', $validated['image']);
            $imageData = str_replace(' ', '+', $imageData);

            $imageName = 'event-' . time() . '.png';
            $relativePath = 'events/' . $imageName;

            // Save image
            Storage::disk('public')->put($relativePath, base64_decode($imageData));

            // 🔥 FORCE URL FORMAT YOU WANT
            $imagePath = url('storage/app/public/' . $relativePath);
        }

        $event = Events::create([
            'title'    => $validated['title'],
            'location' => $validated['location'],
            'date'     => $validated['date'],
            'category' => $validated['category'],
            'image'    => json_encode($imagePath ? [$imagePath] : []),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Event saved successfully',
            'event'   => $event
        ]);
    }

    public function delete($id)
    {
        $event = Events::find($id);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found',
            ], 404);
        }

        // Delete images from storage
        $images = json_decode($event->image, true);
        if (!empty($images)) {
            foreach ($images as $img) {
                $filePath = str_replace('storage/', '', $img); // remove 'storage/' prefix
                if (Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                }
            }
        }

        // Delete the event from DB
        $event->delete();

        return response()->json([
            'success' => true,
            'message' => 'Event deleted successfully',
        ]);
    }


    public function update(Request $request, $id)
    {
        $event = Events::find($id);
        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found',
            ], 404);
        }

        $validated = $request->validate([
            'title'    => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'date'     => 'required|date',
            'category' => 'required|string|max:100',
            'image'    => 'nullable|string', // optional new base64 image
        ]);

        $imagePathArray = json_decode($event->image, true) ?? [];

        if (!empty($validated['image'])) {
            // Delete old images
            foreach ($imagePathArray as $img) {
                $filePath = str_replace('storage/', '', $img);
                if (Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                }
            }

            // Save new image
            $imageData = preg_replace('#^data:image/\w+;base64,#i', '', $validated['image']);
            $imageData = str_replace(' ', '+', $imageData);
            $imageName = 'event-' . time() . '.png';
            Storage::disk('public')->put('events/' . $imageName, base64_decode($imageData));
            $imagePathArray = ['storage/events/' . $imageName];
        }

        // Update event
        $event->update([
            'title'    => $validated['title'],
            'location' => $validated['location'],
            'date'     => $validated['date'],
            'category' => $validated['category'],
            'image'    => json_encode($imagePathArray),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Event updated successfully',
            'event'   => $event
        ]);
    }


    public function getEvents()
    {
        // Fetch all events, ordered by latest first
        $events = Events::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'events'  => $events
        ]);
    }

   public function getEventByUuid($uuid)
    {
        // Find event by UUID
        $event = Events::where('uuid', $uuid)->first();

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found.'
            ], 404);
        }

        // If the 'image' field is stored as JSON array, get only the first image
        $image = null;
        if (!empty($event->image)) {
            $images = json_decode($event->image, true); // decode JSON
            if (is_array($images) && count($images) > 0) {
                $image = $images[0]; // get only the first image
            }
        }

        // Return as array
        return response()->json([
            'success' => true,
            'event' => [
                'id'                  => $event->id,
                'uuid'                => $event->uuid,
                'title'               => $event->title,
                'location'            => $event->location,
                'date'                => $event->date,
                'category'            => $event->category,
                'image'               => $image,
                'photosCount'         => $event->photos_count ?? 0,
                'participantsCount'   => $event->participants_count ?? 0,
            ]
        ]);
    }

  public function upload(Request $request, $uuid)
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
        'photos' => 'required|array',
        'photos.*' => 'required|string', // base64 strings
        'apply_watermark' => 'sometimes|boolean', // optional toggle
    ]);

    $applyWatermark = $validated['apply_watermark'] ?? true;
    $uploadedFiles = [];

    foreach ($validated['photos'] as $index => $photoBase64) {

        // Remove base64 prefix
        $imageData = preg_replace('#^data:image/\w+;base64,#i', '', $photoBase64);
        $imageData = str_replace(' ', '+', $imageData);

        $decoded = base64_decode($imageData);
        if ($decoded === false) {
            return response()->json([
                'success' => false,
                'message' => "Photo #$index is not a valid base64 string"
            ], 400);
        }

        // Generate file name
        $fileName = 'photo-' . time() . '-' . $index . '.png';

        // Folders
        $folderOriginal = 'original';
        $folderWatermarked = 'watermarked';

        $relativeOriginal = "$roleCode/$code/events/$uuid/$folderOriginal/$fileName";
        $relativeWatermarked = "$roleCode/$code/events/$uuid/$folderWatermarked/$fileName";

        // Ensure directories exist
        foreach ([$relativeOriginal, $relativeWatermarked] as $path) {
            $dir = storage_path('app/public/' . dirname($path));
            if (!is_dir($dir)) mkdir($dir, 0755, true);
        }

        // Save original
        Storage::disk('public')->put($relativeOriginal, $decoded);

        // Save watermarked
        if ($applyWatermark) {
            try {
                $image = Image::make($decoded);

                $watermarkPath = storage_path('app/public/watermark.jpg');
                if (file_exists($watermarkPath)) {
                    $watermark = Image::make($watermarkPath);
                    $image->insert($watermark, 'bottom-right', 10, 10);
                }

                Storage::disk('public')->put($relativeWatermarked, (string) $image->encode('png'));
            }  catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Photo #$index could not be processed as an image",
                'error' => $e->getMessage()
            ], 400);
        }
        } else {
            // If watermark not applied, just copy original
            Storage::disk('storage/app/public')->put($relativeWatermarked, $decoded);
        }

        $uploadedFiles[] = [
            'name' => $fileName,
            'original' => asset('storage/app/public/' . $relativeOriginal),
            'watermarked' => asset('storage/app/public/' . $relativeWatermarked),
        ];
    }

    return response()->json([
        'success' => true,
        'message' => 'Photos uploaded successfully',
        'files' => $uploadedFiles,
    ]);
}

  public function uploaddefault(Request $request, $uuid)
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
        'photos' => 'required|array',
        'photos.*' => 'required|string', // base64 strings
    ]);

    $uploadedFiles = [];

    foreach ($validated['photos'] as $index => $photoBase64) {

        // Remove base64 prefix
        $imageData = preg_replace('#^data:image/\w+;base64,#i', '', $photoBase64);
        $imageData = str_replace(' ', '+', $imageData);

        $decoded = base64_decode($imageData);
        if ($decoded === false) {
            return response()->json([
                'success' => false,
                'message' => "Photo #$index is not a valid base64 string"
            ], 400);
        }

        // Generate file name
        $fileName = 'photo-' . time() . '-' . $index . '.png';

        // Relative path: role/code/events/uuid/photos
        $folder = 'photos';
        $relativePath = $roleCode . '/' . $code . '/events/' . $uuid . '/' . $folder . '/' . $fileName;

        // Save to storage
        Storage::disk('public')->put($relativePath, $decoded);

        // Save uploaded file info
        $uploadedFiles[] = [
            'name' => $fileName,
            'url' => asset('storage/app/public/' . $relativePath),
        ];
    }

    return response()->json([
        'success' => true,
        'message' => 'Photos uploaded successfully',
        'files' => $uploadedFiles,
    ]);
}

public function uploadx222(Request $request, $uuid)
{
    try {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }
        if (empty($user->code)) {
            return response()->json(['success' => false, 'message' => 'User code missing'], 400);
        }

        $roleCode = $user->role_code;
        $userCode = $user->code;

        $validated = $request->validate([
            'photos' => 'required|array',
            'photos.*' => 'required|string',
        ]);

        $uploadedFiles = [];

        foreach ($validated['photos'] as $index => $base64Image) {

            // Clean base64
            $imageData = preg_replace('#^data:image/\w+;base64,#i', '', $base64Image);
            $imageData = str_replace(' ', '+', $imageData);

            $decoded = base64_decode($imageData, true);
            if ($decoded === false) {
                return response()->json([
                    'success' => false,
                    'message' => "Photo #$index is not a valid base64 string"
                ], 400);
            }

            // Filename
            $fileName = 'photo-' . time() . '-' . Str::random(5) . '.png';

            $relativeOriginal = "$roleCode/$userCode/events/$uuid/original/$fileName";
            $relativeWatermarked = "$roleCode/$userCode/events/$uuid/watermarked/$fileName";

            // Ensure directories
            foreach ([$relativeOriginal, $relativeWatermarked] as $path) {
                $dir = storage_path('app/public/' . dirname($path));
                if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to create directory: ' . $dir
                    ], 500);
                }
            }

            // Save original
            Storage::disk('public')->put($relativeOriginal, $decoded);

            // Watermarked image
            try {
                $image = Image::make($decoded);

                $watermarkPath = storage_path('app/public/watermark.jpg');
                if (file_exists($watermarkPath)) {
                    $watermark = Image::make($watermarkPath);
                    $image->insert($watermark, 'bottom-right', 10, 10);
                }

                Storage::disk('public')->put($relativeWatermarked, (string) $image->encode('png'));
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to process image: ' . $e->getMessage()
                ], 500);
            }

            $uploadedFiles[] = [
                'name' => $fileName,
                'original' => asset('storage/' . $relativeOriginal),
                'watermarked' => asset('storage/' . $relativeWatermarked),
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Photos uploaded successfully',
            'files' => $uploadedFiles,
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Upload failed: ' . $e->getMessage()
        ], 500);
    }
}


    public function uploadxx(Request $request, $uuid)
    {
        try {
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
                'photos.*' => 'required|image|mimes:jpeg,png|max:10240', // 10MB max
            ]);

            $uploadedFiles = [];

            $files = $request->file('photos');

            if (!$files || count($files) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No photos uploaded'
                ], 400);
            }

            foreach ($files as $file) {

                // Generate file name
                $fileName = 'photo-' . time() . '-' . Str::random(5) . '.' . $file->getClientOriginalExtension();

                // Relative path in storage: role/code/event/photos
                $relativePath = $roleCode . '/' . $code . '/events/' . $uuid . '/' . $fileName;

                // Ensure directory exists
                $dir = dirname(storage_path('app/public/' . $relativePath));
                if (!file_exists($dir)) {
                    mkdir($dir, 0755, true);
                }

                // Open image using Intervention Image
                $image = Image::make($file->getRealPath());

                // 🔹 Apply watermark if exists
                if (Storage::disk('public')->exists('watermark.jpg')) {
                    $watermark = Image::make(public_path('storage/watermark.jpg'));
                    $image->insert($watermark, 'bottom-right', 10, 10);
                }

                // Encode image to PNG
                $imageData = (string) $image->encode('png');

                // Save to storage
                Storage::disk('public')->put($relativePath, $imageData);

                // Add uploaded file info
                $uploadedFiles[] = [
                    'name' => $file->getClientOriginalName(),
                    'url' => asset('storage/app/public/' . $relativePath),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Photos uploaded successfully',
                'files' => $uploadedFiles,
            ]);
        } catch (\Exception $e) {
            // Return error for debugging
            return response()->json([
                'success' => false,
                'message' => 'Upload failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


}
