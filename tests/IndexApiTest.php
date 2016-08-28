<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class IndexApiTest extends TestCase
{
    use DatabaseMigrations;
    use DatabaseTransactions;

    public function setUp()
    {
        parent::setUp();

        // seeding
        factory(App\User::class, 160)->create();
        factory(App\Post::class, 110)->create()->each(function($u) {
            $u->tags()->sync(factory(App\Tag::class, 20)->create());
        });
    }

    public function testStructure()
    {
        $this->json('GET', '/api/v1/users')
            ->seeJsonStructure([
                'total',
                'per_page',
                'current_page',
                'last_page',
                'next_page_url',
                'prev_page_url',
                'from',
                'to',
                'data' => [
                    '*' => [
                        'id', 'name', 'email', 'created_at', 'updated_at', 'posts'
                    ]
                ],
            ]);
    }

    public function testPageSize()
    {
        $pagesize = 10;

        $json = $this
            ->json('GET', '/api/v1/users', ['pagesize' => $pagesize])
            ->decodeResponseJson();

        $this->assertEquals($pagesize, count($json['data']), 'Response data count not equals to pagesize');
        $this->assertEquals($pagesize, (int)$json['per_page'], 'Response data count not equals to responded per_page value');
    }


    public function testPageNumber()
    {
        $page = 4;

        $json = $this
            ->json('GET', '/api/v1/users', ['page' => $page])
            ->decodeResponseJson();

        $this->assertEquals($page, (int)$json['current_page'], 'Response page not equals to requested page');
        $this->assertEquals(intval($json['per_page'])*($page-1)+1, (int)$json['from']);
        $this->assertEquals(intval($json['per_page'])*($page), (int)$json['to']);
    }

    public function testFields()
    {
        $this
            ->json('GET', '/api/v1/users', ['fields' => 'name,posts'])
            ->seeJsonStructure([
                'total',
                'per_page',
                'current_page',
                'last_page',
                'next_page_url',
                'prev_page_url',
                'from',
                'to',
                'data' => [
                    '*' => [
                        'id', 'name', 'posts'
                    ]
                ],
            ]);
    }

    public function testFilter()
    {
        $this
            ->json('GET', '/api/v1/users', ['filter' => 'name,posts'])
            ->seeJsonStructure([
                'total',
                'per_page',
                'current_page',
                'last_page',
                'next_page_url',
                'prev_page_url',
                'from',
                'to',
                'data' => [
                    '*' => [
                        'id', 'name', 'posts'
                    ]
                ],
            ]);
    }
}