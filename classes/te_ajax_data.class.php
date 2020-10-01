<?php
class te_ajax_data Extends te_reply
{
  public $data = null;
  public function __construct($data = null)
  {
    $this->data = $data;
  }
  public function render_reply()
  {
    $json["data"] = $this->data;
    $json["errors"] = tank_engine::get_errors_associative();

    echo json_encode($json);
    return true;
  }
}
