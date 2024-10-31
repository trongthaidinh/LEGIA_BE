<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ZhProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\DB;

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

    public function zhIndex(Request $request)
    {
        try {
            $zhChildNavId = $request->query('zh_child_nav_id');

            if ($zhChildNavId) {
                $products = ZhProduct::where('zh_child_nav_id', $zhChildNavId)->get();
            } else {
                $products = ZhProduct::all();
            }

            return responseJson($products, 200, 'Zh Products retrieved successfully');
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

    public function zhShow($id)
    {
        try {
            $product = ZhProduct::find($id);

            if (!$product) {
                return responseJson(null, 404, 'Zh Product not found');
            }

            return responseJson($product, 200, 'Zh Product found');
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
                'images.*' => 'file|mimes:jpg,jpeg,png,gif|max:50048',
                'child_nav_id' => 'nullable|exists:child_navs,id',
                'price' => 'required|numeric',
                'original_price' => 'nullable|numeric',
                'available_stock' => 'required|numeric',
                'phone_number' => 'required|string|max:20',
                'content' => 'nullable|string',
            ]);

            $directory = storage_path('app/public/images');
            if (!Storage::exists('public/images')) {
                Storage::makeDirectory('public/images');
            }

            if ($request->hasFile('images')) {
                $images = $request->file('images');
                $uploadedImages = [];

                foreach ($images as $image) {
                    $filename = Str::random(10) . '-' . str_replace(' ', '_', pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)) . '.webp';
                    $imagePath = $directory . '/' . $filename;
                    convertToWebp($image->getPathname(), $imagePath);
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

    public function zhStore(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'features' => 'nullable|json',
                'images' => 'nullable|array',
                'images.*' => 'file|mimes:jpg,jpeg,png,gif|max:50048',
                'zh_child_nav_id' => 'nullable|exists:zh_child_navs,id',
                'price' => 'required|numeric',
                'original_price' => 'nullable|numeric',
                'available_stock' => 'required|numeric',
                'phone_number' => 'required|string|max:20',
                'content' => 'nullable|string',
            ]);

            $validated['slug'] = $validated['name'];

            $directory = storage_path('app/public/images');
            if (!Storage::exists('public/images')) {
                Storage::makeDirectory('public/images');
            }

            if ($request->hasFile('images')) {
                $images = $request->file('images');
                $uploadedImages = [];

                foreach ($images as $image) {
                    $filename = Str::random(10) . '-' . str_replace(' ', '_', pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)) . '.webp';
                    $imagePath = $directory . '/' . $filename;
                    convertToWebp($image->getPathname(), $imagePath);
                    $uploadedImages[] = config('app.url') . '/storage/images/' . $filename;
                }

                $validated['images'] = $uploadedImages;
            }

            $product = ZhProduct::create($validated);

            return responseJson($product, 201, 'Zh Product created successfully');
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
                'child_nav_id' => 'nullable|exists:child_navs,id',
                'price' => 'nullable|numeric',
                'original_price' => 'nullable|numeric',
                'phone_number' => 'nullable|string|max:20',
                'content' => 'nullable|string',
                'available_stock' => 'nullable|numeric',
            ]);

            if ($validator->fails()) {
                return responseJson($validator->errors(), 400, 'Validation Failed');
            }

            $validatedData = $validator->validated();

            $directory = storage_path('app/public/images');
            if (!Storage::exists('public/images')) {
                Storage::makeDirectory('public/images');
            }

            if ($request->hasFile('images')) {
                $images = $request->file('images');
                $uploadedImages = [];

                foreach ($request->images as $image) {
                    if (filter_var($image, FILTER_VALIDATE_URL)) {
                        $uploadedImages[] = $image;
                    } elseif ($image instanceof \Illuminate\Http\UploadedFile) {
                        $filename = Str::random(10) . '-' . str_replace(' ', '_', pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)) . '.webp';
                        $imagePath = $directory . '/' . $filename;
                        convertToWebp($image->getPathname(), $imagePath);
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

    public function zhUpdate(Request $request, $id)
    {
        try {
            $product = ZhProduct::find($id);

            if (!$product) {
                return responseJson(null, 404, 'Zh Product not found');
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'features' => 'nullable|json',
                'images' => 'nullable|array',
                'zh_child_nav_id' => 'nullable|exists:zh_child_navs,id',
                'price' => 'nullable|numeric',
                'original_price' => 'nullable|numeric',
                'phone_number' => 'nullable|string|max:20',
                'content' => 'nullable|string',
                'available_stock' => 'nullable|numeric',
            ]);

            if ($validator->fails()) {
                return responseJson($validator->errors(), 400, 'Validation Failed');
            }

            $validatedData = $validator->validated();

            if (isset($validatedData['name'])) {
                $validatedData['slug'] = $validatedData['name'];
            }

            $directory = storage_path('app/public/images');
            if (!Storage::exists('public/images')) {
                Storage::makeDirectory('public/images');
            }

            if ($request->hasFile('images')) {
                $images = $request->file('images');
                $uploadedImages = [];

                foreach ($request->images as $image) {
                    if (filter_var($image, FILTER_VALIDATE_URL)) {
                        $uploadedImages[] = $image;
                    } elseif ($image instanceof \Illuminate\Http\UploadedFile) {
                        $filename = Str::random(10) . '-' . str_replace(' ', '_', pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)) . '.webp';
                        $imagePath = $directory . '/' . $filename;
                        convertToWebp($image->getPathname(), $imagePath);
                        $uploadedImages[] = config('app.url') . '/storage/images/' . $filename;
                    }
                }

                $validatedData['images'] = $uploadedImages;
            }

            $product->update($validatedData);

            return responseJson($product, 200, 'Zh Product updated successfully');
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
                foreach ($product->images as $image) {
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

    public function zhDestroy($id)
    {
        try {
            $product = ZhProduct::find($id);

            if (!$product) {
                return responseJson(null, 404, 'Zh Product not found');
            }

            if ($product->images) {
                foreach ($product->images as $image) {
                    $path = str_replace(config('app.url') . '/storage/', '', $image);
                    $fullPath = storage_path('app/public/' . $path);

                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                    }
                }
            }

            $product->delete();

            return responseJson(null, 200, 'Zh Product deleted successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function getProductsBySlugNav($slugNav)
    {
        try {
            $childNav = DB::table('child_navs')->where('slug', $slugNav)->first();

            if (!$childNav) {
                return responseJson(null, 404, 'Category not found');
            }

            $products = Product::where('child_nav_id', $childNav->id)->get();

            if ($products->isEmpty()) {
                return responseJson(null, 404, 'No products found for this category');
            }

            return responseJson($products, 200, 'Products retrieved successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function zhGetProductsBySlugNav($slugNav)
    {
        try {
            $zhChildNav = DB::table('zh_child_navs')->where('slug', $slugNav)->first();

            if (!$zhChildNav) {
                return responseJson(null, 404, 'Zh Category not found');
            }

            $products = ZhProduct::where('zh_child_nav_id', $zhChildNav->id)->get();

            if ($products->isEmpty()) {
                return responseJson(null, 404, 'No Zh Products found for this category');
            }

            return responseJson($products, 200, 'Zh Products retrieved successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }
}
