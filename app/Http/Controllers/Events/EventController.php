<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event\Events;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;


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

    // public function save(Request $request)
    // {
    //     // Validate incoming request
    //     $validated = $request->validate([
    //         'title'    => 'required|string|max:255',
    //         'location' => 'required|string|max:255',
    //         'date'     => 'required|date',
    //         'category' => 'required|string|max:100',
    //         'image'    => 'nullable|string', // base64 image from Angular
    //     ]);

    //     $imagePath = null;

    //     if (!empty($validated['image'])) {
    //         $imageData = preg_replace('#^data:image/\w+;base64,#i', '', $validated['image']);
    //         $imageData = str_replace(' ', '+', $imageData);
    //         $imageName = 'event-' . time() . '.png';

    //         Storage::disk('public')->put('events/' . $imageName, base64_decode($imageData));

    //         // Full public URL
    //         $imagePath = asset('storage/events/' . $imageName);
    //     }
    //     // Create event in DB
    //     $imagePath = $imagePath ? [$imagePath] : [];
    //     $event = Events::create([
    //         'title'    => $validated['title'],
    //         'location' => $validated['location'],
    //         'date'     => $validated['date'],
    //         'category' => $validated['category'],
    //         'image'    => json_encode($imagePath),
    //     ]);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Event saved successfully',
    //         'event'   => $event
    //     ]);
    // }

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



}
