# Controller [#git](//github.com/froq/froq/blob/master/src/app/Controller.php)

All controllers must extend `froq\app\Controller` class that comes with many (frankly, tons of) handy *final* methods and some *readonly* properties.

Probably the most important properties would be `$repository` (created by `$useRepository`), `$session` (created by `$useSession`) and `$view` (created by `$useView`).

The other ones are `$app` (instance of `froq\App`), `$request` (instance of `froq\http\Request`, reference of `$app->request`), `$response` (instance of `froq\http\Response`, reference of `$app->response`) and `$state` (instance of `State`, a dynamic state object).

*Note: All methods in `froq\app\Controller` are declared as `final`, excluding `__construct()` & `__destruct()` methods (so, to skip them, `init()` / `dinit()` methods can be declared in subcontrollers for these two methods as substitutes when needed.*

*Note: All `__construct()` methods available for promoted parameters, and in these methods `parent::__construct()` calls can be skipped. Plus, all promoted parameter types must be a valid / existing class name (primitive types and interfaces aren't supported).*

### Optional `$use*` properties
All these `$use*` properties must be declared in the subcontroller class as `true` (all defaults are `false`) and both `$repository` & `$session` properties and operations are dependent on the related config options (see [here](/docs/app#optional-properties-session-database-cache)).

#### HTML site example
```php
// File: app/system/UserController.php
namespace app\controller;

use froq\app\Controller;
use your\namespace\Login;

class UserController extends Controller {
    public bool $useRepository = true; // Requires "database" configs.
    public bool $useSession = true;    // Requires "session" configs.
    public bool $useView = true;       // Requires "view" configs.

    public function loginAction() {
        if ($this->request->isPost()) {
            [$username, $password] = $this->request->post(['username', 'password']);

            // Do some repository works here.
            $user = $this->repository->findUserByUsername($username);

            // All done within (just an example).
            $okay = Login::execute($user, $password);

            if ($okay) {
                // Store user data if needed.
                $this->session->set('user', $user);

                // Redirect user to the dashboard.
                return $this->response->redirect('/dashboard');
            }

            // To show login error to user.
            $this->session->flash('login_failed', true);
        }

        return $this->view('login');
    }
}
```

#### API site example
```php
// File: app/system/TokenController.php
namespace app\controller;

use froq\app\Controller;
use froq\http\response\Status;
use your\namespace\{Login, Token};

class TokenController extends Controller {
    public bool $useRepository = true; // Requires "database" configs.

    public function tokenAction() {
        [$username, $password] = $this->request->post(['username', 'password']);

        // Do some repository works here.
        $user = $this->repository->findUserByUsername($username);

        // All done within (just an example).
        $okay = Login::execute($user, $password);

        if ($okay) {
            $token = new Token($user);
            $token->persist();

            // Send token payload.
            return $this->jsonPayload(Status::OK, [
                'token'  => $token->getValue(),
                'expiry' => $token->getExpiry()
            ]);
        }

        // Send error payload.
        return $this->jsonPayload(Status::UNAUTHORIZED, [
            'error' => 'Invalid credentials.'
        ]);
    }
}
```

### Constructor property promotion
Constructor parameters can be promoted as a controller's properties while declaring `__construct()` methods. In these methods, `parent::__construct()` call can be skipped.

*Note: All promoted parameter types must be a valid / existing class name, primitive types and interfaces aren't supported and service registry can be used for interface-based dependencies.*

```php
// File: app/system/TokenController.php
namespace app\controller;

use froq\app\Controller;
use app\service\TokenService;

class TokenController extends Controller {
    public function __construct(
        private TokenService $service
    ) {}

    public function tokenAction() {
        [$username, $password] = $this->request->post(['username', 'password']);

        // Do some service / user works here.
        $user = $this->service->findUserByUsername($username);

        // ...
    }
}
```

### Special methods
Top controller class `froq\app\Controller` can recognise and call (invoke) some special methods that declared in subcontrollers and these methods;

* `init()`: called in `__construct()` method.
* `dinit()`: called in `__destruct()` method.
* `before()`: called before a target action is called (not an action but a regular method as public).
* `after()`: called after a target action is called (not an action but a regular method as public).
* `index()`: where the all index stuff operated in a subcontroller (no `Action` prefix needed / allowed).
* `error()`: where the all error stuff operated in a subcontroller (no `Action` prefix needed / allowed).

<br class="sep">

*Note: For a proper error handling process, `error()` method **must be declared** as per subcontroller or as once in a main subcontroller that's extended by other subcontrollers. Otherwise, in the absence of this method and in the time of error, you may not get any output response but can see the errors in log files.*

#### Error handling with `error()` method
If any internal error occurs, `error()` method is called with one argument (an instance of `Throwable`). The other calls depend on developers and must be declared keeping that `Throwable` class type in mind.

```php
// File: app/system/AppController.php
namespace app\controller;

use froq\app\Controller;
use froq\http\HttpException;
use froq\http\response\Status;
use your\namespace\{
    DatabaseException,
    RecordValidationException,
    RecordDuplicationException
};
use Throwable, SomeError, SomeOtherError;

class AppController extends Controller {
    // Basic example.
    public function error(Throwable $e) {
        switch (true) {
            case ($e instanceof SomeError):
                // Handle some error.
                break;
            case ($e instanceof SomeOtherError):
                // Handle some other error.
                break;
            default:
                // Handle default situation.
        }
    }

    // Complex example (a bit).
    public function error(Throwable|string $e) {
        $status = null;

        if ($e instanceof HttpException) {
            $code = $e->getCode();
            if ($code >= 400 && $code <= 599) {
                $status = $code;
            }
        } elseif ($e instanceof DatabaseException) {
            // Mock errors thrown in repositories.
            $status = match (true) {
                $e instanceof RecordValidationException
                    => Status::BAD_REQUEST,
                $e instanceof RecordDuplicationException
                    => Status::CONFLICT,
                default
                    => null
            };
        }

        // Set internal as default.
        $status ??= Status::INTERNAL;
    }
}

// File: app/system/TokenController.php
namespace app\controller;

...
use froq\http\exception\client\{
    BadRequestException,
    NotFoundException
};

class TokenController extends AppController {
    // All these exceptions below are gonna be caught
    // in App::run() & sent to AppController::error().
    public function tokenAction() {
        [$username, $password] = $this->postParams(['username', 'password'], trim: true);

        if (!$username || !$password) {
            throw new BadRequestException();
        }

        $user = $this->repository->findUserByUsername($username);

        if (!$user) {
            throw new NotFoundException();
        }
    }
}
```

### Internal HTTP-related errors
Some `$e` instances coming as argument on `error()` can be caused by *No ... found* flavoured problems, for example, when no route, no controller / controller route or no action / action route found. If this the is case, then `$e` argument will be an instance of `froq\AppException` containing `$cause` property which is most probably an instance of `froq\http\exception\client\NotFoundException` for no route / controller / action found errors, or an instance of `froq\http\exception\client\NotAllowedException` for a route defined only with a request method like `GET`, `GET|POST` etc., and `getCause()` method as a getter method for `$cause` property.

For more error / exception source, you can see [Thrownable](//github.com/froq/froq-common/blob/master/src/interface/Thrownable.php) / [ThrownableTrait](//github.com/froq/froq-common/blob/master/src/trait/ThrownableTrait.php) source files.

*Note: `NotFoundException` exception can be thrown at the call of controller's `forward()`, `call()` and `callCallable()` methods if any not-found related error occurs during the runtime / calltime.* <br>
*Note: `NotAllowedException` class is alias of `froq\http\exception\client\MethodNotAllowedException` class.*

```php
// Re-writing by the description above.
public function error(Throwable $e) {
    $status = null;

    if ($e instanceof AppException) {
        $cause = $e->getCause();
        switch (true) {
            case ($cause instanceof NotFoundException):
                $status = $e->getCode();
                // And do some work by that.
                break;
            case ($cause instanceof NotAllowedException):
                $status = $e->getCode();
                // And do some work by that too.
                break;
            // ...
        };
    }

    // Set internal as default.
    $status ??= Status::INTERNAL;
}
```

### All-in-one API example with error handling

```php
// File: app/system/ApiController.php
namespace app\controller;

use froq\AppException;
use froq\app\Controller;
use froq\http\response\Status;
use froq\http\response\payload\JsonPayload;
use froq\http\exception\client\{NotFoundException, NotAllowedException};
use Throwable;

class ApiController extends Controller {
    // Error procedure.
    public final function error(Throwable $e): JsonPayload {
        $error = $status = null;

        if ($e instanceof AppException) {
            $cause = $e->getCause();
            switch (true) {
                case ($cause instanceof NotFoundException):
                    $error  = ['code' => 'URI_NOT_FOUND', 'text' => 'URI not found.'];
                    $status = $e->getCode();
                    break;
                case ($cause instanceof NotAllowedException):
                    $error  = ['code' => 'METHOD_NOT_ALLOWED', 'text' => 'Method not allowed.'];
                    $status = $e->getCode();
                    break;
                // ...
            };
        }

        // Defaults.
        $error  ??= ['code' => 'INTERNAL', 'text' => 'Internal error'];
        $status ??= Status::INTERNAL;

        // Return, cos it's used as return in error process.
        return $this->send($status, error: $error);
    }

    // Payload procedure.
    public final function send(int $status, array $data = null, array $error = null): JsonPayload {
        return new JsonPayload($status, content: [
            'status' => $status, 'data' => $data, 'error' => $error
        ]);
    }
}

// File: app/system/TokenController.php
namespace app\controller;

use froq\http\response\Status;
use your\namespace\{Login, Token};

class TokenController extends ApiController {
    public bool $useRepository = true;

    public function tokenAction() {
        [$username, $password] = $this->postParams(['username', 'password']);

        // Do some repository works here.
        $user = $this->repository->findUserByUsername($username);

        // All done within (just an example).
        $okay = Login::execute($user, $password);

        if ($okay) {
            // Cache or save etc.
            $token = new Token($user);
            $token->persist();

            // Send token payload.
            return $this->send(Status::OK, data: [
                'token'  => $token->getValue(),
                'expiry' => $token->getExpiry()
            ]);
        }

        // Send error payload.
        return $this->send(Status::UNAUTHORIZED, error: [
            'code' => 'INVALID_CREDENTIALS',
            'text' => 'Invalid credentials.'
        ]);
    }
}
```

A sample payload would probably be like the following examples, for both success / failure situations.

```js
// Success.
{
    "status": 200,
    "data": {
        "token": "VhPKPwQ5X7JFWueQAFgclER6zTUd8gKBYx3boEx5aI7bQtx ...",
        "expiry": "2023-08-17T21:03:01+00:00",
    },
    "error": null
}

// Failure.
{
    "status": 401,
    "data": null,
    "error": {
        "code": "INVALID_CREDENTIALS",
        "text": "Invalid credentials."
    }
}
```

### Getter methods
Although some properties are public, they also can be retrieved via related methods like;

```php
// Property getters.
$repository = $controller->getRepository();     // ?object(froq\app\Repository)
$session    = $controller->getSession();        // ?object(froq\session\Session)
$view       = $controller->getView();           // ?object(froq\app\View)

// Others (e.g. PostController).
$controller->getName();                         // "app\controller\PostController"
$controller->getShortName();                    // "Post"
$controller->getShortName(suffix: true);        // "PostController"
$controller->getActionName());                  // "showAction"
$controller->getActionShortName());             // "show"
$controller->getActionShortName(suffix: true)); // "showAction"
$controller->getCall();                         // Post.show
$controller->getCall(full: true);               // app.controller.Post.showAction
```

### Working with parameters

There are several ways to work with parameters in Froq!'s both HTTP & Controller system. One of them is **path parameters**, and the other ones are basically `$_GET`, `$_POST` etc. diallers that allow you to access & use in a comfy way enabling `...$options` arguments that can take some callables (`trim`, `map`, `filter` and `combine`) to retrieve these parameters as callable-applied, plus **segment parameters** as well.

#### Path parameters

Path parameters are retrieved in calltime of any path (route), passed to the action that being called in a controller at that time and can be used as typed or non-typed in that action method.

*Note: Each parameter must have same name in both route config & method declaration, plus only `int|float|string|bool` types are operated as parameters type.*

```php
// File: app/controller/PostController.php
// Route: /post/show/:id

// With type declaration.
public function showAction(int $id) {
    // ...
}

// Without type declaration.
public function showAction($id) {
    $id = (int) $id;
    // ...
}
```

#### Path parameter methods

```php
/* Single parameters. */
$controller->hasActionParam(name: 'id');                // true|false
$controller->getActionParam(name: 'id', default: null); // "123"|123|null
$controller->setActionParam(name: 'id', value: 123);    // in case of manipulation

/* Multiple parameters. */
// true if all set or, if names null and any param set
$controller->hasActionParams(names: []|null);
// array of given names' values, array of all params if names null, combine for name/value pairs
$controller->getActionParams(names: []|null, defaults: []|null, combine: false);
// in case of manipulation, params are name/value pairs
$controller->setActionParams(params: []);
```

#### Global `$_GET`, `$_POST`, `$_COOKIE` parameter methods

*Note: All JSON (`/json` containing) requests are automatically parsed to `$_POST` global.*

```php
/* Get parameters. */
$controller->getParam('name', default: null);
$controller->getParams(['name'], defaults: []|null);

/* Post parameters. */
$controller->postParam('name', default: null);
$controller->postParams(['name'], defaults: []|null);

/* Cookie parameters. */
$controller->cookieParam('name', default: null);
$controller->cookieParams(['name'], defaults: []|null);
```

#### Segment methods

Although, that segments property is defined as `$request->uri->segments` and ready-to-use after parsing the URI path, it can be utilised in controller instances too with the methods below (see [Segments](//github.com/froq/froq/blob/master/src/http/request/Segments.php)).

```php
// Route call: /post/show/123
// No index 0: / 1 / 2 / 3

/* Single parameters. */
// "123"
$controller->segment('show', default: null);
// "123" (mind argument 3, as int and real index)
$controller->segment(3, default: null);

/* Multiple parameters. */
// ["123"]
$controller->segments(['show'], defaults: []|null);
// ["123"] (mind argument [3], as [int] and real indexes)
$controller->segments([3], defaults: []|null);
// object(froq\http\request\Segments)
$controller->segments();

/* Named parameters. */
// "123"
$controller->segmentParam('show', default: null);
// ["123"]
$controller->segmentParams(['show'], defaults: []|null);
```

### Injections
While calling the target action, it's possible to inject some objects as action parameters at calltime if they meet the types below and have no NULL defaults.

· Request / Response: `froq\http\Request` and `froq\http\Response`. <br>
· Payloads: `froq\http\request\payload\FormPayload` (for form data), `froq\http\request\payload\JsonPayload` (for JSON data), `froq\http\request\payload\FilePayload` (for a single uploaded file), `froq\http\request\payload\FilesPayload` (for all uploaded files). <br>
· DTO / VO Objects: Driven from `froq\app\data\DataObject` or `froq\app\data\ValueObject` class. <br>
· Input Objects (simple DTOs): Driven from `froq\app\data\InputInterface` interface. <br>
· Entities: Driven from `froq\database\entity\Entity` class. <br>
· All other valid / existing classes.

#### Request & Response
Froq! aims to provide a smooth HTTP interaction to its users (developers) so they can enjoy while they're coding their projects, and to realise that, it brings two components named as `froq\http\Request`, `froq\http\Response` and equipped with many useful properties / methods. You can find more details about [Request](/docs/http-request) and [Response](/docs/http-response) documents.

#### Injecting `Request` & `Response` objects
It's easy to inject these objects into an action in-place and on-demand by declaring these actions with the arguments that typed as `froq\http\Request` and/or `froq\http\Response`. While these arguments will automatically be passed to the action (that being called), the other arguments will also be passed to that action (regardless of their place / order).

Although you can inject these objects into any action, they're just references of `$request` and `$response` properties of controllers and instead of injecting them, you can also use them just like `$this->request` or `$this->response` in controllers.

```php
use froq\http\{Request, Response};

// With path params (e.g: GET /some/:id).
public function someAction(int $id, Request $request, Response $response) {
    $content = [
        'id' => $id,
        'request_time' => $request->time,
        'request_utime' => $request->utime,
    ];

    // ...
}

// Without path params (e.g: GET /some?id=123).
public function someAction(Request $request, Response $response) {
    $content = [
        'id' => (int) $request->get('id'),
        'request_time' => $request->time,
        'request_utime' => $request->utime,
    ];

    // ...
}
```

#### Injecting payload objects
All payload objects proceeded by their relevant content type headers. So, `FilePayload` and `FilesPayload` need `multipart/form-data`,
`FormPayload` needs `/x-www-form-urlencoded` or `multipart/form-data`, and `JsonPayload` needs any content type ending with `/json` (e.g: `application/json`).

Note: You can use `$payload->okay` property to check whether the relevant content type was okey'ed by the target payload class at creation time.

```php
use froq\http\response\Status;
use froq\http\request\payload\{FormPayload, JsonPayload, FilePayload, FilesPayload};

// A form data dependent action.
public function formAction(FormPayload $form) {
    if (!$form->okay) {
        throw $this->createHttpException(415);
        // throw $this->createHttpException(Status::UNSUPPORTED_MEDIA_TYPE);
        // throw new \froq\http\exception\client\UnsupportedMediaTypeException();
    }

    $id = $form->get('id');
    [$id, $name] = $form->getAll(['id', 'name']);

    // Access.
    $id = $form['id'];

    if (isset($form['id'])) {
        // Proceed..
    }

    if (count($form)) {
        // Proceed..
    }

    $data = [];
    foreach ($form as $key => $value) {
        $data[$key] = escape($value);
    }

    // ReadonlyError!
    $form['id'] = 123;

    // ...
}

// A JSON data dependent action.
public function jsonAction(JsonPayload $json) {
    // Same as formAction(), but needs a JSON content type header,
    // such as "application/json", "text/json" etc.
}

// A file/files dependent action.
public function uploadAction(FilePayload $file, FilesPayload $files) {
    // Single file.
    if ($file->exists()) {
        if ($file->mime == 'image/jpeg') {
            $to = format('/path/to/images/%s.jpg', $file->generateId('uuid'));
            $file->move($to);
        } else {
            // Proceed..
        }

        // With options for more safety (@see froq\file\upload\Source).
        $options = ['allowedMimes' => 'image/jpeg', 'allowedExtensions' => 'jpg,jpeg'];

        try {
            $file->move($to, $options);
        } catch (Throwable $e) {
            // Proceed..
        }
    }

    if ($files->count()) {
        foreach ($files as $file) {
            // Same as above.
        }
    }
}
```

#### Injecting DTO / VO objects

```php
use app\data\{UserDTO, UserVO};

// A DTO dependent action.
public function addAction(UserDTO $user) {
    if ($user->validate()) {
        // Proceed..
    } else {
        // Error..
    }
}

// A VO dependent action.
public function addAction(UserVO $user) {
    // Proceed..
}
```

#### Injecting Input objects (simple DTOs)
Population data comes from `$_POST` for POST, PUT, PATCH methods, and for all other methods it comes from `$_GET` data. All path params will be used to map DTO objects, so some global params won't be used if they're already presented in path params (`$mappingData = $pathParams + $_POST`).

```php
class BookDto implements \froq\app\data\InputInterface {
    public ?int $id;
    public ?string $name;
    // ...

    // Simple validation.
    public function isValid(): bool {
        return !empty($this->name);
    }
}
```
```php
// @call POST /book
public function addAction(BookDto $book) {
    assert($book->id === null);

    if ($book->isValid()) {
        $book = $this->repository->add((array) $book);
        // ...
    }
}

// @call PUT /book/:id
public function editAction(int $id, BookDto $book) {
    assert($book->id === $id);

    if ($book->isValid()) {
        $book = $this->repository->edit($id, (array) $book);
        // ...
    }
}
```

#### Injecting Entity objects

```php
use app\entity\User;

// An entity dependent action.
public function addAction(User $user) {
    try {
        $user->save();
    } catch (Throwable) {
        // Proceed..
    }
}
```

#### Injecting other objects

```php
use app\library\Hello;
use app\repository\BookRepository;

// Inject a regular object.
public function helloAction(Hello $hello) {
    echo $hello->say();
}

// Inject a repository object.
public function booksAction(BookRepository $repository) {
    $books = $repository->findAll();
    return $this->view('books', ['books' => $books]);
}
```

#### Getting GET|POST|COOKIE params
There are several ways of getting request parameters, and this can be done using methods below that basically utilise `fetch()` method of `froq\http\request\Params` class ([source](//github.com/froq/froq/blob/master/src/http/request/Params.php)).

_Note: All uses are same for `get*`, `post*` and `cookie*` methods of both controller and request objects._ <br>
_Note: When a parameter isn't set, provided `map` or other callables won't be applied and `null` or given default will be returned._

```php
public function someAction() {
    /* Controller methods. */
    $param = $this->getParam('param', default: null);
    [$param1, $param2] = $this->getParams(['param1', 'param2'], default: null);

    $trimmedParam = $this->getParam('param', default: '', trim: true);
    $trimmedParam = $this->getParam('param', default: '', map: 'trim');

    // Safe example (e.g. "id=%20%20%00123").
    $id = $this->getParam('id', map: 'trim|int'); // int(123)
    // Complex example (e.g. "ids=1,2,3").
    $ids = $this->getParam('ids', map: fn($s) => map(split(',', $s), 'int')); // array<int>[1,2,3]

    /* Nulls, defaults & callables (map, filter etc). */
    // null if no $_GET['id'] isn't set (like no ?id=...).
    $id = $this->getParam('id', map: 'trim|int');
    // -1 if no $_GET['id'] isn't set (like no ?id=...).
    $id = $this->getParam('id', default: -1, map: 'trim|int');

    // The rest of same.
    $this->postParam(...); $this->postParams(...);
    $this->cookieParam(...); $this->cookieParams(...);


    /* Request methods. */
    $id = $this->request->get('id', default: null, map: 'int');
    [$id, $name] = $this->request->get(['id', 'name'], default: null, trim: true);

    // The rest of same.
    $this->request->post(...);  $this->request->cookie(...);
    $this->request->getParam(...); $this->request->getParams(...);
    $this->request->postParam(...); $this->request->postParams(...);
    $this->request->cookieParam(...); $this->request->cookieParams(...);

    // These are explainded above already.
    $this->request->segmentParam(...); $this->request->segmentParams(...);
}
```

### Sending response & payloads
You can send any type of response by using `froq\Controller::response()` method.

```php
use froq\http\response\Status;
// ...

public function someAction() {
    // Send a plain / json text payload.
    return $this->response(Status::OK, 'Hello, world!', ['type' => 'text/plain']);
    return $this->response(Status::OK, ['msg' => 'Hello, world!'], ['type' => 'text/json']);

    // Send (display) an image payload (size & modifiedAt auto-detect if none).
    $attributes = [
        'type' => 'image/jpeg', 'size' => 1024,  // In bytes, like filesize().
        'modifiedAt' => 'unix-time or iso-date', // For headers.
        'expiresAt' => 'unix-time or iso-date',  // For headers.
        'etag' => 'f5a1bffbf0ae28c7558792d ...'  // For headers.
    ];
    return $this->response(Status::OK, 'path/to/file.jpeg', $attributes);
    // return $this->response(Status::OK, 'contents/of/file.jpeg', $attributes);
}
```

Plus, thanks to Froq! [HTTP Payload](/docs/http-payloads) components, you can easily send payloads with a status code (HTTP code) and attributes (e.g. `type` for content type or `charset` for content charset etc.) and its content as well.

_Note: Payloads are simply read-only objects, so you can only set (give) their data for once since they've no setters._

```php
use froq\http\response\Status;
use froq\http\response\payload\{
    // Available payload classes.
    Payload,
    HtmlPayload, PlainPayload
    JsonPayload, XmlPayload
    ImagePayload, FilePayload
};
// ...

public function someAction() {
    // Parameters are same as the response() method above.
    return $this->payload(Status::OK, 'Hello, world!', ['type' => 'text/plain']);
    // return new Payload(Status::OK, 'Hello, world!', ['type' => 'text/plain']);

    return $this->htmlPayload(Status::OK, '<p>Hello, world!</p>');
    // return new HtmlPayload(Status::OK, '<p>Hello, world!</p>');

    // So on ...
}
```

### Flashes
To keep and use some casual / temporary data in sessions, you can use `froq\app\Controller::flash()` method that's basically a proxy method of `froq\session\Session::flash()` method.

_Note: Including `flash()` method, all session features will require `session` options in config file._

```php
public function loginAction() {
    if ($this->request->isPost()) {
        // ...

        if ($loginIsNotOkay) {
            // Set login error.
            $this->flash('login_failed', true);

            return $this->redirect('/login');
        }
    }

    // Get login error (if exists).
    $loginFailed = $this->flash('login_failed');

    return $this->view('login', ['login_failed' => $loginFailed]);
}
```

### Forward / redirect
To forward, actuall to call an other controller's method, you can use `froq\app\Controller::forward()` method by passing the target method with its arguments if any.

_Note: The `forward()` method is for only action calls, meaning that, excluding `index()` and `error()` methods, an `Action` suffix will be added to the given target method, and also `Controller` suffix to its controller._

```php
public function someAction() {
    // For FooController::barAction().
    $return = $this->forward('Foo.bar');
}
```

To redirect the client to another URI / URL, you can use `froq\app\Controller::redirect()` method that's basically a proxy method of `froq\http\Response::redirect()` method.

```php
public function someAction() {
    // Simple usage.
    return $this->redirect('/another-target');

    // Complete usage.
    return $this->redirect(
        '/another-target?id=%d',
        [123],                  // For format('...?id=%d').
        code: Status::FOUND,    // 3xx HTTP code.
        body: '...',            // Just in case.
        headers: ['k/v pairs'], // HTTP headers to send with direction.
        cookies: ['k/v pairs'], // HTTP cookies to send with direction.
    );
}
```

### Misc. methods

```php
// Initialize a controller instance.
$someController = $this->initController('Some');
$someController = $this->initController('some\Some');

// Initialize a repository instance.
$someRepository = $this->initRepository('Some');
$someRepository = $this->initRepository('some\Some');

// Create an HTTP exception with code.
$e = $this->createHttpException(401);
$e = $this->createHttpException(Status::UNAUTHORIZED);
```
