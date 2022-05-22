<?php

namespace App\Helpers;

class ResponseFormatter
{
    protected static array $response = [
        'meta' => [
            'code' => 200,
            'status' => 'success',
            'message' => null
        ],
        'data' => null
    ];


    public static function success($data = null, string $message = null, $code = 200)
    {
        self::$response['meta']['message'] = $message;
        self::$response['meta']['code'] = $code;
        self::$response['data'] = $data;

        return response()->json(self::$response, self::$response['meta']['code']);
    }

    public static function error($data = null, string $message = null, string $status = 'error', int $code = 400)
    {
        self::$response['meta']['message'] = $message;
        self::$response['meta']['code'] = $code;
        self::$response['data'] = $data;
        self::$response['meta']['status'] = $status;

        return response()->json(self::$response, self::$response['meta']['code']);
    }
}
