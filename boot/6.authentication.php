<?php
/* This file checks if the requester is authorized for the requested route
 * and redirects if not.
 * First, get the authorization level of the page that is visited,
 * then that of the user. The latter one should be equal or lower
 * (0 = admin). A routes.php inside a config folder should specify the levels
 */
if(isset($routes[$tank_engine->str_controller][$tank_engine->str_action]))
{
 $page_auth_level = $routes[$tank_engine->str_controller][$tank_engine->str_action];
}
else
{
 $page_auth_level = TE_AUTH_ADMIN;
}

if($user = tank_engine::get_user())
{
 if($user->admin == 1)
 {
   //admin user
   $user_auth_level = TE_AUTH_ADMIN;
 }
 else
 {
   //logged in, but no admin
   $user_auth_level = TE_AUTH_USER;
 }
}
else
{
 $user_auth_level = TE_AUTH_PUBLIC;
}

if($user_auth_level > $page_auth_level)
{
 //user is not allowwed to view page
 tank_engine::throw(WARNING,"You are not authorized to view this page.");
 $tank_engine->str_controller = TE_DEFAULT_CONTROLLER;
 $tank_engine->str_action = TE_DEFAULT_ACTION;
}
