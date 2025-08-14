<?php

namespace App\Traits;

trait ApiResponse
{
    protected function successResponse( string $message = '',  $code = '',  $httpStatus = 200,$data = null)
    {
        return response()->json([
            'status'      => true,
            'http_status' => $httpStatus,
            'code'        => $code,
            'message'     => $message,
            'data'        => $data,
            // locale sẽ được middleware SetLocale chèn vào
        ], $httpStatus);
    }

    protected function errorResponse(string $message = '',  $code = '',  $httpStatus = 400, $data = null)
    {
        return response()->json([
            'status'      => false,
            'http_status' => $httpStatus,
            'code'        => $code,
            'message'     => $message,
            'data'        => $data,
            // locale sẽ được middleware SetLocale chèn vào
        ], $httpStatus);
    }
}
