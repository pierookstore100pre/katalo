<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

$routes->get('api/ping', 'Api\Ping::index');

// Endpoint global de imágenes: se mantiene /c/imagen/{id_imagen} porque el
// id de imagen es único y así se evita duplicar la lógica por ciudad.
$routes->get('c/imagen/(:num)', 'Catalogo\Imagen::ver/$1');

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

    $routes->get('logs', 'Negocio\Logs::index');
});

$routes->group('admin', ['filter' => ['auth', 'admin']], static function (RouteCollection $routes): void {
    $routes->get('dashboard', 'Admin\Dashboard::index');

    $routes->get('ciudades', 'Admin\Ciudades::index');
    $routes->post('ciudades/(:num)/activar', 'Admin\Ciudades::activar/$1');
    $routes->post('ciudades/(:num)/desactivar', 'Admin\Ciudades::desactivar/$1');

    $routes->get('distritos', 'Admin\Distritos::index');
    $routes->get('distritos/crear', 'Admin\Distritos::crear');
    $routes->post('distritos/crear', 'Admin\Distritos::guardar');
    $routes->get('distritos/editar/(:num)', 'Admin\Distritos::editar/$1');
    $routes->post('distritos/editar/(:num)', 'Admin\Distritos::actualizar/$1');
    $routes->post('distritos/(:num)/activar', 'Admin\Distritos::activar/$1');
    $routes->post('distritos/(:num)/desactivar', 'Admin\Distritos::desactivar/$1');

    $routes->get('negocios', 'Admin\Negocios::index');
    $routes->get('negocios/(:num)', 'Admin\Negocios::ver/$1');
    $routes->post('negocios/(:num)/suspender', 'Admin\Negocios::suspender/$1');
    $routes->post('negocios/(:num)/reactivar', 'Admin\Negocios::reactivar/$1');

    $routes->get('moderacion', 'Admin\Moderacion::index');
    $routes->post('moderacion/producto/(:num)/aprobar', 'Admin\Moderacion::aprobarProducto/$1');
    $routes->post('moderacion/producto/(:num)/rechazar', 'Admin\Moderacion::rechazarProducto/$1');
    $routes->post('moderacion/categoria/(:num)/aprobar', 'Admin\Moderacion::aprobarCategoria/$1');
    $routes->post('moderacion/categoria/(:num)/rechazar', 'Admin\Moderacion::rechazarCategoria/$1');
    $routes->post('moderacion/negocio/(:num)/aprobar', 'Admin\Moderacion::aprobarNegocio/$1');
    $routes->post('moderacion/imagen/(:num)/aprobar', 'Admin\Moderacion::aprobarImagen/$1');
    $routes->post('moderacion/imagen/(:num)/rechazar', 'Admin\Moderacion::rechazarImagen/$1');

    $routes->get('planes', 'Admin\Planes::index');
    $routes->get('planes/editar/(:num)', 'Admin\Planes::editar/$1');
    $routes->post('planes/editar/(:num)', 'Admin\Planes::actualizar/$1');
    $routes->post('planes/(:num)/activar', 'Admin\Planes::activar/$1');
    $routes->post('planes/(:num)/desactivar', 'Admin\Planes::desactivar/$1');

    $routes->get('configuracion', 'Admin\Configuracion::index');
    $routes->post('configuracion', 'Admin\Configuracion::guardar');

    $routes->get('logs', 'Admin\Logs::index');

    $routes->get('pagos', 'Admin\Pagos::index');
    $routes->get('pagos/registrar/(:num)', 'Admin\Pagos::registrar/$1');
    $routes->post('pagos/registrar/(:num)', 'Admin\Pagos::guardar/$1');
    $routes->post('pagos/(:num)/confirmar', 'Admin\Pagos::confirmar/$1');
    $routes->post('pagos/(:num)/rechazar', 'Admin\Pagos::rechazar/$1');
});

// Catálogo público. Se registra al final para no colisionar con /auth,
// /negocio, /admin, /dashboard ni /c/imagen. Los slugs reservados se
// rechazan también en los controladores.
$routes->get('c/(:segment)', 'Catalogo\Redireccion::index/$1');
$routes->get('(:segment)/(:segment)/productos', 'Catalogo\Catalogo::productos/$1/$2');
$routes->post('(:segment)/(:segment)/pedido', 'Catalogo\Pedido::crear/$1/$2');
$routes->get('(:segment)/(:segment)', 'Catalogo\Catalogo::index/$1/$2');
$routes->get('(:segment)', 'Catalogo\Catalogo::ciudad/$1');
