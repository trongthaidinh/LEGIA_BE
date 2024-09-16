<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChildNav;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ChildNavController extends Controller
{
    public function index()
    {
        $childNavs = ChildNav::all();
        return responseJson($childNavs);
    }

    public function show($id)
    {
        $childNav = ChildNav::find($id);

        if (!$childNav) {
            return responseJson(null, 404, 'ChildNav not found');
        }

        return responseJson($childNav);
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
                'parent_nav_id' => 'required|exists:parent_navs,id',
                'createdBy' => 'required|string',
                'updatedBy' => 'required|string',
                'position' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return responseJson($validator->errors(), 400, 'Validation Failed');
            }

            $childNav = ChildNav::create($request->all());

            return responseJson($childNav, 201, 'ChildNav created successfully');
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
            $childNav = ChildNav::find($id);

            if (!$childNav) {
                return responseJson(null, 404, 'ChildNav not found');
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


            $childNav->update($updateData);

            $updatedChildNav = ChildNav::find($id);

            return responseJson($updatedChildNav, 200, 'ChildNav updated successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $childNav = ChildNav::find($id);

        if (!$childNav) {
            return responseJson(null, 404, 'ChildNav not found');
        }

        $childNav->delete();

        return responseJson(null, 200, 'ChildNav deleted successfully');
    }
}
