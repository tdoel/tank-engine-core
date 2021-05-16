<?php
/**
 * This file is required by index.php.
 * It loads all classes that are required to run the Tank Engine, and defines some
 * core functions.
 */

//define the document root (parent directory of current script)
define("TE_DOCUMENT_ROOT",dirname(__DIR__));

//set a general exception handler
set_exception_handler("te_exception_handler");

//set a general error handler to turn errors into exceptions
set_error_handler("te_error_handler");

//add autoloader to php
spl_autoload_register("te_autoload");

//initialize Tank Engine class
$te = new te();
$te->initialize();

/* below the function definitions of the Tank Engine core functions*/

// general exception handler
function te_exception_handler($exception)
{
  do
  {
    $msg = $exception->getMessage();
    $code = $exception->getCode();
    $file = $exception->getFile();
    $line = $exception->getLine();

    echo "<p>Uncaught exception [$code]: $msg in <b>$file</b> on line <b>$line</b>.</p>";
  }
  while ($exception = $exception->getPrevious());
}
function te_error_handler($severity, $message, $filename, $lineno)
{
    throw new ErrorException($message, 0, $severity, $filename, $lineno);
    //FIXME: this also makes warnings fatal, maybe that's not desired
}

//autoloader for files, such as config
function te_require_once($file)
{
  $file_loaded = false;

  //try to load the framework version
  $path = TE_DOCUMENT_ROOT . "/framework/" . $file;
  if(file_exists($path))
  {
    require_once $path;
    $file_loaded = true;
  }

  //try to load the application version
  $path = TE_DOCUMENT_ROOT . "/application/" . $file;
  if(file_exists($path))
  {
    require_once $path;
    $file_loaded = true;
  }

  if(!$file_loaded)
  {
    //neither framework nor application available, throw an error
    trigger_error("[Tank Engine] File $file was not available in either application or framework dir");
  }
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
    trigger_error("[Tank Engine] File $file was not available in either application or framework dir");
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
  if(is_file($path))
  {
    return true;
  }

  //check if the application version is available
  $path = TE_DOCUMENT_ROOT . "/application/" . $file;
  if(is_file($path))
  {
    return true;
  }

  return false;
}

//autoloader for classes
function te_autoload($class_name)
{
  //all file names are lowercase
  $class_name = strtolower($class_name);

  //prepare error code to fire
  $err_code = 2;

  //filter out controllers and models first
  if(strpos($class_name,"controller_") === 0)
  {
    $relative_path = "/controllers/".$class_name.".php";
    $err_code = 5;
  }
  elseif(strpos($class_name,"model_") === 0)
  {
    $relative_path = "/models/".$class_name.".php";
    $err_code = 4;
  }
  elseif($class_name[0] == "_")
  {
    $relative_path = "/core/".$class_name.".core.class.php";
    $err_code = 3;
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
    //call static constructor if available
    $call = $class_name."::__construct_static";
    if (is_callable($call))
    {
      call_user_func($call);
    }
    return;
  }

  //try framework
  $path = TE_DOCUMENT_ROOT . "/framework" . $relative_path;
  if(file_exists($path))
  {
    require_once $path;
    //call static constructor if available
    $call = $class_name."::__construct_static";
    if (is_callable($call))
    {
      call_user_func($call);
    }
    return;
  }

  //if neither of those work, throw an exception
  throw new te_runtime_error("The class ".$class_name." was requested, but its file could not be found",$err_code);
}
