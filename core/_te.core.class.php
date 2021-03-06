<?php
class _te
{
  public $request_url = "";
  public $str_controller = "";
  public $str_action = "";
  public $args = null;
  public $handle_request_counter = 0;
  public $handle_request_maximum = 10;
  public $route_authorization = 0;
  public $user_authorization = 0;

  public $config = [];

  public $user = null;
  public $css = [];
  public $js = [];
  public $messages = [];

  //initialize the TE object
  public function initialize()
  {
    $this->do_preliminaries();
    $this->lookup_config();
    $this->interpret_config();
    $this->lookup_routes();
    $this->lookup_request_url();
    $this->parse_request_url();

    $this->add_default_resources();

    $this->handle_request();
  }

  // do some 'preliminiary' stuff, such as starting a session
  public function do_preliminaries()
  {
    session_start();
  }

  //load the config.ini file
  public function lookup_config()
  {
    $this->config = te_parse_ini_file("config/config.ini",true);
  }

  //interpret the config file by setting appropriate properties
  public function interpret_config()
  {
    if(isset($this->config["default_routes"]["controller"]))
    {
      $this->str_controller = $this->config["default_routes"]["controller"];
    }
    if(isset($this->config["default_routes"]["action"]))
    {
      $this->str_action = $this->config["default_routes"]["action"];
    }
    if(isset($this->config["default_routes"]["data"]))
    {
      $this->data = $this->config["default_routes"]["data"];
    }

    if(isset($this->config["url"]["root"]))
    {
      $this->url_root = $this->config["url"]["root"];
    }

    //in case default routes for access denied are not specified, make them
    //equal to default routes
    if(isset($this->config["default_routes"]["controller"]) && !isset($this->config["default_routes_access_denied"]["controller"]))
    {
      $this->config["default_routes_access_denied"]["controller"] = $this->config["default_routes"]["controller"];
    }
    if(isset($this->config["default_routes"]["action"]) && !isset($this->config["default_routes_access_denied"]["action"]))
    {
      $this->config["default_routes_access_denied"]["action"] = $this->config["default_routes"]["action"];
    }
    if(isset($this->config["default_routes"]["data"]) && !isset($this->config["default_routes_access_denied"]["data"]))
    {
      $this->config["default_routes_access_denied"]["data"] = $this->config["default_routes"]["data"];
    }
  }

  //retreive the request url (typically generated by a rewrite rule in .htaccess)
  public function lookup_request_url()
  {
    if(isset($_GET["page"]))
    {
      //sanitize
      $url = filter_var($_GET["page"],FILTER_SANITIZE_URL);

      //trim leading or ending slashes
      $url = trim($url,"/");

      $this->request_url = $url;
    }
    else
    {
      $this->request_url = "";
    }
  }

  //load the routes.ini file
  public function lookup_routes()
  {
    $this->routes = te_parse_ini_file("config/routes.ini",true);
  }

  //parse request url
  public function parse_request_url()
  {
    $url_parts = explode("/",$this->request_url,3);

    //check if the url accidently was index.php (in that case, the default route
    // is fine.
    if($url_parts[0] != "index.php" && $url_parts[0] != "")
    {
      //otherwise, assign the controller, action and possible data this object
      $this->str_controller = $url_parts[0];

      if(isset($url_parts[1]))
      {
        $this->str_action = $url_parts[1];
        if(isset($url_parts[2]))
        {
          $this->args = $url_parts[2];
        }
      }
    }
  }

  public function handle_request()
  {
    //check timeout
    $this->handle_request_counter++;

    if($this->handle_request_counter > $this->handle_request_maximum)
    {
      throw new te_runtime_error("Request overflow; _te::handle_request() possibly stuck in a loop. Maybe the default route is forbidden or inacccesible?");;
    }

    //check if the user is authorized for the requested route
    if($this->redirect_if_not_authorized())
    {
      //if he is not, rerun the handle_request() routine with the updated route
      $this->handle_request();
      return;
    }

    /*first check if controller exists, then if this controller has existing
     *action. Throw fatal erros if that is not the case. In the end, call the
     *action at the controller. The action must return an array containing the
     *view that should be loaded, and optional data.
     */

    $controller_classname = "controller_".$this->str_controller;
    try
    {
      $this->controller = new $controller_classname;
      if($this->controller->action_exists($this->str_action))
      {
        //call action, it will return a reply
        $this->reply = $this->controller->{$this->str_action}($this->args);

        if($this->reply instanceof te_redirect)
        {
          // the controller decided a redirect was neccesary (typically outside)
          // of current controller. Update controller, action and data fields,
          // rerun handle_request() accordingly
          $this->str_controller = $this->reply->controller;
          $this->str_action = $this->reply->action;
          $this->args = $this->reply->args;

          $this->handle_request();
          return;
        }
      }
      else
      {
        throw new te_runtime_error("Action ". $this->str_action . " does not exist in " . $this->str_controller . " controller.");
      }
    }
    catch (te_runtime_error $e)
    {
      //check if this is a controller not found error
      if ($e->getCode() == 5)
      {
        //controller does not exist
        //check if it was unset
        if ($this->str_controller == "")
        {
          throw new te_runtime_error("The controller was not set, and a default route was not available or equal to ''",0,$e);
        }
        else
        {
          throw new te_runtime_error("The controller class could not be found",0,$e);
        }
      }
      else
      {
        throw $e;
      }
    }

    try
    {
      $this->reply->render_reply();
    }
    catch (te_runtime_exception $e)
    {
      //unhandled runtime exception
      if($e instanceof te_runtime_error)
      {
        //fatal, print all messages and rethrow error
        $this->render_messages();
        throw $e;
      }
      else
      {
        //nonfatal, add error to the message list
        $this->add_message("error",$e->getMessage());
      }
    }

    //as final step, check if there are any messages still pending in
    //$this->messages. They are not printed yet, so this should still be done
    if(count($this->messages) > 0)
    {
      $this->render_messages();
    }

  }

  public function redirect($controller = "", $action = "", $args = "")
  {
    return new te_redirect($controller, $action, $args);
  }

  //validate if the current user is authorized for the requested route, redirect
  //if neccesary
  public function redirect_if_not_authorized()
  {
    $this->lookup_user_authorization();
    $this->lookup_route_authorization();

    if(!$this->is_authorized())
    {
      //redirect to default route
      $this->str_controller = $this->config["default_routes_access_denied"]["controller"];
      $this->str_action = $this->config["default_routes_access_denied"]["action"];
      $this->data = $this->config["default_routes_access_denied"]["data"];

      $this->add_message("warning","You are not authorized to view this page");

      return true;
    }

    //return false to indicate no redirection has taken place
    return false;
  }
  public function is_authorized()
  {
    return ($this->user_authorization <= $this->route_authorization);
  }
  public function lookup_user_authorization()
  {
    if($user = $this->get_user())
    {
      //check if admin attribut is set and evaluates to true
      if(isset($user->admin) && $user->admin)
      {
        //logged in, admin
        $this->user_authorization = 0;
      }
      else
      {
        //logged in, no admin
        $this->user_authorization = 1;
      }
    }
    else
    {
      //not logged in
      $this->user_authorization = 2;
    }
  }
  public function lookup_route_authorization()
  {
    if(isset($this->routes[$this->str_controller][$this->str_action]))
    {
      $this->route_authorization = $this->routes[$this->str_controller][$this->str_action];
    }
    else
    {
      //default: ADMIN only
      $this->route_authorization = 0;
    }
  }

  //set a user object
  public function set_user($user)
  {
    $this->user = $user;
    $_SESSION["user_id"] = $user->id;
    $_SESSION["user_class"] = get_class($user);
  }
  //get a user if it is set, return false otherwise
  public function get_user()
  {
    if($this->user)
    {
      return $this->user;
    }
    elseif (isset($_SESSION["user_id"]))
    {
      $classname = $_SESSION["user_class"];
      $this->user = new $classname($_SESSION["user_id"]);
      return $this->user;
    }
    else
    {
      return false;
    }
  }
  public function user_log_out()
  {
    $this->user = null;
    unset($_SESSION["user_id"]);
    unset($_SESSION["user_class"]);
    session_destroy();
  }

  //add css / js resp. to the reply
  public function add_css($css)
  {
    if(!filter_var($css, FILTER_VALIDATE_URL))
    {
      // $css points to a local resource
      $css = $this->url_root."/application/css/".$css.".css";
    }
    //add the full CSS url if it is not already included
    if (!in_array($css, $this->css))
    {
      $this->css[] = $css;
    }
  }
  public function add_js($js)
  {
    if(!filter_var($js, FILTER_VALIDATE_URL))
    {
      // $css points to a local resource
      $js = $this->url_root."/application/js/".$js.".js";
    }
    //add the full JS url if it is not already included
    if (!in_array($js, $this->js))
    {
      $this->js[] = $js;
    }
  }
  public function add_default_resources()
  {
    //adds the default resources

    //separate method for jQuery such that it can easily be overridden by
    //another version
    $this->add_jquery();

    $this->add_css("te.core");
    $this->add_js("te.core");
    $this->add_js("te.jquery.serializejson");
  }
  //add jquery
  public function add_jquery()
  {
    $this->add_js("https://code.jquery.com/jquery-3.5.1.min.js");
  }
  public function render_resources()
  {
    foreach ($this->css as $href)
    {
      echo '<link rel="stylesheet" type="text/css" href="' . $href . '">';
    }
    foreach ($this->js as $href)
    {
      echo '<script src="' . $href . '"></script>';
    }
  }
  public function render_messages()
  {
    foreach ($this->messages as $msg)
    {
      echo '<div class="msg '.$msg[0].'">'.$msg[1].'</div>';
    }
    $this->messages = [];
  }
  public function add_message($type, $text = "")
  {
    $this->messages[] = [$type, $text];
  }
}
