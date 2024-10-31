<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChildNavsTwo;
use App\Models\ZhChildNavsTwo;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ChildNavsTwoController extends Controller
{
    public function index()
    {
        $childNavs = ChildNavsTwo::all();
        return responseJson($childNavs);
    }

    public function indexZh()
    {
        $zhChildNavs = ZhChildNavsTwo::all();
        return responseJson($zhChildNavs);
    }

    public function show($id)
    {
        $childNav = ChildNavsTwo::find($id);

        if (!$childNav) {
            return responseJson(null, 404, 'ChildNavsTwo not found');
        }

        return responseJson($childNav);
    }

    public function showZh($id)
    {
        $zhChildNav = ZhChildNavsTwo::find($id);

        if (!$zhChildNav) {
            return responseJson(null, 404, 'ZhChildNavsTwo not found');
        }

        return responseJson($zhChildNav);
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
                'parent_nav_id' => 'required|exists:child_navs,id',
                'createdBy' => 'required|string',
                'updatedBy' => 'required|string',
                'position' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return responseJson($validator->errors(), 400, 'Validation Failed');
            }

            $childNavTwo = ChildNavsTwo::create($request->all());

            return responseJson($childNavTwo, 201, 'ChildNavsTwo created successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function storeZh(Request $request)
    {
        $user = auth()->userOrFail();
        if (!$user) {
            return responseJson(null, 401, 'Chưa xác thực người dùng');
        }

        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'zh_parent_nav_id' => 'required|exists:zh_child_navs,id',
                'createdBy' => 'required|string',
                'updatedBy' => 'required|string',
                'position' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return responseJson($validator->errors(), 400, 'Validation Failed');
            }


            $data = $request->all();
            $data['slug'] = $data['title'];

            $zhChildNavTwo = ZhChildNavsTwo::create($data);

            return responseJson($zhChildNavTwo, 201, 'ZhChildNavsTwo created successfully');
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
            $childNav = ChildNavsTwo::find($id);

            if (!$childNav) {
                return responseJson(null, 404, 'ChildNavsTwo not found');
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

            $updatedChildNavsTwo = ChildNavsTwo::find($id);

            return responseJson($updatedChildNavsTwo, 200, 'ChildNavsTwo updated successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function updateZh(Request $request, $id)
    {
        $user = auth()->userOrFail();
        if (!$user) {
            return responseJson(null, 401, 'Chưa xác thực người dùng');
        }

        try {
            $zhChildNav = ZhChildNavsTwo::find($id);

            if (!$zhChildNav) {
                return responseJson(null, 404, 'ZhChildNavsTwo not found');
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
                $updateData['slug'] = $updateData['title'];
            }


            $zhChildNav->update($updateData);

            $updatedZhChildNavsTwo = ZhChildNavsTwo::find($id);

            return responseJson($updatedZhChildNavsTwo, 200, 'ZhChildNavsTwo updated successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $childNav = ChildNavsTwo::find($id);

        if (!$childNav) {
            return responseJson(null, 404, 'ChildNavsTwo not found');
        }

        $childNav->delete();

        return responseJson(null, 200, 'ChildNavsTwo deleted successfully');
    }

    public function destroyZh($id)
    {
        $zhChildNav = ZhChildNavsTwo::find($id);

        if (!$zhChildNav) {
            return responseJson(null, 404, 'ZhChildNavsTwo not found');
        }

        $zhChildNav->delete();

        return responseJson(null, 200, 'ZhChildNavsTwo deleted successfully');
    }
}
