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
    $router->get('/', function () {
        $result = [
            'version' => env('APP_VERSION')
        ];
        return \App\Helpers\AppHelper::success($result);
    });

//    $router->group(['prefix' => 'user'], function () use ($router) {
//        $router->get('signup', 'UserController@signup');
//        $router->get('signin', 'UserController@signin');
//    });

    $router->get('init', 'MainController@init');

    $router->get('status', 'MainController@status');

    $router->get('date', 'MainController@date');

    $router->get('currency', 'MainController@currency');

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

    $router->get('nassaab', 'MainController@nassaab');

    $router->get('filimo', 'MainController@filimo');

    $router->group(['prefix' => 'cinema'], function () use ($router) {
        $router->get('/', 'CinemaController@home');
        $router->get('search', 'CinemaController@search');
        $router->get('movie', 'CinemaController@get');
    });
});
