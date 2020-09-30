<?php
/**
 * This file defines the $roues variable, that indicates which routes require
 * which authentication.
 *
 * This file can (must) be overridden in the application/boot directory.
 * To do so, copy this file to /application/boot and in that file,
 * modify the contents according to your poject details.
 *
 * The syntax is
 * $routes[controller][action] = authorization level;
 *
 * The default constants for this are TE_AUTH_PUBLIC, TE_AUTH_USER and
 * TE_AUTH_ADMIN. The first makes a route accessible to everybody, the
 * second to all logged in users and the third to users who have $user->admin
 * is true. Routes that are not set, are TE_AUTH_ADMIN.
 */

 $routes = [];

 //always allow css, js and images
 $routes["application"]["css"] = TE_AUTH_PUBLIC;
 $routes["application"]["js"] = TE_AUTH_PUBLIC;
 $routes["application"]["img"] = TE_AUTH_PUBLIC;

 //make entry point home/index of project accessible to public
 $routes["home"]["index"] = TE_AUTH_PUBLIC;
