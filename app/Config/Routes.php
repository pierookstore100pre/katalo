<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

$routes->get('api/ping', 'Api\Ping::index');

$routes->get('auth/registro', 'Auth\Registro::index');
$routes->post('auth/registro', 'Auth\Registro::guardar');

$routes->get('auth/login', 'Auth\Login::index');
$routes->post('auth/login', 'Auth\Login::autenticar');

$routes->get('auth/logout', 'Auth\Logout::index');

$routes->get('auth/recuperar', 'Auth\RecuperarPassword::index');
$routes->post('auth/recuperar', 'Auth\RecuperarPassword::solicitar');

$routes->get('auth/reset', 'Auth\RecuperarPassword::reset');
$routes->post('auth/reset', 'Auth\RecuperarPassword::actualizar');

$routes->get('dashboard', 'Dashboard::index', ['filter' => 'auth']);
