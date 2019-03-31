<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->group(['middleware' => ['auth', 'headers']], function () use ($router) {
    $router->get('test', 'MainController@test');

    $router->get('/', function () use ($router) {
        $result = [
            'version' => env('APP_VERSION')
        ];
        return \App\Helpers\AppHelper::instance()->success($result);
    });

//    $router->group(['prefix' => 'user'], function () use ($router) {
//        $router->get('signup', 'UserController@signup');
//        $router->get('signin', 'UserController@signin');
//    });

    $router->get('init', 'MainController@init');

    $router->get('date', 'MainController@date');

    $router->get('dollar', 'MainController@dollar');

    $router->get('proxy', 'MainController@proxy');

    $router->get('soundcloud', 'MainController@soundcloud');

    $router->get('youtube', 'MainController@youtube');

    $router->get('npm', 'MainController@npm');

    $router->get('packagist', 'MainController@packagist');

    $router->get('gravatar', 'MainController@gravatar');

    $router->get('bankDetector', 'MainController@bankDetector');

    $router->get('dictionary', 'MainController@dictionary');

    $router->get('omen', 'MainController@omen');

    $router->get('emamsadegh', 'MainController@emamsadegh');

    $router->get('weather', 'MainController@weather');

    $router->get('dns', 'MainController@dns');

    $router->get('nassaab', 'MainController@nassaab');

    $router->group(['prefix' => 'filimo'], function () use ($router) {
        $router->get('search', 'FilimoController@search');
        $router->get('get', 'FilimoController@fetch');
        $router->get('user', 'FilimoController@finder');
    });

    $router->group(['prefix' => 'namava'], function () use ($router) {
        $router->get('search', 'NamavaController@search');
        $router->get('get', 'NamavaController@fetch');
    });
});
