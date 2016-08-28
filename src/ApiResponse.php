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
    public function error($code, $message)
    {
        return response(
            ['error' => ['code' => $code, 'message' => $message]],
            (int) $code
        )
            ->withHeaders($this->headers);
    }

    /**
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return mixed
     */
    public function paginate($query)
    {
        $page = (int)request()->input('page', 1);
        $page_size = (int)request()->input('pagesize', config('api.default_pagesize'));
        $total = $query->count();

        return [
            'total'     => $total,
            'pagesize'  => $page_size,
            'page'      => $page,
            'last_page' => ceil($total/$page_size),
            'results'   => $query->skip(($page-1)*$page_size)->take($page_size)->get(),
        ];
    }
}