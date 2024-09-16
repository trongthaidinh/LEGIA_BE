<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ParentNav;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

use Exception;

class ParentNavController extends Controller
{
    public function index()
    {
        try {
            $parentNavs = ParentNav::all();
            return responseJson($parentNavs, 200, "Get Parent Navigation Successful");
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function getAllWithChildren()
    {
        try {
            $parentNavs = ParentNav::with('children.children')->get();

            return responseJson($parentNavs, 200, 'ParentNavs with children and grandchildren retrieved successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }


    public function show($id)
    {
        try {
            $parentNav = ParentNav::find($id);

            if (!$parentNav) {
                return responseJson(null, 404, 'ParentNav not found');
            }

            return responseJson($parentNav, 200, 'ParentNav found');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $user = auth()->userOrFail();
        if (!$user) {
            return responseJson(null, 401, 'Chưa xác thực người dùng');
        }

        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'created_by' => 'required|string',
                'updated_by' => 'required|string',
                'position' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return responseJson($validator->errors(), 400, 'Validation Failed');
            }

            $parentNav = ParentNav::create($request->all());

            return responseJson($parentNav, 201, 'ParentNav created successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }


    public function update(Request $request, $id)
    {
        $user = auth()->userOrFail();
        if (!$user) {
            return responseJson(null, 401, 'Chưa xác thực người dùng');
        }

        try {
            $parentNav = ParentNav::find($id);

            if (!$parentNav) {
                return responseJson(null, 404, 'ParentNav not found');
            }

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|required|string|max:255',
                'updated_by' => 'string',
                'position' => 'sometimes|required|integer',
            ]);

            if ($validator->fails()) {
                return responseJson($validator->errors(), 400, 'Validation Failed');
            }

            $updateData = $request->only(['title', 'updated_by', 'position']);

            if (isset($updateData['title'])) {
                $updateData['slug'] = Str::slug($updateData['title']);
            }


            $parentNav->update($updateData);

            $updatedParentNav = ParentNav::find($id);

            return responseJson($updatedParentNav, 200, 'ParentNav updated successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }


    public function destroy($id)
    {
        try {
            $parentNav = ParentNav::find($id);

            if (!$parentNav) {
                return responseJson(null, 404, 'ParentNav not found');
            }

            $parentNav->delete();

            return responseJson(null, 200, 'ParentNav deleted successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }
}
