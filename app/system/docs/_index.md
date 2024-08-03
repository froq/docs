## Installation

### What you'll need:

- · PHP 8.2 or newer.
- · A web server (or just PHP's built-in web server for local developments).
- · An activated URL-Rewrite feature for the web server.

<br class="sep">

### What you'll do:

After creating your project folder, run the following command in:

```none
git clone git@github.com:froq/sample.git . && composer install
```

<br class="sep">

*Note: To manipulate autoloader, change `autoload.psr-4` field in `composer.json` file. Otherwise, with a secondary look-up (as those "or" below indicate), all the autoload operations will be handled like in the example below as default by `froq\Autoloader` class (see `getFile()` source [here](//github.com/froq/froq/blob/master/src/Autoloader.php#L349)), yet depending on their namespaces that start with these three: `app\controller`, `app\repository`, `app\library`.*

```none
Route      : /post (e.g: "api.foo.com/post" requests)

# Controllers.
Controller : app\controller\PostController => app/system/Post/PostController.php

# Repository & other data-related things.
Repository : app\repository\PostRepository => app/system/Post/PostRepository.php
                                           or app/system/Post/data/PostRepository.php
Entity     : app\repository\PostEntity     => app/system/Post/PostEntity.php
                                           or app/system/Post/data/PostEntity.php
EntityList : app\repository\PostEntityList => app/system/Post/PostEntityList.php
                                           or app/system/Post/data/PostEntityList.php
Query      : app\repository\PostQuery      => app/system/Post/PostQuery.php
                                           or app/system/Post/data/PostQuery.php
Search     : app\repository\PostSearch     => app/system/Post/PostSearch.php
                                           or app/system/Post/data/PostSearch.php
Resource   : app\repository\PostResource   => app/system/Post/PostResource.php
                                           or app/system/Post/data/PostResource.php
DTOs       : app\repository\PostDto        => app/system/Post/PostDto.php
                                           or app/system/Post/data/PostDto.php
# Library items.
Library    : app\library\PostHelper        => app/library/PostHelper.php
```

<br class="sep">

### Local Development with Built-in Web Server

```bash
# As current user.
php -S localhost:8080 bin/server.php
# Or with public (static) folder.
php -S localhost:8080 -t pub/ bin/server.php

# As another user.
sudo -u www-data php -S localhost:8080 bin/server.php
# Or with public (static) folder.
sudo -u www-data php -S localhost:8080 -t pub/ bin/server.php
```

## Configuration

Froq! runs almost with 0 option, except needed `routes` options that set in `app/config/routes.php` file and included into `app/config/config.php` file at top.

In this [file](//github.com/froq/sample/blob/master/app/config/config.php), you will find more options that you may want to change by your needs. If you want a *Dot-Env* file for your configurations, use `dotenv` option to address this file.

As an example, here is the `view` options to tell the View component that it will use a directory as base for all view files and a layout file as a main file to print `$CONTENT` into.

```php
'view' => ['base' => APP_DIR . '/app/system/view',
           'layout' => APP_DIR . '/app/system/view/layout.php']
```

## Web Servers

The configurations below are enough for Froq! to run, but you can add more options as needed. Also `/var/www` is just a general path and can be changed as well.

*Note: If you do not want to work completely on PHP's built-in server, remember to modify `/etc/hosts` for local developements (e.g. for `example.com.local`, add this line: `127.0.0.1 example.com.local`). Plus, Froq! automatically detects and sets the application environment just checking TLD. If it is `.local` or `.localhost` it sets `App::$env` as `developement` and both `App::isLocal()` and `__local__`, `__LOCAL__` constants will be `true`. It is also checked if `$_SERVER['SERVER_NAME']` is `localhost` or `127.0.0.1` by the way.*

<br class="sep">

### Nginx

```none
server {
    listen 80;
    server_name example.com;
    root /var/www/example.com/pub;
    index index.php;
    location / {
       try_files $uri $uri/ /index.php?$args;
    }
    # Don't miss PHP options here!
}
```

<br class="sep">

### Apache

```none
<VirtualHost *:80>
    ServerName example.com
    DocumentRoot /var/www/example.com/pub
    <Directory /var/www/example.com/pub>
        Options +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```
