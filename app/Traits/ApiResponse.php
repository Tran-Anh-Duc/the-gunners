<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use League\Fractal\TransformerAbstract;

trait ApiResponse
{
    protected function successResponse(  $message = '',  $code = '',  $httpStatus = 200,$data = null): JsonResponse
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

    protected function errorResponse( $message = '',  $code = '',  $httpStatus = 400, $data = null): JsonResponse
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

    protected function transformData($data, TransformerAbstract $transformer): ?array
    {
        $transformedData = fractal($data, $transformer);
        return $transformedData->toArray();
    }
}
