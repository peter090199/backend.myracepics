<?php

use Illuminate\Support\Facades\Route;
use  App\Http\Controllers\Auth\ProfileController;
use  App\Http\Controllers\Auth\PostController;
use  App\Http\Controllers\MessageController;
use  App\Events\MessageSent;
use  App\Http\Controllers\Auth\GoogleAuthController;
use Intervention\Image\ImageManager;
use App\Http\Controllers\Events\UploadController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::get('/login', function () {
    return view('auth.signin');
});
Route::get('/test-upload', function () {
    return view('auth.test-upload');
});


//     Route::get('/testwatermark', function () {

//     // ✅ Create Image Manager using GD
//     $manager = ImageManager::gd();

//     // ✅ Read base image
//   $image = $manager->read(storage_path('app/public/watermark.jpg'));

//     // ✅ Apply watermark
//     $watermarkPath = public_path('images/watermark.jpg');

//     if (file_exists($watermarkPath)) {
//         $image->place(
//             $watermarkPath,
//             'bottom-right', // position
//             10,             // x offset
//             10,             // y offset
//             25              // opacity
//         );
//     }

//     // ✅ Save output
//     $savePath = storage_path('app/public/test-watermarked.jpg');
//     $image->save($savePath);

//     return response()->json([
//         'success' => true,
//         'url' => asset('storage/test-watermarked.jpg')
//     ]);
// });

Route::get('/testwatermark', function () {
    // ✅ Create Image Manager (Intervention v3)
    $manager = ImageManager::gd();
    // ✅ BASE IMAGE (main image)
    $baseImagePath = storage_path('app/public/watermark.jpg');

    if (!file_exists($baseImagePath)) {
        return response()->json([
            'success' => false,
            'message' => 'Base image not found at storage/app/public/base.jpg'
        ], 404);
    }

    // ✅ Read base image
    $image = $manager->read($baseImagePath);

    // ✅ WATERMARK IMAGE
   $watermarkPath = storage_path('app/public/watermark.jpg');

    if (!file_exists($watermarkPath)) {
        return response()->json([
            'success' => false,
            'message' => 'Watermark image not found at public/images/watermark.jpg'
        ], 404);
    }

    // ✅ Apply watermark
    $image->place(
        $watermarkPath,
        'bottom-right', // position
        10,             // x offset
        10,             // y offset
        25              // opacity (0–100)
    );

    // ✅ Save result
    $savePath = storage_path('app/public/images/test-watermarked.jpg');
    $image->save($savePath, quality: 90);

    return response()->json([
        'success' => true,
        'url' => asset('storage/test-watermarked.jpg')
    ]);
});


Route::get('/loopwatermark', function () {

    // ✅ Create Image Manager (GD)
    $manager = ImageManager::gd();

    // ✅ BASE IMAGE
   $baseImagePath = storage_path('app/public/watermark.jpg');

    if (!file_exists($baseImagePath)) {
        return response()->json([
            'success' => false,
            'message' => 'Base image not found'
        ], 404);
    }

    $image = $manager->read($baseImagePath);

    // ✅ WATERMARK IMAGE
    $watermarkPath = storage_path('app/public/watermark.jpg');

    if (!file_exists($watermarkPath)) {
        return response()->json([
            'success' => false,
            'message' => 'Watermark image not found'
        ], 404);
    }

    // ✅ Read watermark
    $watermark = $manager->read($watermarkPath);

    // ✅ Get sizes
    $imageWidth  = $image->width();
    $imageHeight = $image->height();

    $wmWidth  = $watermark->width();
    $wmHeight = $watermark->height();

    // ✅ Vertical spacing (gap between watermarks)
    $gap = 40;

    // ✅ START POSITION (center horizontally)
    $x = intval(($imageWidth - $wmWidth) / 2);
    $y = 10;

    // ✅ LOOP VERTICALLY
    while ($y < $imageHeight) {
        $image->place(
            $watermarkPath,
            'top-left',
            $x,
            $y,
            25 // opacity (0–100)
        );

        $y += $wmHeight + $gap;
    }

    // ✅ Save result
    $savePath = storage_path('app/public/images/loopwatermark.jpg');
    $image->save($savePath, quality: 90);

    return response()->json([
        'success' => true,
        'url' => asset('storage/loopwatermark.jpg')
    ]);
});


Route::get('/diagonalwatermark1', function () {

    $manager = ImageManager::gd();

    // ✅ Base image
    $basePath = storage_path('app/public/profile.jpg');
    if (!file_exists($basePath)) {
        return response()->json(['success' => false, 'message' => 'Base image missing'], 404);
    }

    $image = $manager->read($basePath);

    // ✅ Watermark image (PNG recommended with transparency)
    $watermarkPath = storage_path('app/public/watermark.jpg');
    if (!file_exists($watermarkPath)) {
        return response()->json(['success' => false, 'message' => 'Watermark missing'], 404);
    }

    $watermark = $manager->read($watermarkPath);

    // ✅ Resize watermark (optional)
    $watermark->scale(120); // adjust size

    // ✅ Rotate watermark diagonally
    $watermark->rotate(-45);

    // ✅ Image dimensions
    $imgW = $image->width();
    $imgH = $image->height();

    $wmW = $watermark->width();
    $wmH = $watermark->height();

    // ✅ Gap between watermarks
    $gapX = 80;
    $gapY = 80;

    // ✅ Tile watermark diagonally
    for ($y = -$imgH; $y < $imgH * 2; $y += ($wmH + $gapY)) {
        for ($x = -$imgW; $x < $imgW * 2; $x += ($wmW + $gapX)) {
            $image->place(
                $watermark,
                'top-left',
                $x,
                $y,
                20 // opacity (0–100)
            );
        }
    }

    // ✅ Save output
    $savePath = storage_path('app/public/diagonal-watermarked.jpg');
    $image->save($savePath, quality: 90);

    return response()->json([
        'success' => true,
        'url' => asset('storage/diagonal-watermarked.jpg')
    ]);
});

Route::get('/diagonalwatermark2', function () {

    $manager = ImageManager::gd();

    // ✅ Base image
    $basePath = storage_path('app/public/profile.jpg');
    if (!file_exists($basePath)) {
        return response()->json(['success' => false, 'message' => 'Base image missing'], 404);
    }

    $image = $manager->read($basePath);

    // ✅ Watermark (transparent PNG)
    $watermarkPath = storage_path('app/public/wt2.png');
    if (!file_exists($watermarkPath)) {
        return response()->json(['success' => false, 'message' => 'Watermark missing'], 404);
    }

    $watermark = $manager->read($watermarkPath);

    // 🔥 MAKE WATERMARK SMALL
    $watermark->scale(80);     // 👈 smaller watermark
    $watermark->rotate(-45);   // diagonal

    // ✅ Dimensions
    $imgW = $image->width();
    $imgH = $image->height();
    $wmW  = $watermark->width();
    $wmH  = $watermark->height();

    // 🔥 Tighter spacing = professional look
    $gapX = 120;
    $gapY = 120;

    // ✅ Tile watermark diagonally
    for ($y = -$imgH; $y < $imgH * 2; $y += ($wmH + $gapY)) {
        for ($x = -$imgW; $x < $imgW * 2; $x += ($wmW + $gapX)) {
            $image->place(
                $watermark,
                'top-left',
                $x,
                $y,
                15 // 👈 soft opacity
            );
        }
    }

    // ✅ Save
    $savePath = storage_path('app/public/logo/diagonal-watermarked.jpg');
    $image->save($savePath, quality: 90);

    return response()->json([
        'success' => true,
        'url' => asset('storage/diagonal-watermarked.jpg')
    ]);
});



Route::get('/diagonalwatermarklogo', function () {

    $manager = ImageManager::gd();

    // ✅ Base image
    $basePath = storage_path('app/public/profile.jpg');
    if (!file_exists($basePath)) {
        return response()->json(['success' => false, 'message' => 'Base image missing'], 404);
    }
    $image = $manager->read($basePath);

    // ✅ Watermark (transparent PNG)
    $watermarkPath = storage_path('app/public/wt2.png');
    if (!file_exists($watermarkPath)) {
        return response()->json(['success' => false, 'message' => 'Watermark missing'], 404);
    }
    $watermark = $manager->read($watermarkPath);

    // 🔥 Make watermark smaller & rotate
    $watermark->scale(80);     // smaller watermark
    $watermark->rotate(-45);   // diagonal

    // ✅ Dimensions
    $imgW = $image->width();
    $imgH = $image->height();
    $wmW  = $watermark->width();
    $wmH  = $watermark->height();

    // 🔥 Tighter spacing for tiling
    $gapX = 120;
    $gapY = 120;

    // ✅ Tile watermark diagonally
    for ($y = -$imgH; $y < $imgH * 2; $y += ($wmH + $gapY)) {
        for ($x = -$imgW; $x < $imgW * 2; $x += ($wmW + $gapX)) {
            $image->place(
                $watermark,
                'top-left',
                $x,
                $y,
                17 // soft opacity
            );
        }
    }

    // ✅ Add logo image in bottom-right corner
    $logoPath = storage_path('app/public/watermark.png');
    if (file_exists($logoPath)) {
        $logo = $manager->read($logoPath);

        // Resize logo if needed (optional)
        $logo->scale(170);

        // Place logo 20px from bottom-right corner
        $image->place(
            $logo,
            'bottom-right',
            50,
            50,
            100 // full opacity for logo
        );
    }

    // ✅ Save final image
    $savePath = storage_path('app/public/diagonal-watermarked.jpg');
    $image->save($savePath, 90);

    return response()->json([
        'success' => true,
        'url' => asset('storage/diagonal-watermarked.jpg')
    ]);
});



Route::get('/diagonalwatermarklogo2', function () {

    $manager = ImageManager::gd();

    // ✅ Base image
    $basePath = storage_path('app/public/profile.jpg');
    if (!file_exists($basePath)) {
        return response()->json(['success' => false, 'message' => 'Base image missing'], 404);
    }
    $image = $manager->read($basePath);

    // ✅ Watermark (transparent PNG)
    $watermarkPath = storage_path('app/public/wt2.png');
    if (!file_exists($watermarkPath)) {
        return response()->json(['success' => false, 'message' => 'Watermark missing'], 404);
    }
    $watermark = $manager->read($watermarkPath);

    // 🔥 Make watermark smaller & rotate
    $watermark->scale(80);
    $watermark->rotate(-45);

    // ✅ Dimensions
    $imgW = $image->width();
    $imgH = $image->height();
    $wmW  = $watermark->width();
    $wmH  = $watermark->height();

    // 🔥 Tile watermark diagonally
    $gapX = 120;
    $gapY = 120;
    for ($y = -$imgH; $y < $imgH * 2; $y += ($wmH + $gapY)) {
        for ($x = -$imgW; $x < $imgW * 2; $x += ($wmW + $gapX)) {
            $image->place($watermark, 'top-left', $x, $y, 15);
        }
    }

    // ✅ Add logo in bottom-right with NO margin
    $logoPath = storage_path('app/public/watermark.png');
    if (file_exists($logoPath)) {
        $logo = $manager->read($logoPath);

        // Resize logo if needed
        $logo->scale(150);

        // Place logo flush to bottom-right
        $image->place($logo, 'bottom-right', 0, 0, 100);
    }

    // ✅ Save final image
    $savePath = storage_path('app/public/diagonal-watermarked.jpg');
    $image->save($savePath, 90);

    return response()->json([
        'success' => true,
        'url' => asset('storage/diagonal-watermarked.jpg')
    ]);
});



Route::get('/diagonalwatermarkText', function () {

    $manager = ImageManager::gd();
    $basePath = storage_path('app/public/profile.jpg');
    if (!file_exists($basePath)) {
        return response()->json(['success' => false, 'message' => 'Base image missing'], 404);
    }
    $image = $manager->read($basePath);
    $imgW = $image->width();
    $imgH = $image->height();

    // =============================
    // TEXT WATERMARK (REPEATED)
    // =============================
    $text = 'PITIK NI ELIZABETH';
    $fontSize = max(14, intval($imgW / 60));
    $opacity = 1;

    $gapX = $fontSize * 12;
    $gapY = $fontSize * 8;
    $angle = -45;

    // Slide increment per column for staggered diagonal effect
    $slideStep = intval($fontSize * 2);

    for ($y = -$imgH; $y < $imgH * 2; $y += $gapY) {
        for ($x = -$imgW; $x < $imgW * 2; $x += $gapX) {

            // Calculate staggered slide offset per column
            $slideOffset = ($y / $gapY) * $slideStep;

            // Shadow text for readability
            $image->text($text, $x + $slideOffset + 2, $y + 2, function ($font) use ($fontSize, $angle) {
                $font->size($fontSize);
                $font->color('rgba(255, 247, 247, 0)');
                $font->angle($angle);
            });

            // Main watermark text
            $image->text($text, $x + $slideOffset, $y, function ($font) use ($fontSize, $opacity, $angle) {
                $font->size($fontSize);
                 $font->color('rgba(255, 248, 248, 0.98)');
                $font->angle($angle);
            });
        }
    }

    // =============================
    // OPTIONAL LOGO IMAGE (BOTTOM-RIGHT)
    // =============================
    $logoPath = storage_path('app/public/watermark.png');
    if (file_exists($logoPath)) {
        $logo = $manager->read($logoPath);
        $logo->scale(120);

        $image->place($logo, 'bottom-right', 10, 10, 80);
    }

    // =============================
    // Save image
    // =============================
    $savePath = storage_path('app/public/diagonal-watermarked.jpg');
    $image->save($savePath, 90);

    return response()->json([
        'success' => true,
        'url' => asset('storage/diagonal-watermarked.jpg')
    ]);
});


Route::get('/diagonalwatermarkslide22', function () {

    $manager = ImageManager::gd();
    $basePath = storage_path('app/public/profile.jpg');
    if (!file_exists($basePath)) {
        return response()->json(['success' => false, 'message' => 'Base image missing'], 404);
    }
    $image = $manager->read($basePath);
    $imgW = $image->width();
    $imgH = $image->height();

    $text = '© Juan Dela Cruz Photography';

    $fontSize = max(12, intval($imgW / 60));
    $opacity = 0.3;

    $gapX = $fontSize * 12;
    $gapY = $fontSize * 8;
    $angle = -45;

    // Slide increment per column for staggered diagonal effect
    $slideStep = intval($fontSize * 2);

    for ($y = -$imgH; $y < $imgH * 2; $y += $gapY) {
        for ($x = -$imgW; $x < $imgW * 2; $x += $gapX) {

            // Calculate staggered slide offset per column
            $slideOffset = ($y / $gapY) * $slideStep;

            // Shadow text for readability
            $image->text($text, $x + $slideOffset + 2, $y + 2, function ($font) use ($fontSize, $angle) {
                $font->size($fontSize);
                $font->color('rgba(0,0,0,0.3)');
                $font->angle($angle);
            });

            // Main watermark text
            $image->text($text, $x + $slideOffset, $y, function ($font) use ($fontSize, $opacity, $angle) {
                $font->size($fontSize);
                $font->color("rgba(255,255,255,$opacity)");
                $font->angle($angle);
            });
        }
    }

    // Optional logo bottom-right
    $logoPath = storage_path('app/public/watermark.png');
    if (file_exists($logoPath)) {
        $logo = $manager->make($logoPath)->resize(120, null, function ($constraint) {
            $constraint->aspectRatio();
        });

        $image->insert($logo, 'bottom-right', 10, 10);
    }

    $savePath = storage_path('app/public/diagonal-watermarked.jpg');
    $image->save($savePath, 90);

    return response()->json([
        'success' => true,
        'url' => asset('storage/diagonal-watermarked.jpg')
    ]);
});



Route::get("auth/google",[GoogleAuthController::class,"redirectToGoogle"])->name("redirect.google");
Route::get("auth/google/callback",[GoogleAuthController::class,"handleGoogleCallback"]);
Route::get('/', function () {
    return view('welcome');
});