<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Exception;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        try {
            $childNavId = $request->query('child_nav_id');

            if ($childNavId) {
                $products = Product::where('child_nav_id', $childNavId)->get();
            } else {
                $products = Product::all();
            }

            return responseJson($products, 200, 'Products retrieved successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return responseJson(null, 404, 'Product not found');
            }

            return responseJson($product, 200, 'Product found');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'features' => 'nullable|json',
                'images' => 'nullable|array',
                'images.*' => 'file|mimes:jpg,jpeg,png,gif|max:5048',
                'child_nav_id' => 'required|exists:child_navs,id',
                'created_by' => 'nullable|string|max:255',
                'updated_by' => 'nullable|string|max:255',
                'summary' => 'nullable|string',
                'phone_number' => 'required|string|max:20',
                'content' => 'nullable|string',
            ]);

            if ($request->hasFile('images')) {
                $images = $request->file('images');
                $uploadedImages = [];

                foreach ($images as $image) {
                    $filename = Str::random(10) . '-' . str_replace(' ', '_', $image->getClientOriginalName());
                    $image->storeAs('/public/images', $filename);
                    $uploadedImages[] = config('app.url') . '/storage/images/' . $filename;
                }

                $validated['images'] = $uploadedImages;
            }

            $product = Product::create($validated);

            return responseJson($product, 201, 'Product created successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return responseJson(null, 404, 'Product not found');
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'features' => 'nullable|json',
                'images' => 'nullable|array',
                'images.*' => 'sometimes|required',
                'child_nav_id' => 'sometimes|required|exists:child_navs,id',
                'created_by' => 'nullable|string|max:255',
                'updated_by' => 'nullable|string|max:255',
                'summary' => 'nullable|string',
                'phone_number' => 'nullable|string|max:20',
                'content' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return responseJson($validator->errors(), 400, 'Validation Failed');
            }

            $validatedData = $validator->validated();

            if ($request->has('images')) {
                $images = $request->file('images');
                $uploadedImages = [];

                foreach ($request->images as $image) {
                    if (filter_var($image, FILTER_VALIDATE_URL)) {
                        $uploadedImages[] = $image;
                    } elseif ($image instanceof \Illuminate\Http\UploadedFile) {
                        $filename = Str::random(10) . '-' . str_replace(' ', '_', $image->getClientOriginalName());
                        $image->storeAs('public/images', $filename);
                        $uploadedImages[] = config('app.url') . '/storage/images/' . $filename;
                    }
                }

                $validatedData['images'] = $uploadedImages;
            }

            $product->update($validatedData);

            return responseJson($product, 200, 'Product updated successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return responseJson(null, 404, 'Product not found');
            }

            if ($product->images) {
                $images = $product->images;

                foreach ($images as $image) {
                    $path = str_replace(config('app.url') . '/storage/', '', $image);
                    $fullPath = storage_path('app/public/' . $path);

                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                    }
                }
            }

            $product->delete();

            return responseJson(null, 200, 'Product deleted successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }
}
