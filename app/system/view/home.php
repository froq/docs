<div class="home">
    <h2>Welcome</h2>
    <p>Froq! is a PHP framework that helps you quickly write simple yet powerful web applications and APIs with a minimal installation / configuration process. It basically accepts HTTP requests, applies a method / callback routine, and returns HTTP responses.</p>
    <p>Froq! neither follows MVC architecture rules nor contains Model component, but instead, it makes use of Controller, Repository and View components.</p>
    <p>Froq! can also be used as a micro framework as you might be familiar with <a href="//slimframework.com/">Slim</a> framework.</p>

    <br>

    <h2>How does it work?</h2>
    <p>After applying web server configs, Froq! can be installed and run easily. Here is a <code>HelloController</code> example:</p>

    <pre><code class="language-php">
    // app/config/routes.php
    ['/hello/:name', 'Hello.say']

    // app/system/HelloController.php
    namespace app\controller;

    class HelloController extends \froq\app\Controller {
        function sayAction($name) {
            echo "Hello, {$name}!", "\n";
        }
    }
    </code></pre>

    <br>

    <p>And as mentioned before, you can also use Froq! as a micro framework. Just open <code>pub/index.php</code> file and add your routes with callbacks as below:</p>

    <pre><code class="language-php">
    $app->get('/hello/:name', function ($name) {
        echo "Hello, {$name}!", "\n";
    });
    </code></pre>
</div><!-- .home -->
