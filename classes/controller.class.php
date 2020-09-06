<?php
class te_controller
{
  public function action_exists($action)
  {
    return method_exists($this,$action);
  }
}
?>
