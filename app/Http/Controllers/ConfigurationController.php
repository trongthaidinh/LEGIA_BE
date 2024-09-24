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
                'homepage_slider.*' => 'file|mimes:jpg,jpeg,png,gif|max:10048', // Validate each file
                'contact_email' => 'nullable|email',
                'phone_number' => 'nullable|string|max:20',
            ]);

            // Handle file uploads
            if ($request->hasFile('homepage_slider')) {
                $sliderImages = $request->file('homepage_slider');
                $uploadedImages = [];

                foreach ($sliderImages as $image) {
                    $filename = Str::random(10) . '-' . str_replace(' ', '_', $image->getClientOriginalName());
                    $image->storeAs('public/homepage_sliders', $filename); // Save to storage
                    $uploadedImages[] = config('app.url') . '/storage/homepage_sliders/' . $filename;
                }

                // Add the uploaded image URLs to the validated data
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
                'homepage_slider.*' => 'file|mimes:jpg,jpeg,png,gif|max:10048',
                'contact_email' => 'nullable|email',
                'phone_number' => 'nullable|string|max:20',
            ]);

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
                    $filename = Str::random(10) . '-' . str_replace(' ', '_', $image->getClientOriginalName());
                    $image->storeAs('public/homepage_sliders', $filename);
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
}
