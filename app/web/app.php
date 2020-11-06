<?php

$checkIfDisabled = function () {
    if (file_exists(__DIR__ . '/app-disabled')) {
        for ($i = 0; $i <= 20; $i++) {
            sleep(1);
            if (!file_exists(__DIR__ . '/app-disabled')) {
                break;
            }
        }
        if (file_exists(__DIR__ . '/app-disabled')) {
            header('HTTP/1.1 503 Service Temporarily Unavailable');
            header('Status: 503 Service Temporarily Unavailable');
            header('Retry-After: ' . (60 * 2));
            readfile(__DIR__ . '/disabled.html');
            exit;
        }
    }
};
$checkIfDisabled();

use Symfony\Component\HttpFoundation\Request;

/**
 * @var Composer\Autoload\ClassLoader
 */
$loader = require __DIR__ . '/../app/autoload.php';

// Enable APC for autoloading to improve performance.
// You should change the ApcClassLoader first argument to a unique prefix
// in order to prevent cache key conflicts with other applications
// also using APC.
/*
$apcLoader = new Symfony\Component\ClassLoader\ApcClassLoader(sha1(__FILE__), $loader);
$loader->unregister();
$apcLoader->register(true);
*/

//require_once __DIR__.'/../app/AppCache.php';

$kernel = new AppKernel('prod', false);
//$kernel = new AppCache($kernel);

// When using the HttpCache, you need to call the method in your front controller instead of relying on the configuration parameter
//Request::enableHttpMethodParameterOverride();
$request  = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
