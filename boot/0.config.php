<?php
/**
 * This file summarizes some common config work you may need to do.
 *
 * It is named 0-config.php to ensure it is always loaded first fro all
 * config files that are available under application/config and framework/config.

 * This file MUST be overridden in the application/boot directory.
 * To do so, copy this file to /application/boot and in that file,
 * modify the constants according to your poject details.
 */

//specify the URL root of your application
define("TE_URL_ROOT","http://wherever.your.site.is.located.nl");

//define default database constants to allow access to the sql database of your project
define("TE_DB_USER","<sql username>");
define("TE_DB_PASS","<sql password>");
define("TE_DB_HOST","127.0.0.1");
define("TE_DB_DB","<sql database>");

//if neccesary, specify the timezone of you project
date_default_timezone_set('Europe/Amsterdam');

//specify a default route
define("TE_DEFAULT_CONTROLLER","home");
define("TE_DEFAULT_ACTION","index");
