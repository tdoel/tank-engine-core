<?php
class te_model_field
{
  private $fieldname = "";
  private $is_model = false;
  private $datatype = "";
  private $association = "";
  private $foreign_key = "";
  private $polymorphic = false;
  private $default_value = null;
  private $dependent = false;

  //for validation
  private $allowed_properties = array(
    "datatype" => "",
    "association" => ["belongs_to","has_one","has_many"],
    "foreign_key" => "",
    "default_value" => "",
    "polymorphic" => [true, false],
    "dependent" => [true, false],
  );

  public function __construct($fieldname, $properties)
  {
    global $te;
    $this->fieldname = $fieldname;
    foreach($properties as $key => $value)
    {
      //properties and values are case insensitive
      $key = strtolower($key);
      $value = strtolower($value);

      //validate if this is an allowed setting
      if(isset($this->allowed_properties[$key]))
      {
        //key is allowed
        $allowed_values = $this->allowed_properties[$key];
        if(is_array($allowed_values))
        {
          //array contains allowed values
          if(!in_array($value,$allowed_values))
          {
            //not found
            throw new te_runtime_warning("Property '".$key."' in for field '".$this->fieldname."' set to invalid value '".$value."'.");
            continue;
          }
        }
        //no errors found, add the key
        $this->$key = $value;
      }
      else
      {
        //unknown key, throw a warning and do nothing with it
        throw new te_runtime_warning("Invalid key '".$key."' specified for field '".$this->fieldname."'.");
      }
    }

    switch($this->association)
    {
      case "":
        if(substr($this->datatype,0,6) == "model_")
        {
          $this->is_model = true;
          if($this->association == "")
          {
            throw new te_runtime_error("Association not specified for field '".$this->fieldname."'");
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
        if ($this->polymorphic)
        {
          return array(
            $this->fieldname."_id" => $this->get_sql_datatype(),
            $this->fieldname."_type" => "varchar(63)",
          );
        }
        else
        {
          return array($this->fieldname."_id" => $this->get_sql_datatype());
        }
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
  public function get_default_value()
  {
    return $this->default_value;
  }
  public function is_model()
  {
    return $this->is_model;
  }
  public function is_polymorphic()
  {
    return $this->polymorphic;
  }
  public function is_dependent()
  {
    return $this->dependent;
  }
  public function get_assoc_array($value)
  {
    $array = [];
    switch ($this->association)
    {
      case 'has_one':
        // do not include in list, foreign key is in other table
        break;

      case 'has_many':
        // do not include in list, foreign key is in other table
        break;

      case 'belongs_to':
        // include in list with _id suffix

        // first check if the child has a set id. If it has not, throw an error;
        // the child must be saved first
        if(!isset($value->id))
        {
          throw new te_runtime_error("Field '".$this->fieldname."' attempted to generate an associative array with an ID column for a child, but this child is not known in the database, so its ID is not defined",6);
        }
        $array[$this->fieldname."_id"] = $value->id;
        if($this->polymorphic)
        {
          $array[$this->fieldname."_type"] = get_class($value);
        }
        break;

      default:
        $array[$this->fieldname] = $value;
        break;
    }
    return $array;
  }
}
