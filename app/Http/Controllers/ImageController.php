<?php

namespace App\Http\Controllers;

use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{

    public function index()
    {
        try {
            $images = Image::all();
            return responseJson($images, 200, 'Images retrieved successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }


    public function show($id)
    {
        try {
            $image = Image::find($id);

            if (!$image) {
                return responseJson(null, 404, 'Image not found');
            }

            return responseJson($image, 200, 'Image found');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }


    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'image' => 'required|file|mimes:jpg,jpeg,png,gif|max:5048',
            ]);

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $filename = Str::random(10) . '-' . str_replace(' ', '_', $image->getClientOriginalName());
                $image->storeAs('public/images', $filename);

                $uploadedImages = config('app.url') . '/storage/images/' . $filename;

                $validated['url'] = $uploadedImages;
            }

            $newImage = Image::create($validated);

            return responseJson($newImage, 201, 'Image created successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }


    public function update(Request $request, $id)
    {
        try {
            $image = Image::find($id);

            if (!$image) {
                return responseJson(null, 404, 'Image not found');
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'image' => 'sometimes|required|file|mimes:jpg,jpeg,png,gif|max:5048',
            ]);

            if ($request->hasFile('image')) {
                $oldImagePath = str_replace(config('app.url') . '/storage/', '', $image->url);
                Storage::delete('public/' . $oldImagePath);

                $newImage = $request->file('image');
                $filename = Str::random(10) . '-' . str_replace(' ', '_', $newImage->getClientOriginalName());
                $newImage->storeAs('public/images', $filename);
                $validated['url'] = config('app.url') . '/storage/images/' . $filename;
            }

            $image->update($validated);

            return responseJson($image, 200, 'Image updated successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }


    public function destroy($id)
    {
        try {
            $image = Image::find($id);

            if (!$image) {
                return responseJson(null, 404, 'Image not found');
            }

            $imagePath = str_replace(config('app.url') . '/storage/', '', $image->url);
            Storage::delete('public/' . $imagePath);

            $image->delete();

            return responseJson(null, 200, 'Image deleted successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }
}
