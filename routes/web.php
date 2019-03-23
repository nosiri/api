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

$router->group(['middleware' => ['auth']], function () use ($router) {
    $router->get('/', function () use ($router) {
        return response()->json(['status' => true, 'version' => 1]);
    });

    $router->group(['prefix' => 'user'], function () use ($router) {
        $router->get('signup', 'UserController@signup');
        $router->get('signin', 'UserController@signin');
    });

    $router->get('date', 'ResponseController@date');

    $router->get('dollar', 'ResponseController@dollar');

    $router->get('proxy', 'ResponseController@proxy');

    $router->post('soundcloud', 'ResponseController@soundcloud');

    $router->post('youtube', 'ResponseController@youtube');

    $router->post('translate', 'ResponseController@translate');

    $router->post('npm', 'ResponseController@npm');

    $router->post('packagist', 'ResponseController@packagist');

    $router->post('gravatar', 'ResponseController@gravatar');

    $router->post('bankDetector', 'ResponseController@bankDetector');

    $router->post('dictionary', 'ResponseController@dictionary');

    $router->post('omen', 'ResponseController@omen');

    $router->post('emamsadegh', 'ResponseController@emamsadegh');

    $router->post('weather', 'ResponseController@weather');

    $router->post('dns', 'ResponseController@dns');

    $router->group(['prefix' => 'filimo'], function () use ($router) {
        $router->get('search/{query}', function ($query) {
            // Matches The "/filimo/search/{something}" URL
        });
        $router->get('get/{id}', function ($id) {
            // Matches The "/filimo/get/{something}" URL
        });
    });

    $router->group(['prefix' => 'namava'], function () use ($router) {
        $router->get('search/{query}', function ($query) {
            // Matches The "/namava/search/{something}" URL
        });
        $router->get('get/{id}', function ($id) {
            // Matches The "/namava/get/{something}" URL
        });
    });
});
