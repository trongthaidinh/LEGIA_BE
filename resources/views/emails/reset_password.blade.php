<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .header {
            background-color: #153448;
            color: #fff;
            padding: 20px;
            text-align: center;
        }

        .content {
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
            margin: 20px auto;
            max-width: 600px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .footer {
            background-color: #153448;
            color: #fff;
            text-align: center;
            padding: 10px;
        }

        a {
            color: #153448;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .btn {
            background-color: #153448;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            display: inline-block;
        }

        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reset Password</h1>
    </div>
    <div class="content">
        <p>Bạn đã yêu cầu đặt lại mật khẩu cho tài khoản của mình. Nhấp vào liên kết dưới đây để đặt lại mật khẩu:</p>
        <p><a class="btn" href="{{ $url }}">Đặt lại mật khẩu</a></p>
        <p>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</p>
    </div>
    <div class="footer">
        <p>Sandy - Social Media</p>
    </div>
</body>
</html>
