<?php
class te_redirect
{
  public $controller = "";
  public $action = "";
  public $args = null;

  public function __construct($controller = "", $action = "", $args = "")
  {
    //change unset variables to their specified values
    global $te;
    if($controller == "")
    {
      $controller = $te->config["default_routes"]["controller"];
    }
    if($action == "")
    {
      $action = $te->config["default_routes"]["action"];
    }
    if($args == "")
    {
      $args = $te->config["default_routes"]["data"];
    }

    $this->controller = $controller;
    $this->action = $action;
    $this->args = $args;
  }
}
