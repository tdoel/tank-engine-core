<?php
/**
 * This file defines a number of constants used by the Tank Engine
 */

//define error levels:
define("NOTICE","notice");
define("WARNING","warning");
define("ERROR","error");

//these constants are used to define the various authorization levels
define("TE_AUTH_ADMIN",0);
define("TE_AUTH_USER", 1);
define("TE_AUTH_PUBLIC",2);

//this piece defines the default route
$tank_engine->str_controller = TE_DEFAULT_CONTROLLER;
$tank_engine->str_action = TE_DEFAULT_ACTION;
