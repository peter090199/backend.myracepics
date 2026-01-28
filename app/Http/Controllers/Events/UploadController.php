<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use App\Models\ImagesUpload;
use App\Models\Event\EventImage;

class UploadController extends Controller
{

    // public function uploadBase64(Request $request)
    // {
    //     $user = Auth::user();
    //     if (!$user) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Unauthenticated'
    //         ], 401);
    //     }

    //     $request->validate([
    //         'images'      => 'required|array|min:1',
    //         'images.*'    => 'required|string',
    //         'evnt_id'     => 'required|string',
    //         'evnt_name'   => 'nullable|string',
    //         'img_price'   => 'nullable|numeric',
    //         'img_qty'     => 'nullable|integer',
    //         'platform_fee'=> 'nullable|numeric',
    //         'service_fee' => 'nullable|numeric',
    //     ]);

    //     $manager = ImageManager::gd();
    //     $code          = $user->code;
    //     $roleCode      = $user->role_code;
    //     $fullname      = $user->fullname;
    //     $textwatermark = trim($user->textwatermak ?? 'myracepics.com');

    //     // Directories

    //     // $fileName = 'event-' . time() . '.png';
    //     // $relativePath = $roleCode . '/' . $code . '/' . $fileName;
    //     // Storage::disk('public')->put($relativePath, base64_decode($imageData));
    //     // $imagePath = asset('storage/app/public/' . $relativePath);

    //     $baseDir      = storage_path("app/public/{$roleCode}/{$code}/{$request->evnt_id}");
    //     $originalDir  = "{$baseDir}/original";
    //     $watermarkDir = "{$baseDir}/watermark";

    //     foreach ([$originalDir, $watermarkDir] as $dir) {
    //         if (!is_dir($dir)) mkdir($dir, 0755, true);
    //     }

    //     $uploaded = [];
    //     $skipped  = [];

    //     // ----------------------
    //     // Create Header
    //     // ----------------------
    //     $eventHeader = EventImage::create([
    //         'code'          => $code,
    //         'role_code'     => $roleCode,
    //         'fullname'      => $fullname,
    //         'evnt_id'       => $request->evnt_id,
    //         'evnt_name'     => $request->evnt_name,
    //         'img_price'     => $request->img_price ?? 0,
    //         'img_qty'       => $request->img_qty ?? 1,
    //         'platform_fee'  => $request->platform_fee ?? 0,
    //         'service_fee'   => $request->service_fee ?? 0,
    //     ]);

    //     // ----------------------
    //     // Loop through images (details)
    //     // ----------------------
    //     foreach ($request->images as $base64Image) {

    //         if (!preg_match('/^data:image\/(png|jpg|jpeg|webp);base64,/', $base64Image, $matches)) {
    //             $skipped[] = ['reason' => 'invalid_base64'];
    //             continue;
    //         }

    //         $binary = base64_decode(substr($base64Image, strpos($base64Image, ',') + 1));
    //         if ($binary === false) {
    //             $skipped[] = ['reason' => 'decode_failed'];
    //             continue;
    //         }

    //         $extension = strtolower($matches[1]);
    //         $filename  = uniqid('event_') . '.' . $extension;

    //         $originalPath  = "{$originalDir}/{$filename}";
    //         $watermarkPath = "{$watermarkDir}/{$filename}";

    //         file_put_contents($originalPath, $binary);

    //         // Read image
    //         try {
    //             $image = $manager->read($originalPath);
    //         } catch (\Exception $e) {
    //             unlink($originalPath);
    //             $skipped[] = ['reason' => 'cannot_read_image'];
    //             continue;
    //         }

    //         $imgW = $image->width();
    //         $imgH = $image->height();

    //         // Text watermark
    //         $fontSize = max(14, intval($imgW / 45));
    //         $gapX     = $fontSize * 12;
    //         $gapY     = $fontSize * 8;
    //         $fontPath = storage_path('app/public/fonts/italic.ttf');

    //         if (file_exists($fontPath)) {
    //             for ($y = -$imgH; $y < $imgH * 2; $y += $gapY) {
    //                 for ($x = -$imgW; $x < $imgW * 2; $x += $gapX) {
    //                     $image->text($textwatermark, $x, $y, function ($font) use ($fontSize, $fontPath) {
    //                         $font->file($fontPath);
    //                         $font->size($fontSize);
    //                         $font->color('rgba(255, 255, 255, 0.88)');
    //                         $font->angle(-45);
    //                     });
    //                 }
    //             }
    //         }

    //         // Logo watermark
    //         $logoPath = storage_path('app/public/watermark.jpg');
    //         if (file_exists($logoPath)) {
    //             $logo = $manager->read($logoPath)->scale(120, null, function ($constraint) {
    //                 $constraint->aspectRatio();
    //             });
    //             $image->place($logo, 'bottom-right', 20, 20);
    //         }

    //         $image->save($watermarkPath, 90);

    //         // ----------------------
    //         // Save detail row
    //         // ----------------------
    //         $detail = ImagesUpload::create([
    //             'event_image_id'=> $eventHeader->id, // FK to header
    //             'code'          => $code,
    //             'role_code'     => $roleCode,
    //             'fullname'      => $fullname,
    //             'evnt_id'       => $request->evnt_id,
    //             'evnt_name'     => $request->evnt_name,
    //             'img_id'        => (string) Str::uuid(),
    //             'img_name'      => $filename,
    //             'original_path' => $originalPath,
    //             'watermark_path'=>  $watermarkPath,
    //             'img_price'     => $request->img_price ?? 0,
    //             'img_qty'       => $request->img_qty ?? 1,
    //             'platform_fee'  => $request->platform_fee ?? 0,
    //             'service_fee'   => $request->service_fee ?? 0,
    //         ]);

    //         $uploaded[] = [
    //             'watermark' => $watermarkPath,
    //         ];
    //     }

    //    return response()->json([
    //         'success'         => true,
    //         'uploaded_count'  => count($uploaded),
    //         'skipped_count'   => count($skipped),
    //         'uploaded'        => $uploaded,
    //         'skipped'         => $skipped,
    //     ]);

    // }

    public function uploadBase64(Request $request)
    {
        // ----------------------
        // AUTH CHECK
        // ----------------------
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        // ----------------------
        // VALIDATION
        // ----------------------
        $request->validate([
            'images'       => 'required|array|min:1',
            'images.*'     => 'required|string',
            'evnt_id'      => 'required|string',
            'evnt_name'    => 'nullable|string',
            'img_price'    => 'nullable|numeric',
            'img_qty'      => 'nullable|integer',
            'platform_fee' => 'nullable|numeric',
            'service_fee'  => 'nullable|numeric',
        ]);

        // ----------------------
        // SETUP
        // ----------------------
        $manager   = ImageManager::gd();
        $disk      = Storage::disk('public');

        $code          = $user->code;
        $roleCode      = $user->role_code;
        $fullname      = $user->fullname;
        $textwatermark = trim($user->textwatermak ?? 'myracepics.com');

        // ----------------------
        // DIRECTORIES (PUBLIC DISK)
        // ----------------------
        $baseDir      = "{$roleCode}/{$code}/{$request->evnt_id}";
        $originalDir  = "{$baseDir}/original";
        $watermarkDir = "{$baseDir}/watermark";

        $disk->makeDirectory($originalDir);
        $disk->makeDirectory($watermarkDir);

        $uploaded = [];
        $skipped  = [];

        // ----------------------
        // CREATE HEADER
        // ----------------------
        $eventHeader = EventImage::create([
            'code'         => $code,
            'role_code'    => $roleCode,
            'fullname'     => $fullname,
            'evnt_id'      => $request->evnt_id,
            'evnt_name'    => $request->evnt_name,
            'img_price'    => $request->img_price ?? 0,
            'img_qty'      => $request->img_qty ?? 1,
            'platform_fee' => $request->platform_fee ?? 0,
            'service_fee'  => $request->service_fee ?? 0,
        ]);

        // ----------------------
        // LOOP IMAGES
        // ----------------------
        foreach ($request->images as $index => $base64Image) {

            // Validate base64 header
            if (!preg_match('/^data:image\/(png|jpg|jpeg|webp);base64,/', $base64Image, $matches)) {
                $skipped[] = ['index' => $index, 'reason' => 'invalid_base64'];
                continue;
            }

            $binary = base64_decode(substr($base64Image, strpos($base64Image, ',') + 1));
            if ($binary === false) {
                $skipped[] = ['index' => $index, 'reason' => 'decode_failed'];
                continue;
            }

            $extension = strtolower($matches[1]);
            $filename  = uniqid('event_') . '.' . $extension;

            // Relative paths (SAVE TO DB)
            $originalRelativePath  = "{$originalDir}/{$filename}";
            $watermarkRelativePath = "{$watermarkDir}/{$filename}";

            // Absolute paths (PROCESSING ONLY)
            $originalAbsolutePath  = public_path("{$originalRelativePath}");
            $watermarkAbsolutePath = public_path("{$watermarkRelativePath}");

            // Save original image
            $disk->put($originalRelativePath, $binary);

            // Read image
            try {
                $image = $manager->read($originalAbsolutePath);
            } catch (\Exception $e) {
                $disk->delete($originalRelativePath);
                $skipped[] = ['index' => $index, 'reason' => 'cannot_read_image'];
                continue;
            }

            $imgW = $image->width();
            $imgH = $image->height();

            // ----------------------
            // TEXT WATERMARK
            // ----------------------
            $fontSize = max(14, intval($imgW / 45));
            $gapX     = $fontSize * 12;
            $gapY     = $fontSize * 8;
            $fontPath = public_path('fonts/italic.ttf');

            if (file_exists($fontPath)) {
                for ($y = -$imgH; $y < $imgH * 2; $y += $gapY) {
                    for ($x = -$imgW; $x < $imgW * 2; $x += $gapX) {
                        $image->text($textwatermark, $x, $y, function ($font) use ($fontSize, $fontPath) {
                            $font->file($fontPath);
                            $font->size($fontSize);
                            $font->color('rgba(255,255,255,0.88)');
                            $font->angle(-45);
                        });
                    }
                }
            }

            // ----------------------
            // LOGO WATERMARK
            // ----------------------
            $logoPath = public_path('/watermark.jpg');
            if (file_exists($logoPath)) {
                $logo = $manager->read($logoPath)->scale(120, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $image->place($logo, 'bottom-right', 20, 20);
            }

            // Save watermark image
            $image->save($watermarkAbsolutePath, 90);

            // ----------------------
            // SAVE DETAIL
            // ----------------------
            $detail = ImagesUpload::create([
                'event_image_id'=> $eventHeader->id,
                'code'          => $code,
                'role_code'     => $roleCode,
                'fullname'      => $fullname,
                'evnt_id'       => $request->evnt_id,
                'evnt_name'     => $request->evnt_name,
                'img_id'        => (string) Str::uuid(),
                'img_name'      => $filename,

                // ✅ RELATIVE PATHS
                'original_path' => $originalAbsolutePath,
                'watermark_path'=> $watermarkAbsolutePath,

                'img_price'     => $request->img_price ?? 0,
                'img_qty'       => $request->img_qty ?? 1,
                'platform_fee'  => $request->platform_fee ?? 0,
                'service_fee'   => $request->service_fee ?? 0,
            ]);

            // $uploaded[] = [
            //     'img_id'        => $detail->img_id,
            //     'watermark_url' => asset('storage/' . $watermarkRelativePath),
            // ];
        }
        return response()->json([
            'success'        => true,
            'uploaded_count' => count($uploaded),
            'skipped_count'  => count($skipped),
            'skipped'        => $skipped,
        ]);
    }

    public function download($imageId)
    {
        $user = Auth::user();

        $image = ImagesUpload::findOrFail($imageId);

        // 1️⃣ Check payment
        if (!$image->is_paid) {
            abort(403, 'Payment required');
        }

        // 2️⃣ Build original path
        $path = storage_path("app/public/{$image->code}/{$image->evnt_id}/original/{$image->filename}");

        if (!file_exists($path)) {
            abort(404);
        }

        // 3️⃣ Force download (no public URL)
        return response()->download($path);
    }

}
