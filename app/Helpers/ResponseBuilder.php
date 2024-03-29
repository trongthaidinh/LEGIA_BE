<?php
use Illuminate\Http\Request;



function responseJson($data, $status = 200) {
    $messages = [
        200 => 'Success',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        500 => 'Internal Server Error',
        503 => 'Service Unavailable',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        511 => 'Network Authentication Required',
        530 => 'Access Denied',
    ];

    return response()->json(['message'=> $messages[$status] ,'data' => $data, 'status' => $status]);
}
