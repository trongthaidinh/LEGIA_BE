<?php

namespace App\Http\Controllers;

use App\Models\Experience;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class ExperienceController extends Controller
{
    public function index()
    {
        try {
            $experiences = Experience::all();
            return responseJson($experiences, 200, 'Experiences retrieved successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $experience = Experience::find($id);

            if (!$experience) {
                return responseJson(null, 404, 'Experience not found');
            }

            return responseJson($experience, 200, 'Experience found');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'images' => 'nullable|array',
                'images.*' => 'file|mimes:jpg,jpeg,png,gif|max:5048',
                'child_nav_id' => 'required|exists:child_navs,id',
                'created_by' => 'nullable|string|max:255',
                'updated_by' => 'nullable|string|max:255',
                'summary' => 'nullable|string',
                'content' => 'nullable|string',
            ]);

            if ($request->hasFile('images')) {
                $images = $request->file('images');
                $uploadedImages = [];

                foreach ($images as $image) {
                    $filename = Str::random(10) . '-' . str_replace(' ', '_', $image->getClientOriginalName());
                    $image->storeAs('/public/images', $filename);
                    $uploadedImages[] = 'storage/images/' . $filename;
                }

                $validated['images'] = $uploadedImages;
            }

            $experience = Experience::create($validated);

            return responseJson($experience, 201, 'Experience created successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $experience = Experience::find($id);

            if (!$experience) {
                return responseJson(null, 404, 'Experience not found');
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'images' => 'nullable|array',
                'images.*' => 'file|mimes:jpg,jpeg,png,gif|max:5048',
                'child_nav_id' => 'sometimes|required|exists:child_navs,id',
                'created_by' => 'nullable|string|max:255',
                'updated_by' => 'nullable|string|max:255',
                'summary' => 'nullable|string',
                'slug' => 'sometimes|required|string|unique:experiences,slug,' . $id,
                'content' => 'nullable|string',
            ]);

            if ($request->hasFile('images')) {
                $images = $request->file('images');
                $uploadedImages = [];

                foreach ($images as $image) {
                    $filename = Str::random(10) . '-' . str_replace(' ', '_', $image->getClientOriginalName());
                    $image->storeAs('public/images', $filename);
                    $uploadedImages[] = 'storage/images/' . $filename;
                }

                $validated['images'] = $uploadedImages;
            }

            $experience->update($validated);

            return responseJson($experience, 200, 'Experience updated successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $experience = Experience::find($id);

            if (!$experience) {
                return responseJson(null, 404, 'Experience not found');
            }

            if ($experience->images) {
                $images = $experience->images;

                foreach ($images as $image) {
                    $path = str_replace('storage/', 'public/', $image);
                    $fullPath = storage_path('app/' . $path);

                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                    }
                }
            }

            $experience->delete();

            return responseJson(null, 200, 'Experience deleted successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }
}
