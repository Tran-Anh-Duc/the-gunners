<?php

namespace App\Traits;

use League\Fractal\TransformerAbstract;

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
        if (!is_numeric($httpStatus)){
            $httpStatus = 400;
        }else{
            $httpStatus = (int) $httpStatus;
        }

        return response()->json([
            'status'      => false,
            'http_status' => $httpStatus,
            'code'        => $code,
            'message'     => $message,
            'data'        => $data,
            // locale sẽ được middleware SetLocale chèn vào
        ], $httpStatus);
    }

    protected function transformData($data, TransformerAbstract $transformer)
    {
        $transformedData = fractal($data, $transformer);
        return $transformedData->toArray();
    }
}
