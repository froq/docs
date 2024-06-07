# Application [#git](//github.com/froq/froq/blob/master/src/App.php)

Application (as a class `froq\App` and internally a global variable `$app`) is the base where all application logic is being run and managed, and comes with lots of handy / ready-to-use properties and methods. Here we'll see all properties & methods step by step examples.

### Checking URI root (base) & environment

```php
public function fooAction() {
    // true (if root is "/" as default or defined like "/api/v1").
    assert($this->app->isRoot() === true);
    assert(($this->app->root === '/api/v1') === true);

    // true (if app is running on PHP's built-in server or a server like "foo.local").
    assert($this->app->isLocal() === true);
    assert($this->app->isDevelopment() === true);
    assert(($this->app->env == \froq\AppEnv::DEVELOPMENT) === true);
}
```

### Accessing / using `Logger`, `Request`, `Response` properties

```php
public function fooAction() {
    // Level is LogLevel::ALL as default (can be changed via $logger->setLevel()).
    assert($this->app->logger instanceof \froq\log\Logger);

    // Global request / response objects.
    assert($this->app->request instanceof \froq\http\Request);
    assert($this->app->response instanceof \froq\http\Response);

    // As a shortcut, as same as the properties above.
    assert($this->request instanceof \froq\http\Request);
    assert($this->response instanceof \froq\http\Response);
}
```

### Optional properties `Session`, `Database`, `Cache`
These properties (`$session`, `$database` and `$cache`) will only be created automatically by given the configuration options in `app/config/config.php` file and accessible from all over the controllers (e.g. `$this->app->session`).

```php
// Sample Session options (as default):
'session' => true,
// Sample Session options (as custom):
'session' => [
    'name'     => string,
    'hash'     => 'uuid' /* or 32, 40, 16 */, 'hashUpper' => bool,
    'savePath' => 'path/to/session-folder', 'saveHandler' => /* class or [class, class-file] */,
    'cookie'   => [
        'lifetime' => int,  'path'     => string, 'domain'   => string,
        'secure'   => bool, 'httponly' => bool,   'samesite' => string,
    ],
],

// Sample database options.
'database' => [
    'dsn'       => 'pgsql:host=localhost;dbname=test;sslmode=disable',
    'user'      => string, 'pass' => string,
    'logging'   => array /* default=null */,
    'profiling' => bool /* default=false */,
]

// Sample cache options.
'cache' => [
    'id' => string, 'options' => [
        'id'     => ?string,
        'ttl'    => ?int, /* default=60 */
        'agent'  => 'file|apcu|redis|memcached',
        'static' => bool /* default=true */,
    ]
]
```

*Note: As you may guess, all these properties are app-wide global properties. So, if any specific `database` or `cache` is necessary, they could be created and used in-place.*

### Caching operations

Application's cache is a global approach to caching operations and these operations can be done by the following two methods. In any case where another caching approach (or [agent](//github.com/froq/froq-cache/tree/master/src/agent)) is needed, `froq\cache\CacheFactory` can be used.

```php
// Set a cache item.
$this->app->cache(key, value, ?ttl);

// Get a cache item.
value = $this->app->cache(key);

// Delete a cache item or all items.
$this->app->uncache(key);
$this->app->uncache('*');
```

### Config operations
Froq! keeps all the (app) configuration options read-only, meaning that, all options can only be set via `app/config/config.php` file but are accessible application-wide via `$app->config()` method.

```php
// Set an option in config.php file.
'allowedMimeTypes' => ['image/jpeg', 'image/jpe', 'image/jpg']

// Get/use an option.
public function uploadAction() {
    $allowedMimeTypes = $this->app->config('allowedMimeTypes');
    $uploadedFileMimeType = /* Resolved or received MIME type. */;

    if (!array_contains($allowedMimeTypes, $uploadedFileMimeType)) {
        throw new UnsupportedMediaTypeException(); /* froq\http\exception\client */
        // throw new HttpException(code: 415);     /* Alternative: froq\http */
        // throw $this->createHttpException(415);  /* Alternative: froq\app\Controller. */
    }
}
```

### Logging operations
General / arbitrary logging operations can be done with `$app->log()` method (uses `$app->logger` property, an instance of `froq\log\Logger`) or with `$app->logger` property directly. If any other specific logger is required, againg `froq\log\Logger` class or an external class can be used in case.

*Note: While the default app's log level is `LogLevel::ALL`, it can also be changed in configuration file via `log.level` option or `$app->logger->setLevel()` method using `froq\log\LogLevel` (pseudo-enum) class constants ([source](//github.com/froq/froq-log/blob/master/src/LogLevel.php)).*

```php
public function loginAction() {
    [$username, $password] = $this->postParams(['username', 'password']);

    // Login helper (just an example).
    if (!Login::execute($username, $password)) {
        $this->app->log(format(
            'Failed login attempt, username: %s, password: %s, ip: %s',
            $username, $password, $this->request->client->getIp()
        ));

        // Or directly.
        // $this->app->logger->logInfo('Failed login attempt, ...');

        throw new UnauthorizedException();        /* froq\http\exception\client */
        // throw new HttpException(code: 401);    /* Alternative: froq\http */
        // throw $this->createHttpException(401); /* Alternative: froq\app\Controller. */
    }
}
```

### Registering & using services
While services can be registered via `app/confing/services.php` file, that also can be done `$app->service()` method. But the thing is, if you don't use a main controller (e.g. `AppController` extended by all other related controllers), it's useless and you'd better use the services file only. Otherwise, we're going with `AppController` example here, but also going to see getting / using registered services.

```php
// AppController
public function init() {
    // Add a callable returning string.
    $this->app->service('hello', static function ($name) {
        return format('Hello, %s', $name);
    });


    // Add a callable returning an object instance.
    $this->app->service('hello', static function ($name) {
        return new \app\library\Hello($name);
    });
}

// Other controller.
public function saveAction() {
    /** @var callable */
    $hello = $this->app->service('hello');
    $result = $hello($this->getParam('name'));

    /** @var \app\library\Hello */
    $hello = $this->app->service('hello')($this->getParam('name'));
    $result = $hello->greet();
}
```

### Route (micro) methods
The `froq\App` class comes with some micro methods to provide these `GET`, `POST`, `PUT`, `DELETE` route definitions easy-in-short without using `app/config/routes.php` file, and this methods must be called in `pub/index.php` file inside of `prepare()` part. For other any specific HTTP-method related (e.g: `PATCH`) routes can be defined via `route()` method.

*Note: Every given callable is binded to `froq\app\Controller` class, meaning that, `$this` variable is available as an instance of this class inside of these callables.* <br>
*Note: Not everytime a closure callable is needed for a callback routine, meaning that, you can still declare controller classes and given their actions as callables.*

**Simple example**
```php
// A route with controller action.
$app->get('/book/:id', 'Book.show');

// A route with closure callable.
$app->get('/book/:id', function ($id) { ... });

// Another HTTP-method route.
$app->route('/book/:id', 'PATCH', function ($id) { ... });
```

**Detailed example**
```php
// File: pub/index.php
use froq\http\response\Status;

// Up to you.
use app\entity\Book;
use app\service\BookService;

...
->prepare(function ($app) {
    ...
    BookService::setApp($app);

    // Send a Book.
    $app->get('/book/:id', function (int $id): void {
        /** @var Book|null */
        $book = BookService::findBook($id);

        $this->response->json(
            $book?->isFound() ? Status::OK : Status::NOT_FOUND,
            ['data' => ['book' => $book], 'error' => null]
        );
    });

    // Create a Book.
    $app->post('/book', function (): void {
        /** @var array */
        $data = $this->request->json();

        /** @var Book|null */
        $book = BookService::saveBook($data, $error);

        $this->response->json(
            $book?->isSaved() ? Status::CREATED : Status::INTERNAL_ERROR,
            ['data' => ['book' => $book], 'error' => $error]
        );
    });

    // Update a Book.
    $app->put('/book/:id', function (int $id): void {
        /** @var array */
        $data = $this->request->json();
        $data['id'] = $id;

        /** @var Book|null */
        $book = BookService::saveBook($data, $error);

        $this->response->json(
            $book?->isSaved() ? Status::ACCEPTED : Status::INTERNAL_ERROR,
            ['data' => ['book' => $book], 'error' => $error]
        );
    });

    // Delete a Book.
    $app->delete('/book/:id', function (int $id): void {
        /** @var Book|null */
        $book = BookService::removeBook($id);

        $this->response->json(
            $book?->isRemoved() ? Status::OK : Status::NOT_FOUND,
            ['data' => ['book' => $book], 'error' => null]
        );
    });
})
```

*Note: If you don't want to push all these rotuing stuff into `pub/index.php` file, you can separate each related routing file and include them into index file.*

```php
...
->prepare(function ($app) {
    ...

    // Shortcut routes.
    include 'book.php';
    include 'book_author.php';
})
```
