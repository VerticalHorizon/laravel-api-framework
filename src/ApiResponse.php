<?php

namespace Karellens\LAF;

use Illuminate\Http\Response;

class ApiResponse
{
    protected $headers = [
        'Content-Type' => 'application/json',
    ];

    public function __construct($headers = [])
    {
        $this->headers = array_merge($this->headers, $headers);
    }

    /**
     * @return mixed
     */
    public function error($code, $message)
    {
        return response(
            ['error' => ['code' => $code, 'message' => $message]],
            (int) $code
        )
            ->withHeaders($this->headers);
    }
}