<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class ConfigurationController extends Controller
{
    public function index()
    {
        try {
            $configuration = Configuration::all();
            return responseJson($configuration, 200);
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $configuration = Configuration::find($id);

            if (!$configuration) {
                return responseJson(null, 404, 'Configuration not found');
            }

            return responseJson($configuration, 200);
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'homepage_slider' => 'nullable|array',
                'homepage_slider.*' => 'file|mimes:jpg,jpeg,png,gif,webp|max:50048',
                'contact_email' => 'nullable|email',
                'phone_number' => 'nullable|string|max:20',
            ]);

            $directory = storage_path('app/public/homepage_sliders');
            if (!Storage::exists('public/homepage_sliders')) {
                Storage::makeDirectory('public/homepage_sliders');
            }

            if ($request->hasFile('homepage_slider')) {
                $sliderImages = $request->file('homepage_slider');
                $uploadedImages = [];

                foreach ($sliderImages as $image) {
                    $filename = Str::random(10) . '-' . str_replace(' ', '_', pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)) . '.webp';
                    $imagePath = $directory . '/' . $filename;

                    convertToWebp($image->getPathname(), $imagePath);

                    $uploadedImages[] = config('app.url') . '/storage/homepage_sliders/' . $filename;
                }

                $validated['homepage_slider'] = $uploadedImages;
            }

            $configuration = Configuration::create($validated);

            return responseJson($configuration, 201, 'Configuration created successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $configuration = Configuration::find($id);

            if (!$configuration) {
                return responseJson(null, 404, 'Configuration not found');
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'homepage_slider' => 'nullable|array',
                'homepage_slider.*' => 'file|mimes:jpg,jpeg,png,gif,webp|max:50048',
                'contact_email' => 'nullable|email',
                'phone_number' => 'nullable|string|max:20',
            ]);

            $directory = storage_path('app/public/homepage_sliders');
            if (!Storage::exists('public/homepage_sliders')) {
                Storage::makeDirectory('public/homepage_sliders');
            }

            if ($request->hasFile('homepage_slider')) {
                if (!empty($configuration->homepage_slider)) {
                    foreach ($configuration->homepage_slider as $oldImage) {
                        $filePath = str_replace(config('app.url') . '/storage/', '', $oldImage);
                        if (Storage::exists('public/' . $filePath)) {
                            Storage::delete('public/' . $filePath);
                        }
                    }
                }

                $sliderImages = $request->file('homepage_slider');
                $uploadedImages = [];

                foreach ($sliderImages as $image) {
                    $filename = Str::random(10) . '-' . str_replace(' ', '_', pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)) . '.webp';
                    $imagePath = $directory . '/' . $filename;

                    convertToWebp($image->getPathname(), $imagePath);

                    $uploadedImages[] = config('app.url') . '/storage/homepage_sliders/' . $filename;
                }

                $validated['homepage_slider'] = $uploadedImages;
            }

            $configuration->update($validated);

            return responseJson($configuration, 200, 'Configuration updated successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $configuration = Configuration::find($id);

            if (!$configuration) {
                return responseJson(null, 404, 'Configuration not found');
            }

            if ($configuration->homepage_slider && is_array($configuration->homepage_slider)) {
                foreach ($configuration->homepage_slider as $image) {
                    $imagePath = str_replace(config('app.url') . '/storage', 'public', $image);
                    if (Storage::exists($imagePath)) {
                        Storage::delete($imagePath);
                    }
                }
            }

            $configuration->delete();

            return responseJson(null, 200, 'Configuration and associated images deleted successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    // private function convertToWebp($sourcePath, $destinationPath)
    // {
    //     $image = null;

    //     $imageInfo = getimagesize($sourcePath);
    //     $mimeType = $imageInfo['mime'];

    //     switch ($mimeType) {
    //         case 'image/jpeg':
    //             $image = imagecreatefromjpeg($sourcePath);
    //             break;
    //         case 'image/png':
    //             $image = imagecreatefrompng($sourcePath);
    //             break;
    //         case 'image/gif':
    //             $image = imagecreatefromgif($sourcePath);
    //             break;
    //         default:
    //             throw new Exception("Unsupported image format: $mimeType");
    //     }

    //     if ($image) {
    //         imagewebp($image, $destinationPath, 90);
    //         imagedestroy($image);
    //     }
    // }
}
