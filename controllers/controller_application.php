<?php
class controller_application Extends te_controller
{
  //general controller for loading css / js / images
  public function css($file = null)
  {
    return new te_rescource("css/".$file, "text/css");
  }
  public function js($file = null)
  {
    return new te_rescource("js/".$file, "text/javascript");
  }
  public function img($file = null)
  {
    return new te_rescource("img/".$file);
  }
}
