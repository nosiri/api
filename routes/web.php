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

$router->group(['middleware' => ['auth', 'validator']], function () use ($router) {
    $router->get('/', function () use ($router) {
        return $router->app->version();
    });

    $router->group(['prefix' => 'user'], function () use ($router) {
        $router->get('signup', ['middleware' => 'auth'], 'UserController@signup');
        $router->get('signin', ['middleware' => 'auth'], 'UserController@signin');
    });

    $router->get('date', function () {
        return response()->json(['a' => time()]);
    });

    $router->get('dollar', function () {

    });

    $router->get('proxy', function () {

    });

    $router->get('soundcloud/{link}', function ($link) {

    });

    $router->get('youtube/{link}', function ($link) {

    });

    $router->get('translate/{text}', function ($text) {

    });

    $router->get('npm/{query}', function ($query) {

    });

    $router->get('packagist/{query}', function ($query) {

    });

    $router->get('gravatar/{email}', function ($email) {

    });

    $router->get('bankDetector/{card: [0-9]{16}}', function ($cardNumber) {

    });

    $router->get('word/{word}', function ($word) {

    });

    $router->get('omen[/{id: [0-9]{3}}]', function ($omenId) {

    });

    $router->get('emamsadegh/{name}', function ($name) {

    });

    $router->get('weather/{location}', function ($location) {

    });

    $router->get('dns/{domain}', function ($domain) {

    });

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
