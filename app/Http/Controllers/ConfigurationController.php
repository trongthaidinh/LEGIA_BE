<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\ZhConfiguration;
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

    public function indexZh()
    {
        try {
            $zhConfiguration = ZhConfiguration::all();
            return responseJson($zhConfiguration, 200);
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

    public function showZh($id)
    {
        try {
            $zhConfiguration = ZhConfiguration::find($id);
            return responseJson($zhConfiguration, 200);
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

    public function storeZh(Request $request)
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

            $zhConfiguration = ZhConfiguration::create($validated);

            return responseJson($zhConfiguration, 201, 'ZhConfiguration created successfully');
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
                'homepage_slider' => 'required|json',
                'slide_images.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:50048',
                'contact_email' => 'nullable|email',
                'phone_number' => 'nullable|string|max:20',
            ]);

            // Decode the homepage_slider JSON string to array
            $existingSlides = json_decode($request->homepage_slider, true);

            // Handle new uploaded images
            if ($request->hasFile('slide_images')) {
                $directory = storage_path('app/public/homepage_sliders');
                if (!Storage::exists('public/homepage_sliders')) {
                    Storage::makeDirectory('public/homepage_sliders');
                }

                foreach ($request->file('slide_images') as $image) {
                    $filename = Str::random(10) . '-' . str_replace(' ', '_', pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)) . '.webp';
                    $imagePath = $directory . '/' . $filename;

                    convertToWebp($image->getPathname(), $imagePath);

                    // Add new image URL to existing slides
                    $existingSlides[] = config('app.url') . '/storage/homepage_sliders/' . $filename;
                }
            }

            // Update the validated data with combined slider images
            $validated['homepage_slider'] = $existingSlides;

            $configuration->update($validated);

            return responseJson($configuration, 200, 'Configuration updated successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function updateZh(Request $request, $id)
    {
        try {
            $zhConfiguration = ZhConfiguration::find($id);

            if (!$zhConfiguration) {
                return responseJson(null, 404, 'Configuration not found');
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'homepage_slider' => 'required|json',
                'slide_images.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:50048',
                'contact_email' => 'nullable|email',
                'phone_number' => 'nullable|string|max:20',
            ]);

            // Decode the homepage_slider JSON string to array
            $existingSlides = json_decode($request->homepage_slider, true);

            // Handle new uploaded images
            if ($request->hasFile('slide_images')) {
                $directory = storage_path('app/public/homepage_sliders');
                if (!Storage::exists('public/homepage_sliders')) {
                    Storage::makeDirectory('public/homepage_sliders');
                }

                foreach ($request->file('slide_images') as $image) {
                    $filename = Str::random(10) . '-' . str_replace(' ', '_', pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)) . '.webp';
                    $imagePath = $directory . '/' . $filename;

                    convertToWebp($image->getPathname(), $imagePath);

                    // Add new image URL to existing slides
                    $existingSlides[] = config('app.url') . '/storage/homepage_sliders/' . $filename;
                }
            }

            // Update the validated data with combined slider images
            $validated['homepage_slider'] = $existingSlides;

            $zhConfiguration->update($validated);

            return responseJson($zhConfiguration, 200, 'ZhConfiguration updated successfully');
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

    public function destroyZh($id)
    {
        try {
            $zhConfiguration = ZhConfiguration::find($id);

            if (!$zhConfiguration) {
                return responseJson(null, 404, 'Configuration not found');
            }

            if ($zhConfiguration->homepage_slider && is_array($zhConfiguration->homepage_slider)) {
                foreach ($zhConfiguration->homepage_slider as $image) {
                    $imagePath = str_replace(config('app.url') . '/storage', 'public', $image);
                    if (Storage::exists($imagePath)) {
                        Storage::delete($imagePath);
                    }
                }
            }

            $zhConfiguration->delete();

            return responseJson(null, 200, 'ZhConfiguration and associated images deleted successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }
}
