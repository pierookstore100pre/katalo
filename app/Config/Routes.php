<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

$routes->get('api/ping', 'Api\Ping::index');

$routes->get('c/imagen/(:num)', 'Catalogo\Imagen::ver/$1');
$routes->post('c/(:segment)/pedido', 'Catalogo\Pedido::crear/$1');
$routes->get('c/(:segment)/productos', 'Catalogo\Catalogo::productos/$1');
$routes->get('c/(:segment)', 'Catalogo\Catalogo::index/$1');

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

    $routes->get('productos', 'Negocio\Productos::index');
    $routes->get('productos/crear', 'Negocio\Productos::crear');
    $routes->post('productos/crear', 'Negocio\Productos::guardar');
    $routes->get('productos/editar/(:num)', 'Negocio\Productos::editar/$1');
    $routes->post('productos/editar/(:num)', 'Negocio\Productos::actualizar/$1');
    $routes->post('productos/ocultar/(:num)', 'Negocio\Productos::ocultar/$1');
    $routes->post('productos/reactivar/(:num)', 'Negocio\Productos::reactivar/$1');
    $routes->post('productos/agotar/(:num)', 'Negocio\Productos::agotar/$1');
    $routes->post('productos/reactivar-estado/(:num)', 'Negocio\Productos::reactivarEstado/$1');

    $routes->get('productos/(:num)/imagenes', 'Negocio\Imagenes::index/$1');
    $routes->post('productos/(:num)/imagenes', 'Negocio\Imagenes::subir/$1');
    $routes->post('productos/(:num)/imagenes/(:num)/principal', 'Negocio\Imagenes::principal/$1/$2');
    $routes->post('productos/(:num)/imagenes/(:num)/eliminar', 'Negocio\Imagenes::eliminar/$1/$2');

    $routes->get('pedidos', 'Negocio\Pedidos::index');
    $routes->get('pedidos/(:num)', 'Negocio\Pedidos::ver/$1');
    $routes->post('pedidos/(:num)/estado', 'Negocio\Pedidos::cambiarEstado/$1');

    $routes->get('mi-plan', 'Negocio\MiPlan::index');
    $routes->post('mi-plan/upgrade/(:num)', 'Negocio\MiPlan::solicitarUpgrade/$1');
});

$routes->group('admin', ['filter' => ['auth', 'admin']], static function (RouteCollection $routes): void {
    $routes->get('pagos', 'Admin\Pagos::index');
    $routes->get('pagos/registrar/(:num)', 'Admin\Pagos::registrar/$1');
    $routes->post('pagos/registrar/(:num)', 'Admin\Pagos::guardar/$1');
    $routes->post('pagos/(:num)/confirmar', 'Admin\Pagos::confirmar/$1');
    $routes->post('pagos/(:num)/rechazar', 'Admin\Pagos::rechazar/$1');
});
