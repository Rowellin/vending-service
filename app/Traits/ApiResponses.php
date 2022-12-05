<?php

namespace App\Traits;

trait ApiResponses
{
    protected function success($data = null, $message = '', $code = 200)
    {
        return response()->json([
            'code' => $code,
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function failure($message = 'Internal server error', $code = 500)
    {
        return response()->json([
            'code' => $code,
            'success' => false,
            'message' => $message,
        ], $code);
    }
}
