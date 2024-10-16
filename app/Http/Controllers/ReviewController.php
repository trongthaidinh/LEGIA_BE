<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReviewController extends Controller
{
    public function index()
    {
        try {
            $reviews = Review::all();
            return responseJson($reviews, 200, 'Reviews retrieved successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $review = Review::find($id);

            if (!$review) {
                return responseJson(null, 404, 'Review not found');
            }

            return responseJson($review, 200, 'Review found');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'image' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:10048',
                'review' => 'required|string|max:1000',
            ]);

            $directory = storage_path('app/public/reviews');
            if (!Storage::exists('public/reviews')) {
                Storage::makeDirectory('public/reviews');
            }

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $filename = Str::random(10) . '-' . str_replace(' ', '_', pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)) . '.webp';
                $imagePath = $directory . '/' . $filename;
                convertToWebp($image->getPathname(), $imagePath);
                $validated['image'] = config('app.url') . '/storage/reviews/' . $filename;
            }

            $newReview = Review::create($validated);

            return responseJson($newReview, 201, 'Review created successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $review = Review::find($id);

            if (!$review) {
                return responseJson(null, 404, 'Review not found');
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'image' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:10048',
                'review' => 'sometimes|required|string|max:1000',
            ]);

            $directory = storage_path('app/public/images');
            if (!Storage::exists('public/images')) {
                Storage::makeDirectory('public/images');
            }

            if ($request->hasFile('image')) {
                $oldImagePath = str_replace(config('app.url') . '/storage/', '', $review->image);
                Storage::delete('public/' . $oldImagePath);

                $image = $request->file('image');
                $filename = Str::random(10) . '-' . str_replace(' ', '_', pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)) . '.webp';
                $imagePath = $directory . '/' . $filename;
                convertToWebp($image->getPathname(), $imagePath);
                $validated['image'] = config('app.url') . '/storage/reviews/' . $filename;
            }

            $review->update($validated);

            return responseJson($review, 200, 'Review updated successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $review = Review::find($id);

            if (!$review) {
                return responseJson(null, 404, 'Review not found');
            }

            // Xóa hình ảnh (nếu có)
            if ($review->image) {
                $imagePath = str_replace(config('app.url') . '/storage/', '', $review->image);
                Storage::delete('public/' . $imagePath);
            }

            $review->delete();

            return responseJson(null, 200, 'Review deleted successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }
}
