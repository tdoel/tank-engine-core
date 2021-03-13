<?php
class te_model_field
{
  private $fieldname = "";
  private $is_model = false;
  private $datatype = "";
  private $association = "";
  private $foreign_key = "";

  public function __construct($fieldname, $properties)
  {
    $this->fieldname = $fieldname;
    foreach($properties as $key => $value)
    {
      $this->$key = $value;
    }

    switch($this->association)
    {
      case "":
        if(substr($this->datatype,0,6) == "model_")
        {
          $this->is_model = true;
          if($this->association == "")
          {
            tank_engine::throw(WARNING,"Field ".$fieldname." is a model, but an association is not defined. Falling back to belongs_to");
            $this->association = "belongs_to";
          }
        }
        break;
      case "belongs_to":
        $this->is_model = true;
        break;
      case "has_many":
        $this->is_model = true;
        break;
      case "has_one":
        $this->is_model = true;
        break;
    }
  }

  public function get_association()
  {
    return $this->association;
  }
  public function get_foreign_key()
  {
    return $this->foreign_key."_id";
  }
  public function get_foreign_table()
  {
    $classname = $this->datatype;
    return $classname::get_table_name();
  }
  public function get_fieldname()
  {
    return $this->fieldname;
  }
  public function get_db_columns()
  {
    switch ($this->association)
    {
      case "":
        return array($this->fieldname => $this->get_sql_datatype());
      case "belongs_to":
        return array($this->fieldname."_id" => $this->get_sql_datatype());
      case "has_one":
        return [];
      case "has_many":
        return [];
    }
  }
  public function get_sql_datatype()
  {
    if($this->is_model)
    {
      return 'int';
    }
    else
    {
      return $this->datatype;
    }
  }
  public function get_datatype()
  {
    return $this->datatype;
  }
  public function is_model()
  {
    return $this->is_model;
  }
}
