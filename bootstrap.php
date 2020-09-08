<?php
/*
This file is required by index.php.
It loads all classes that are required to run the Tank Engine, and defines some
core functions.
*/

//define the document root (parent directory of current script)
define("TE_DOCUMENT_ROOT",dirname(__DIR__));

//add custom defined error handler and autoloader to php
set_error_handler("te_error_handler");
spl_autoload_register("te_autoload");

//look into the config directories in framework and application. If the same file
//is present in both directories, only include the application version.
//files are loaded on alphabetical order
$basenames = [];

foreach (glob(TE_DOCUMENT_ROOT . "/framework/config/*.php") as $path)
{
  $basenames[basename($path)] = "framework";
}
foreach (glob(TE_DOCUMENT_ROOT . "/application/config/*.php") as $path)
{
  $basenames[basename($path)] = "application";
}
//$basenames now contains an array of all files in the config directories. If both
//application and framework version of a file are available, it points to the application version.

//sort the basenames array to its keys, such that files will be included in alphabetical order
ksort($basenames);

//perform the actual includes.
foreach ($basenames as $basename => $path_prefix)
{
  include TE_DOCUMENT_ROOT . "/" . $path_prefix . "/config/" . $basename;
}

//initialize Tank Engine class
$tank = new tank_engine($routes);

/* below the function definitions of the Tank Engine core functions*/

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

//autoloader for files, such as config
function te_require_once($file)
{
  //try to load the application version
  $path = TE_DOCUMENT_ROOT . "/application/" . $file;
  if(file_exists($path))
  {
    require_once $path;
    return true;
  }

  //try to load the framework version
  $path = TE_DOCUMENT_ROOT . "/framework/" . $file;
  if(file_exists($path))
  {
    require_once $path;
    return true
  }

  //neither framework nor application available, throw an error
  trigger_error("[Tank Engine] File $file was not available in either application or framework dir.");
  return false;
}
function te_require($file)
{
  $file_loaded = false;

  //try to load the framework version
  $path = TE_DOCUMENT_ROOT . "/framework/" . $file;
  if(file_exists($path))
  {
    require $path;
    $file_loaded = true;
  }

  //try to load the application version
  $path = TE_DOCUMENT_ROOT . "/application/" . $file;
  if(file_exists($path))
  {
    require $path;
    $file_loaded = true;
  }

  if(!$file_loaded)
  {
    //neither framework nor application available, throw an error
    trigger_error("[Tank Engine] File $file was not available in either application or framework dir.");
  }
}
function te_get_absolute_path($file)
{
  //check if the application version is available
  $path = TE_DOCUMENT_ROOT . "/application/" . $file;
  if(file_exists($path))
  {
    return $path;
  }

  //check if the framwork version is available
  $path = TE_DOCUMENT_ROOT . "/framework/" . $file;
  if(file_exists($path))
  {
    return $path;
  }

  return false;
}

//checks if either an application version OR a framework version of a file is available
function te_file_exists($file)
{
  //check if the framwork version is available
  $path = TE_DOCUMENT_ROOT . "/framework/" . $file;
  if(file_exists($path))
  {
    return true;
  }

  //check if the application version is available
  $path = TE_DOCUMENT_ROOT . "/application/" . $file;
  if(file_exists($path))
  {
    return true;
  }

  return false;
}

//autoloader for classes
function te_autoload($class_name)
{
  //all file_names are lowercase
  $class_name = strtolower($class_name);

  //filter out controllers and models first
  if(strpos($class_name,"controller_") === 0)
  {
    $relative_path = "/controllers/".$class_name.".php";
  }
  elseif(strpos($class_name,"model_") === 0)
  {
    $relative_path = "/models/".$class_name.".php";
  }
  else
  {
    $relative_path = "/classes/".$class_name.".class.php";
  }

  //try application
  $path = TE_DOCUMENT_ROOT . "/application" . $relative_path;
  if(file_exists($path))
  {
    require_once $path;
    return;
  }

  //try framework
  $path = TE_DOCUMENT_ROOT . "/framework" . $relative_path;
  if(file_exists($path))
  {
    require_once $path;
    return;
  }

  //if neither of those work, throw an exception
  trigger_error("[Tank Engine] The class ".$class_name." was requested, but its file could not be found.",E_USER_WARNING);
}

?>
