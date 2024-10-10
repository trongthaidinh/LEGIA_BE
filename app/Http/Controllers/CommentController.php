<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Exception;

class CommentController extends Controller
{
    public function index($productId)
    {
        try {
            $product = Product::find($productId);

            if (!$product) {
                return responseJson(null, 404, 'Product not found');
            }

            $comments = $product->comments;

            return responseJson($comments, 200, 'Comments retrieved successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $product = Product::find($request->product_id);

            if (!$product) {
                return responseJson(null, 404, 'Product not found');
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'content' => 'required|string',
                'email' => 'required|email',
                'images' => 'nullable|array',
                'images.*' => 'file|mimes:jpg,jpeg,png,gif|max:10048',
            ]);

            $comment = new Comment($validated);

            if ($request->hasFile('images')) {
                $images = $request->file('images');

                foreach ($images as $image) {
                    $filename = Str::random(10) . '-' . str_replace(' ', '_', $image->getClientOriginalName());
                    $image->storeAs('/public/images', $filename);
                    $uploadedImages[] = config('app.url') . '/storage/images/' . $filename;
                }

                $comment->images = $uploadedImages;
            }

            $comment->product_id = $product->id;
            $comment->date = now();
            $comment->save();

            return responseJson($comment, 201, 'Comment added successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    // Xóa bình luận
    public function destroy($id)
    {
        try {
            $comment = Comment::find($id);

            if (!$comment) {
                return responseJson(null, 404, 'Comment not found');
            }

            if ($comment->images) {
                $images = json_decode($comment->images);

                array_walk($images, function ($image) {
                    $path = str_replace(config('app.url') . '/storage/', '', $image);
                    $fullPath = storage_path('app/public/' . $path);

                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                    }
                });
            }

            $comment->delete();

            return responseJson(null, 200, 'Comment deleted successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }
}
