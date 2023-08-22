# Repository [THE_SOURCE_CODE](//github.com/froq/froq/blob/master/src/app/Repository.php)

All repositories must extend `froq\app\Repository` class that comes some handy *final* method and *protected* properties.

These properties are `$db` (instance of `froq\database\Database`) for query and CRUD operations and `$em` (instance of `froq\database\entity\EntityManager`) for entity operations.

Here we'll see some basic examples of using repositories, but not go to details since [database](/docs/database) and [entities](/docs/database-entities) are explained there.

```php
// File: app/repository/BookRepository.php
namespace app\repository;

use froq\app\Repository;
use froq\database\query\QueryParams;

class BookRepository extends Repository {
    public function find(int $id): ?array {
        return $this->initQuery()
                    ->select('*')
                    ->from('books')
                    ->where('id', [$id])
                    ->get();
    }

    public function findAll(QueryParams $qp, ?int $page = null, int $limit = 10): ?array {
        return $this->initQuery()
                    ->select('*')
                    ->from('books')
                    ->where($qp)
                    ->order('id', -1)
                    ->paginate($page, $limit)
                    ->getAll();
    }
}

// File: app/controller/BookController.php
namespace app\controller;

use froq\app\Controller;
use froq\http\response\Status;

class BookController extends Controller {
    public bool $useRepository = true;

    public function showAction(int $id) {
        $book = $this->repository->find($id);
        $status = $book ? Status::OK : Status::NOT_FOUND;

        return $this->view('show', data: ['book' => $book], status: $status);
    }

    public function searchAction() {
        $qp = $this->repository->initQueryParams();

        /** @var UrlQuery|null (sugar) */
        if ($q = $this->request->query()) {
            $page = (int) $q->get('page');

            $q->has('isbn') && $qp->addIn('isbn', $q->get('isbn'));
            $q->has('price') && $qp->addBetween('price', $q->get('price'));
            // ...
        }

        $books = $this->repository->findAll($qp, $page ?? null);
        $status = $books ? Status::OK : Status::NOT_FOUND;

        return $this->view('search', data: ['books' => $books], status: $status);
    }
}
```



<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>



### Checking URI root (base) & environment

```php
public function fooAction() {
    // true (if root is "/" as default or defined like "/api/v1").
    assert($this->app->isRoot());
    assert($this->app->root === '/');

    // true (if app is running on PHP's built-in server or a server like "foo.local").
    assert($this->app->isLocal());
    assert($this->app->env === \froq\Env::DEVELOPMENT);
}
```

### Accessing / using `Logger`, `Request`, `Response` properties

```php
public function fooAction() {
    // Level is LogLevel::ALL as default (can be changed calling $logger->setLevel()).
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

These properties will only be created automatically by given configuration options in `app/config/config.php` file and accessible from all over the controllers (e.g. `$this->app->session`).

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
$this->app->cache(key);

// Delete a cache item or all items.
$this->app->uncache(key);
$this->app->uncache('*');
```

### Config operations

Froq! makes all the (app) configuration options as read-only, meaning that, all options can be set only via `app/config/config.php` file but are accessible all over the application instance.

```php
// Set an option in config.php file.
'allowedMimeTypes' => ['image/jpeg', 'image/jpe', 'image/jpg']

// Get/use an option.
public function uploadAction() {
    $allowedMimeTypes = $this->app->config('allowedMimeTypes');
    $uploadedFileMimeType = /* Resolved or received MIME type. */;

    if (!array_contains($uploadedFileMimeType, ...$allowedMimeTypes)) {
        throw new UnsupportedMediaTypeException(); /* froq\http\exception\client */
        // throw new HttpException(code: 415);        /* Alternative: froq\http */
        // throw new $this->createHttpException(415); /* Alternative: froq\app\Controller. */
    }
}
```

### Logging operations

General logging operations can be done with `App::log()` method (uses `$app->logger`) or with `$app->logger` property directly. If any other specific logger is required, `froq\log\Logger` can be used in case.

```php
public function loginAction() {
    [$username, $password] = $this->postParams(['username', 'password']);

    // Login helper is just an example.
    if (!Login::execute($username, $password)) {
        $this->app->log(format(
            'Failed login attemp, username: %s, password: %s, ip: %s',
            $username, $password, $this->request->client->getIp()
        ));

        // Or directly.
        // $this->app->logger->logInfo('Failed login attepmt, ...');

        throw new UnauthorizedException(); /* froq\http\exception\client */
        // throw new HttpException(code: 401);        /* Alternative: froq\http */
        // throw new $this->createHttpException(401); /* Alternative: froq\app\Controller. */
    }
}
```

### Registering & using services

While services can be registered via `app/confing/services.php` file, that also can be done `App::service()` method. But the thing is, if you don't use a main controller (e.g. `AppController` extended by all other related controllers), it's useless and you'd better use the services file only. Otherwise, we're going with `AppController` example here, but also going to see getting / using registered services.

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

