<?php
declare(strict_types=1);

defined('BASEPATH') or exit('No direct script access allowed');

$route['default_controller'] = 'authcontroller/login';
$route['404_override'] = '';
$route['translate_uri_dashes'] = false;

$route['auth/login'] = 'authcontroller/login';
$route['auth/logout'] = 'authcontroller/logout';
$route['auth/register'] = 'authcontroller/register';
$route['auth/forgot'] = 'authcontroller/forgotPassword';
$route['auth/reset/(:any)'] = 'authcontroller/resetPassword/$1';
$route['auth/lock'] = 'authcontroller/lockSession';
$route['auth/unlock'] = 'authcontroller/unlockSession';
$route['auth/otp'] = 'authcontroller/manageOtp';
$route['auth/otp/enable'] = 'authcontroller/enableOtp';
$route['auth/otp/disable'] = 'authcontroller/disableOtp';
$route['auth/otp/verify'] = 'authcontroller/verifyLoginOtp';

$route['admin'] = 'admincontroller/index';
$route['admin/users'] = 'admincontroller/users';
$route['admin/users/create'] = 'admincontroller/createUser';
$route['admin/users/edit/(:any)'] = 'admincontroller/editUser/$1';
$route['admin/users/delete/(:any)'] = 'admincontroller/deleteUser/$1';
$route['admin/roles'] = 'admincontroller/roles';
$route['admin/roles/create'] = 'admincontroller/createRole';
$route['admin/roles/edit/(:any)'] = 'admincontroller/editRole/$1';
$route['admin/roles/delete/(:any)'] = 'admincontroller/deleteRole/$1';
