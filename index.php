<?php

require_once __DIR__ . '/app-core/config/env.php';

/*
|--------------------------------------------------------------------------
| Error Handling
|--------------------------------------------------------------------------
*/

if (defined('APP_DEBUG') && APP_DEBUG === 'true') {

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

} else {

    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL);
}

/*
|--------------------------------------------------------------------------
| Path Constants
|--------------------------------------------------------------------------
| BASE_PATH    : folder app-core/ (source code; diblokir total via .htaccess
|                di dalamnya, walau secara fisik tetap di dalam public_html
|                karena keterbatasan shared hosting yang tidak bisa custom
|                document root)
| APP_PATH     : folder app/ (controllers, models, views, dst)
| PUBLIC_PATH  : document root (ini __DIR__, sama dengan public_html)
| UPLOADS_PATH : folder uploads/ di document root (perlu diakses via URL)
*/

define('BASE_PATH', __DIR__ . '/app-core');
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', __DIR__);
define('UPLOADS_PATH', __DIR__ . '/uploads');

/*
|--------------------------------------------------------------------------
| Load Config
|--------------------------------------------------------------------------
*/

$config = require BASE_PATH . '/config/app.php';
$db     = require BASE_PATH . '/config/database.php';

/*
|--------------------------------------------------------------------------
| Autoload
|--------------------------------------------------------------------------
*/

spl_autoload_register(function ($class) {

    $map = [
        'App\\Core\\'        => APP_PATH . '/core/',
        'App\\Controllers\\' => APP_PATH . '/controllers/',
        'App\\Models\\'      => APP_PATH . '/models/',
        'App\\Middleware\\'  => APP_PATH . '/middleware/',
    ];

    foreach ($map as $prefix => $dir) {

        if (str_starts_with($class, $prefix)) {

            $relative = substr($class, strlen($prefix));

            $file = $dir . str_replace('\\', '/', $relative) . '.php';

            if (is_file($file)) {
                require $file;
                return;
            }
        }
    }
});

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

foreach (['auth', 'url', 'format', 'upload', 'face', 'policy'] as $helper) {
    require APP_PATH . '/helpers/' . $helper . '.php';
}

/*
|--------------------------------------------------------------------------
| Use Statements
|--------------------------------------------------------------------------
*/

use App\Core\App;
use App\Core\Database;
use App\Core\Router;
use App\Core\Session;

/*
|--------------------------------------------------------------------------
| Boot Application
|--------------------------------------------------------------------------
*/

Session::start($config);

Database::init($db);

$router = new Router();

require BASE_PATH . '/routes/web.php';

App::run($router, $config);
