# View [THE_SOURCE_CODE](//github.com/froq/froq/blob/master/src/app/View.php)

Since the PHP language is already capable of working as a template engine itself and contains useful keywords (such as `if/endif`, `for/endfor` etc.) just like template engines use, Froq! doesn't bring or use any _exotic_ template stuff (like _blade_, _sharp_, _lord of the templates_ etc.) that makes these template files simply complicated, but provides a dead-simple view interface for rendering PHP files to HTML outputs.

To use this feature, `view` options in config file must be provided and in each controller that'll use a view file must have `$useView` property as `true` (default `false`, so for saving some OS resource & thinking that 99% of an API controllers won't use a view, just like a session).

### View options
* `layout`: to echo all rendered view file content into (required).
* `base`: to put all view files into, otherwise each view file will be addressed in `app/system/<Name-Of-Controller>/view/<name-of-file>.php` (optional).

<br class="sep">

*Note: To echo the rendered content into layout file can only be done `$CONTENT` variable, and this variable name must not be used when sending data to the view file calling `$this->view()` or $this->view->setData() methods.*

As an example, this site's view config is like;

```php
'view' => [
    'layout' => APP_DIR . '/app/system/view/layout.php',
    'base'   => APP_DIR . '/app/system/view'
]
```

And layout file is like;

```php
<!DOCTYPE html>
<html>
<head>
    ...
</head>
<body>
    <div class="head wrap">
        ...
    </div>
    <div class="body wrap">
        <div class="main">
            <div><?= $CONTENT ?></div>
        </div><!-- .main -->

        <div class="side">
            ...
        </div><!-- .side -->
    </div>
    <div class="foot wrap">
        ...
    </div>
</body>
</html>
```

### Rendering view files
To render a view file can be done using a controller's `view()` method, optionally `$data` and `$status` arguments, but also `froq\app\View::render()` method can be used directly with `$file` and `$fileData` arguments.

```php
// File: app/controller/UserController.php
class UserController extends Controller {
    public bool $useSession = true;
    public bool $useView = true;

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
        $status = $loginFailed ? Status::FORBIDDEN : Status::OK;

        return $this->view('login', ['login_failed' => $loginFailed], $status);
    }
}
```

```php
// File: app/system/view/login.php
<?php if ($login_failed): ?>
    <div class="error">Invalid login.</div>
<?php endif ?>
```

### Data methods
Although all data can be sent at the calltime of `view()` method, it can also be done `setData()` method. And getting a data field is done by `getData()` method.

```php
// Send data to a view file.
$this->view(file: a_file_name, data: an_assoc_array|null, status: a_valid_http_status|null);

// Verbosive way of setting view data.
$this->view->setData('key', $value);

// Get a data field.
$value = $this->view->getData('key', default: null);
```

### Layout methods
To change the layout file per controller or action, you can use `setLayout()` method.

```php
$this->view->setLayout('path/to/layout.php');

$layout = $this->view->getLayout();
```
