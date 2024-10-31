<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChildNav;
use App\Models\ZhChildNav;
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

    public function indexZh()
    {
        $childNavs = ZhChildNav::all();
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

    public function showZh($id)
    {
        $zhChildNav = ZhChildNav::find($id);

        if (!$zhChildNav) {
            return responseJson(null, 404, 'ZhChildNav not found');
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

    public function storeZh(Request $request)
    {
        $user = auth()->userOrFail();
        if (!$user) {
            return responseJson(null, 401, 'Chưa xác thực người dùng');
        }

        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'zh_parent_nav_id' => 'required|exists:zh_parent_navs,id',
                'createdBy' => 'required|string',
                'updatedBy' => 'required|string',
                'position' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return responseJson($validator->errors(), 400, 'Validation Failed');
            }

            $data = $request->all();
            $data['slug'] = $data['title'];

            $zhChildNav = ZhChildNav::create($data);

            return responseJson($zhChildNav, 201, 'ZhChildNav created successfully');
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

    public function updateZh(Request $request, $id)
    {
        $user = auth()->userOrFail();
        if (!$user) {
            return responseJson(null, 401, 'Chưa xác thực người dùng');
        }

        try {
            $zhChildNav = ZhChildNav::find($id);

            if (!$zhChildNav) {
                return responseJson(null, 404, 'ZhChildNav not found');
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

            $updatedZhChildNav = ZhChildNav::find($id);

            return responseJson($updatedZhChildNav, 200, 'ZhChildNav updated successfully');
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

    public function destroyZh($id)
    {
        $zhChildNav = ZhChildNav::find($id);

        if (!$zhChildNav) {
            return responseJson(null, 404, 'ZhChildNav not found');
        }

        $zhChildNav->delete();

        return responseJson(null, 200, 'ZhChildNav deleted successfully');
    }
}
