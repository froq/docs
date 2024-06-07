<div class="home">
    <h2>Welcome</h2>

    <p>Froq! is a PHP framework that helps you quickly create simple yet powerful web applications and APIs with a minimal installation / configuration process. It basically accepts HTTP requests, applies some method / callback routines, and returns HTTP responses.</p>

    <p>Froq! neither follows MVC architecture rules nor contains a Model component, but instead, it makes use of Controller, Repository and View components.</p>

    <p>Froq! can also be used as a micro framework that you might be familiar with <a href="//slimframework.com/">Slim</a> framework.</p>

    <br>

    <h2>How does it work?</h2>

    <p>After applying web server configurations, Froq! can be installed and run easily. Here is a <code>HelloController</code> example:</p>

    <pre><code class="language-php">
    // app/config/routes.php
    ['/hello/:name', 'Hello.say']

    // app/system/HelloController.php
    namespace app\controller;

    class HelloController extends \froq\app\Controller {
        function sayAction($name) {
            echo "Hello, ", escape($name), "!\n";
        }
    }
    </code></pre>

    <br>

    <p>And as mentioned before, you can also use Froq! as a micro framework. Just open <code>pub/index.php</code> file and add your routes with callbacks as below:</p>

    <pre><code class="language-php">
    $app->get('/hello/:name', function ($name) {
        echo "Hello, ", escape($name), "!\n";
    });
    </code></pre>

    <br>

    <p><em>Note: The <code>escape()</code> function doesn't exist in Froq! but is used there just because to emphasise that the <code>$name</code> variable is an external input (path / segment parameter).</em></p>
</div><!-- .home -->
