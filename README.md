# laravel-api-framework
RESTful api layer over Laravel 5

```bash
$ php artisan vendor:publish --provider="Karellens\LAF\LafServiceProvider"
```

Then add the following line into the `providers` array:
```php
    Karellens\LAF\LafServiceProvider::class,
```

And middleware
```php
    'laf' => \Karellens\LAF\Http\Middleware\CheckRequest::class,
```
to `routeMiddleware` array in `app/Http/Kernel.php`.

Start customize controller:
```bash
$ cp vendor/karellens/laravel-api-framework/src/LaravelApiFramework/Http/Controllers/ApiController.php app/Http/Controllers/UsersController.php 

```