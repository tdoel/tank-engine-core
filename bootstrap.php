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

    global $te_loaded_classes;
    echo "<p>
    <h4>Stacktrace</h4>
    </p>
    <style>td {padding-right: 10px;}</style>
    <table><tr>
    <td>File</td>
    <td>Line</td>
    <td>Class</td>
    <td>Function</td>
    <td>Class used</td>
    </tr>";
    $trace = $exception->getTrace();
    for($i = -1; $i < count($trace); $i++)
    {
      if (isset($trace[$i+1]["class"]) && isset($te_loaded_classes[$trace[$i+1]["class"]]) && $te_loaded_classes[$trace[$i+1]["class"]] == "application")
      {
          echo '<tr style="font-weight:bold;">';
      }
      else
      {
        echo "<tr>";
      }
      echo "<td>".(isset($trace[$i]["file"]) ? $trace[$i]["file"] : ($i == -1 ? $exception->getFile() : "-"))."</td>";
      echo "<td>".(isset($trace[$i]["line"]) ? $trace[$i]["line"] : ($i == -1 ? $exception->getLine() : "-"))."</td>";
      echo "<td>".(isset($trace[$i+1]["class"]) ? $trace[$i+1]["class"] : "-")."</td>";
      echo "<td>".(isset($trace[$i+1]["function"]) ? $trace[$i+1]["function"] : "-")."</td>";
      echo "<td>".(isset($trace[$i+1]["class"]) && isset($te_loaded_classes[$trace[$i+1]["class"]]) ? $te_loaded_classes[$trace[$i+1]["class"]] : "-")."</td>";
      echo "</tr>";
    }
    echo "</table>";
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

//a list of all loaded classes, that holds whether the $framework or application
//version was loaded. Used by the general exception handler for making the
//stack trace more insightful.
$te_loaded_classes = [];

//autoloader for classes
function te_autoload($class_name)
{
  global $te_loaded_classes;

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

    //register class as 'application version loaded'
    $te_loaded_classes[$class_name] = "application";

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

    //regiser class as 'framework version loaded'
    $te_loaded_classes[$class_name] = "framework";

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
function te_parse_ini_file($path, $process_sections = false , $scanner_mode = INI_SCANNER_NORMAL)
{
  $ini = [];
  $file_found = false;

  $framework_path = TE_DOCUMENT_ROOT . "/framework/" . $path;
  $application_path = TE_DOCUMENT_ROOT . "/application/" . $path;

  if(file_exists($framework_path))
  {
    $file_found = true;
    $ini = array_merge($ini, parse_ini_file($framework_path, $process_sections, $scanner_mode));
  }
  if(file_exists($application_path))
  {
    $file_found = true;
    $ini = array_merge($ini, parse_ini_file($application_path, $process_sections, $scanner_mode));
  }

  if(!$file_found)
  {
    throw new te_runtime_warning("INI file '".$path."' requested but it could not be found");
  }

  return $ini;
}
