<?php
/**
 * This file starts a session and checks if any errors are present in the session
 * variable (which may occur on redirects).
 */
session_start();

//if we arrive via a redirect, some messages may be stored in SESSION
if(isset($_SESSION["errors"]))
{
  tank_engine::$errors = $_SESSION["errors"];
  //clear session errs.
  unset($_SESSION["errors"]);
}
