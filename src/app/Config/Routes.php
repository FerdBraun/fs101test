<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'CommentController::index');
$routes->post('comments', 'CommentController::create');
$routes->post('comments/delete/(:num)', 'CommentController::delete/$1');
