<?php

namespace Karellens\LAF;

use Illuminate\Http\Response;

class ApiResponse
{
    protected $headers = [
        'Access-Control-Allow-Origin' => '*',
        'Content-Type' => 'application/json; charset=utf-8',
        'Cache-Control' => 'no-cache, must-revalidate',
        'Pragma' => 'no-cache',
        'Expires' => 0,
    ];

    public function __construct($headers = [])
    {
        $this->headers = array_merge($this->headers, $headers);
    }

    /**
     * @param int $code
     * @param string $message
     * @return mixed
     */
    public function error($message, $code = 500)
    {
        return response(
            ['error' => ['code' => $code, 'message' => $message]],
            (int) $code
        )
            ->withHeaders($this->headers);
    }

    /**
     * @param int $code
     * @param string $message
     * @return mixed
     */
    public function success($message, $code = 200)
    {
        return response(
            ['success' => ['code' => $code, 'message' => $message]],
            (int) $code
        )
            ->withHeaders($this->headers);
    }
}