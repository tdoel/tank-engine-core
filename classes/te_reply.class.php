<?php
//this class is the parent class of everything that can be served back
//to the client, such as the view, json_data, or css / js
abstract class te_reply
{
  protected $ajax_additional_anchors = [];
  protected $ajax_pushstate = "";

  abstract public function render_reply();

  public function add_ajax_additional_anchor($anchor)
  {
    $this->ajax_additional_anchors[] = $anchor;
  }
  public function set_ajax_pushstate($state)
  {
    $this->ajax_pushstate = $state;
  }
}
?>
