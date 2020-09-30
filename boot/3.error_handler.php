<?php
/**
 * This file defines a custom error handling function that is used by
 * the Tank Engine. If the Tank Engine class is available, errors are
 * handled by that, otherwise, PHPs normal error handler will be called.
 */

set_error_handler("te_error_handler");

//customized error handler
function te_error_handler($errno, $errstr, $errfile, $errline)
{
 //if the tank_engine class is available, run the error through that. Otherwise,
 //use PHP's default handler
 if(class_exists("tank_engine"))
 {
   tank_engine::throw(ERROR,"Error [$errno]: $errstr on line $errline in $errfile");
   return true;
 }
 else
 {
   return false;
 }
}
