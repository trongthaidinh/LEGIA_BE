<?php
    function userValidatorMessages(){
        return [
            'old_password.required' => 'Vui lòng nhập mật khẩu cũ!',
            'new_password.required' => 'Vui lòng điền mật khẩu mới!',
            'new_password.min' => 'Mật khẩu phải chứa ít nhất 8 kí tự!',
            'new_password.max' => 'Mật khẩu chỉ chứa tối đa 200 kí tự!',
            'email.required' => 'Vui lòng nhập email',
            'email.email' => 'Email không đúng định dạng',
            'email.unique' => 'Email được sử dụng',
            'password.required' => 'Vui lòng nhập mật khẩu',
            'password.max' => 'Mật khẩu tối đa 200 ký tự',
            'password.min' => 'Mật khẩu tối thiểu 8 ký tự',
            'first_name.required' => 'Vui lòng nhập tên',
            'last_name.required' => 'Vui lòng nhập họ',
            'first_name.max' => 'Tên tối đa 30 ký tự',
            'last_name.max' => 'Họ tối đa 20 ký tự',
            'phone_number.required' => 'Vui lòng nhập số điện thoại',
            'phone_number.max' => 'Số điện thoại tối đa 10 ký tự',
            'phone_number.unique' => 'Số điện thoại đã được sử dụng',
            'role.in' => 'Vai trò không hợp lệ',
            'avatar.mimes' => 'Avatar phải dùng các file có định dạng sau: jpeg, png, jpg',
            'avatar.max' => 'Avatar tối đa 2MB',
            'avatar.file' => 'Avatar phải là định dạng file',
            'avatar.image' => 'Avatar phải là file ảnh',
            'gender.in' => 'Giới tính phải là một trong: nam, nữ, khác',
            'avatar.required' => 'Vui lòng chọn avatar',
            'friend.required' => 'Vui lòng chọn người dùng để kết bạn!',
            'friend.exists' => 'Người dùng bạn muốn kết bạn không tìm thấy!',
        ];
    }

    function chatValidatorMessages(){
        return [
            'type.in' => 'Dạng phòng chat chỉ bao gồm "Cá nhân" hoặc "Nhóm"',
        ];
    }



?>
