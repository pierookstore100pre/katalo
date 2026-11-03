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

$routes->group('negocio', ['filter' => 'auth'], static function (RouteCollection $routes): void {
    $routes->get('perfil', 'Negocio\Perfil::index');
    $routes->post('perfil', 'Negocio\Perfil::guardar');

    $routes->get('categorias', 'Negocio\Categorias::index');
    $routes->get('categorias/crear', 'Negocio\Categorias::crear');
    $routes->post('categorias/crear', 'Negocio\Categorias::guardar');
    $routes->get('categorias/editar/(:num)', 'Negocio\Categorias::editar/$1');
    $routes->post('categorias/editar/(:num)', 'Negocio\Categorias::actualizar/$1');
    $routes->get('categorias/eliminar/(:num)', 'Negocio\Categorias::eliminar/$1');
});
