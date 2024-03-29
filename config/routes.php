<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */

use Hyperf\HttpServer\Router\Router;

Router::get('/get', 'App\Controller\IndexController@get');
Router::get('/upload', 'App\Controller\IndexController@upload');

Router::addServer('ws', function () {
    Router::get('/', 'App\Controller\WebSocketController');
});