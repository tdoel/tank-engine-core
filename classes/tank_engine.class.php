<?php
class tank_engine
{
  private static $css = [];
  private static $js = [];

  public static $errors = [];
  public static $errors_printed = false;
  public static $check_errors_printed = true;

  private static $user = null;

  public static $title = "Tank Engine";

  public $str_controller = "";
  public $str_action = "";
  public $args = null;

  public function serve_request()
  {
    /*first check if controller exists, then if this controller has existing
     *action. Throw fatal erros if that is not the case. In the end, call the
     *action at the controller. The action must return an array containing the
     *view that should be loaded, and optional data.
     */
     $controller_classname = "controller_".$this->str_controller;
     if(class_exists($controller_classname))
     {
       $this->controller = new $controller_classname;
       if($this->controller->action_exists($this->str_action))
       {
         //call action, it will return a reply
         $this->reply = $this->controller->{$this->str_action}($this->args);
       }
       else {
         static::throw(ERROR, "Action ". $this->str_action . " does not exist in " . $this->str_controller . " controller.");
       }
     }
     else
     {
       //controller undefined
       static::throw(ERROR, "Controller " . $this->str_controller . " does not exist.");
     }

     $this->render_reply();

     //if no errors were printed, let it know here
     if(!static::$errors_printed && static::$check_errors_printed && $this->reply instanceOf te_view)
     {
       static::throw(ERROR,'Errors thrown using the Tank Engine class are not reported. Either include a tank_engine::render_errors() somewhere in your view, or suppress this message by including a tank_engine::$check_errors_printed = false to any config file.');
       $this->render_errors();
     }
  }

  public function render_reply()
  {
    if($this->get_err_level() < 3)
    {
      //no fatal errors, continue with replying
      if(!$this->reply->render_reply())
      {
        //fatal error occured during rendering
        $this->render_errors();
      }
    }
    else
    {
      //fatal errors occured, print them
      $this->render_errors();
    }
  }

  public static function redirect($controller = TE_DEFAULT_CONTROLLER, $action = TE_DEFAULT_ACTION, $args = null)
  {
    $_SESSION["errors"] = static::get_errors();
    Header("Location: ".TE_URL_ROOT . "/" . $controller . "/" . $action . "/" . $args);
    die;
  }

  //set and get the user. $user may be any type of Model that is used as 'user'
  public static function set_user($user)
  {
    $_SESSION["user_id"] = $user->id;
    static::$user = $user;
  }
  public static function get_user()
  {
    if(isset($_SESSION["user_id"]))
    {
      if(!static::$user)
      {
        static::$user = new model_user($_SESSION["user_id"]);
      }
      return static::$user;
    }
    else
    {
      return false;
    }
  }
  public static function user_log_out()
  {
    static::$user = null;
    unset($_SESSION["user_id"]);
    session_destroy();
  }

  //set and get title of document. layout must contain get_title() with
  //<title></title> tags.
  public static function set_title($title)
  {
    static::$title = $title;
  }
  public static function get_title()
  {
    if(static::get_err_level() < 3)
    {
      return static::$title;
    }
    else
    {
      return "Error occured";
    }
  }

  //add css / js resp. to the reply
  public static function add_css($css)
  {
    if(filter_var($css, FILTER_VALIDATE_URL))
    {
      //the $css is an url -> external rescource
      static::$css[] = $css;
    }
    else
    {
      static::$css[] = TE_URL_ROOT."/application/css/".$css.".css";
    }
  }
  public static function add_js($js)
  {
    if(filter_var($js, FILTER_VALIDATE_URL))
    {
      //the $css is an url -> external rescource
      static::$js[] = $js;
    }
    else
    {
      static::$js[] = TE_URL_ROOT."/application/js/".$js.".js";
    }
  }
  public static function get_css()
  {
    return static::$css;
  }
  public static function get_js()
  {
    return static::$js;
  }
  public static function get_rescources()
  {
    $rescources = array_merge(
      static::$css,
      static::$js
    );

    return array_unique($rescources);
  }
  public static function render_rescources()
  {
    $css = array_unique(static::$css);
    foreach ($css as $href)
    {
      echo '<link rel="stylesheet" type="text/css" href="' . $href . '">';
    }

    $js = array_unique(static::$js);
    foreach ($js as $href)
    {
      echo '<script src="' . $href . '"></script>';
    }
  }

  public static function get_err_level()
  {
    $level = 0;
    //0 -> all is fine
    //1 -> notices
    //2 -> warnings
    //3 -> errors (assumed fatal)
    foreach (static::$errors as $error) {
      if($error[0] == ERROR)
      {
        $level = 3;
        break;
        //highest level reached, no need to continue
      }
      if($error[1] == WARNING && $level < 2)
      {
        $level = 2;
      }
      else if($error[1] == NOTICE && $level < 1)
      {
        $level = 1;
      }
    }
    return $level;
  }

  public static function throw($level, $msg)
  {
    static::$errors[] = [$level,$msg];
  }
  public static function get_errors()
  {
    return static::$errors;
  }
  public static function get_errors_associative()
  {
    //same as get_errors, but with associative array rather than indexes
    $errlist = static::$errors;
    $returnlist = []  ;
    foreach ($errlist as $error) {
      $returnlist[] = array("type" => $error[0], "msg" => $error[1]);
    }
    return $returnlist;

  }
  public static function render_errors()
  {
    if(static::get_err_level() == 3)
    {
      //include te.core.css because it looks nicer
      echo "<style>";
      te_require_once("css/te.core.css");
      echo "</style>";
    }
    echo '<div id="err_list">';
    if(count(static::$errors) > 0)
    {
      foreach(static::$errors as $error)
      {
        echo '<div class="msg '.$error[0].'">'.$error[1].'</div>';
      }
    }
    else
    {
      //no errors -> render nothing
    }
    echo '</div>';

    static::$errors_printed = true;
  }
}
?>
