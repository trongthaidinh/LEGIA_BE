<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class NewsController extends Controller
{
    public function index()
    {
        try {
            $news = News::all();
            return responseJson($news, 200, 'News retrieved successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $news = News::find($id);

            if (!$news) {
                return responseJson(null, 404, 'News not found');
            }

            $news->increment('views');

            return responseJson($news, 200, 'News found');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }


    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'images' => 'nullable|array',
                'images.*' => 'file|mimes:jpg,jpeg,png,gif|max:5048',
                'child_nav_id' => 'required|exists:child_navs,id',
                'created_by' => 'nullable|string|max:255',
                'updated_by' => 'nullable|string|max:255',
                'summary' => 'nullable|string',
                'content' => 'nullable|string',
                'views' => 'nullable|integer|min:0',
                'isFeatured' => 'nullable|boolean',
            ]);

            if ($request->hasFile('images')) {
                $images = $request->file('images');
                $uploadedImages = [];

                foreach ($images as $image) {
                    $filetitle = Str::random(10) . '-' . str_replace(' ', '_', $image->getClientOriginalName());
                    $image->storeAs('/public/images', $filetitle);
                    $uploadedImages[] = 'storage/images/' . $filetitle;
                }

                $validated['images'] = $uploadedImages;
            }

            $news = News::create($validated);

            return responseJson($news, 201, 'News created successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $news = News::find($id);

            if (!$news) {
                return responseJson(null, 404, 'News not found');
            }

            $validated = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'images' => 'nullable|array',
                'images.*' => 'file|mimes:jpg,jpeg,png,gif|max:5048',
                'child_nav_id' => 'sometimes|required|exists:child_navs,id',
                'created_by' => 'nullable|string|max:255',
                'updated_by' => 'nullable|string|max:255',
                'summary' => 'nullable|string',
                'slug' => 'sometimes|required|string|unique:news,slug,' . $id,
                'content' => 'nullable|string',
                'views' => 'nullable|integer|min:0',
                'isFeatured' => 'nullable|boolean',
            ]);

            if ($request->hasFile('images')) {
                $images = $request->file('images');
                $uploadedImages = [];

                foreach ($images as $image) {
                    $filetitle = Str::random(10) . '-' . str_replace(' ', '_', $image->getClientOriginalName());
                    $image->storeAs('public/images', $filetitle);
                    $uploadedImages[] = 'storage/images/' . $filetitle;
                }

                $validated['images'] = $uploadedImages;
            }

            $news->update($validated);

            return responseJson($news, 200, 'News updated successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $news = News::find($id);

            if (!$news) {
                return responseJson(null, 404, 'News not found');
            }

            if ($news->images) {
                $images = $news->images;

                foreach ($images as $image) {
                    $path = str_replace('storage/', 'public/', $image);
                    $fullPath = storage_path('app/' . $path);

                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                    }
                }
            }

            $news->delete();

            return responseJson(null, 200, 'News deleted successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }
}
