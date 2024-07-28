<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Background;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BackgroundController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            $backgrounds = Background::all();
        } else {
            $backgrounds = Background::where('is_hidden', false)->get();
        }

        return responseJson($backgrounds, 200, 'Danh sách Backgrounds');
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'value' => 'required',
                'text_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'is_hidden' => 'boolean',
            ], [
                'value.required' => 'Bạn phải chọn một mã màu hoặc tải lên một hình ảnh.',
                'text_color.required' => 'Vui lòng chọn màu chữ.',
                'text_color.regex' => 'Mã màu chữ không hợp lệ.',
                'text_color.string' => 'Mã màu chữ không hợp lệ.',
            ]);

            if ($validator->fails()) {
                return responseJson(null, 400, $validator->errors());
            }

            $value = $request->input('value');
            $isHexColor = preg_match('/^#[0-9A-Fa-f]{6}$/', $value);

            if (!$isHexColor) {
                if ($request->hasFile('value')) {
                    $result = $request->file('value')->storeOnCloudinary('background_images');
                    $backgroundPublicId = $result->getPublicId();
                    $value = "{$result->getSecurePath()}?public_id={$backgroundPublicId}";
                } else {
                    return responseJson(null, 400, 'Giá trị không phải là một mã màu hợp lệ hoặc một hình ảnh.');
                }
            }

            $existingBackground = Background::where('value', $value)
                ->where('text_color', $request->input('text_color'))
                ->first();
            if ($existingBackground) {
                return responseJson(null, 409, 'Background với màu chữ này đã tồn tại.');
            }

            $background = Background::create([
                'value' => $value,
                'text_color' => $request->input('text_color'),
                'is_hidden' => $request->input('is_hidden', false),
            ]);

            return responseJson($background, 201, 'Background đã được tạo thành công');
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi tạo background: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $background = Background::find($id);

        if (!$background) {
            return responseJson(null, 404, 'Background không tồn tại');
        }

        return responseJson($background, 200, 'Thông tin chi tiết của Background');
    }

    public function update(Request $request, $id)
{
    try {
        $background = Background::find($id);

        if (!$background) {
            return responseJson(null, 404, 'Background không tồn tại');
        }

        $validator = Validator::make($request->all(), [
            'value' => 'required',
            'text_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_hidden' => 'boolean',
        ], [
            'value.required' => 'Bạn phải chọn một mã màu hoặc tải lên một hình ảnh.',
            'text_color.required' => 'Vui lòng chọn màu chữ.',
            'text_color.regex' => 'Mã màu chữ không hợp lệ.',
            'text_color.string' => 'Mã màu chữ không hợp lệ.',
        ]);

        if ($validator->fails()) {
            return responseJson(null, 400, $validator->errors());
        }

        $value = $request->input('value');
        $isHexColor = preg_match('/^#[0-9A-Fa-f]{6}$/', $value);

        if (!$isHexColor) {
            if($request->hasFile('value')){
                $result = $request->file('value')->storeOnCloudinary('background_images');
                $backgroundPublicId = $result->getPublicId();
                $value = "{$result->getSecurePath()}?public_id={$backgroundPublicId}";
            } else {
                return responseJson(null, 400, 'Giá trị không phải là một mã màu hợp lệ hoặc một hình ảnh.');
            }
        }

        $background->update([
            'value' => $value,
            'text_color' => $request->input('text_color'),
            'is_hidden' => $request->input('is_hidden', $background->is_hidden),
        ]);

        return responseJson($background, 200, 'Background đã được cập nhật thành công');
    } catch (\Exception $e) {
        return responseJson(null, 500, 'Đã xảy ra lỗi khi cập nhật background: ' . $e->getMessage());
    }
}

    public function toggleVisibility($id)
    {
        try {
            $background = Background::find($id);

            if (!$background) {
                return responseJson(null, 404, 'Background không tồn tại');
            }

            $background->is_hidden = !$background->is_hidden;
            $background->save();

            $message = $background->is_hidden ? 'Background đã được ẩn' : 'Background đã được hiển thị';

            return responseJson($background, 200, $message);
        } catch (\Exception $e) {
            return responseJson(null, 500, 'Đã xảy ra lỗi khi thay đổi trạng thái hiển thị của background: ' . $e->getMessage());
        }
    }


    public function destroy($id)
    {
        $background = Background::find($id);

        if (!$background) {
            return responseJson(null, 404, 'Background không tồn tại');
        }

        $background->delete();

        return responseJson(null, 200, 'Background đã được xóa thành công');
    }
}

