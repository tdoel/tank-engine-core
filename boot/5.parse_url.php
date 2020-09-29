<?php
/**
 * This file parses the url that is put in $_GET["page"] by Apache
 * (this is configured in .htaccess).
 */

//first, get the url from $_GET
if(isset($_GET["page"]))
{
  $url = filter_var($_GET["page"],FILTER_SANITIZE_URL);

  //trim URL of possible leading /
  if(substr($url,0,1) == "/")
  {
    $url = substr($url,1);
  }

  $url_parts = explode("/",$url,3);

  //check if the url accidently was index.php (in that case, the default /home/index is fine)
  if($url_parts[0] != "index.php" && $url_parts[0] != "")
  {
    //otherwise, assign the controller, action and possible data this object
    $tank_engine->str_controller = $url_parts[0];

    if(isset($url_parts[1]))
    {
      $tank_engine->str_action = $url_parts[1];
      if(isset($url_parts[2]))
      {
        $tank_engine->args = $url_parts[2];
      }
    }
  }
}
