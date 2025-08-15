<?php

namespace App\Traits;

trait ApiResponse
{
    protected function successResponse(  $message = '',  $code = '',  $httpStatus = 200,$data = null)
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

    protected function errorResponse( $message = '',  $code = '',  $httpStatus = 400, $data = null)
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
