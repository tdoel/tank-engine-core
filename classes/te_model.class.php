<?php
class te_model
{
  /**FIELD DEFINITIONS
   * Every child class should define these variables:
   *
   * protected static $table_name = "table name in SQL database";
   * //this variable holds the table name of the database that is used in all
   * //queries.
   *
   * protected static $fields = array(
   *  "field 1" => "sql data type OR model_xxx"
   * );
   * //this array holds the various fields or properties of this model. The
   * //should correspond to the columnnames of the databaes, and the datatype
   * //can be any accepted SQL datatype (such as 'int' or 'varchar(127)') or
   * //a model, specified by 'model_name' (e.g. 'model_user').
   *
   * A child may optionally declare default values for these fields:
   * protected static $default_values = array(
   *  "field 1" => default value;
   * );
   **/

  //this will hold the database connection
  protected static $conn = null;

  /* PUBLIC METHODS */
  public function save()
  {
    if(isset($this->id))
    {
      //update existing record in database
      static::update($this->get_assoc_array(), array("id" => $this->id));
    }
    else
    {
      //create new record in database
      $id = static::insert($this->get_assoc_array());
      $this->id = $id;
    }
  }

  /* CONSTRUCTORS */
  public function __construct($construction = null)
  {
    //prepare a construction array, with default values if present
    if(isset(static::$field_defaults))
    {
      $construction_array = static::$field_defaults;
    }
    else
    {
      $construction_array = [];
    }

    //interpret $construction and compese an associative array with the data to
    //add to this model
    if(is_numeric($construction))
    {
      //numeric constructor, interpret this as id
      $construction_array = array_merge($construction_array,static::get_row("SELECT * FROM ".static::$table_name." WHERE id = :id", array("id" => $construction)));

      if($construction_array == [])
      {
        //item with this id not found
        throw new Exception("Record with id = $construction does not exist in ".static::$table_name." (issued by ".get_called_class().").");
      }

      //assign id to self, to indicate that the object exists in database
      $this->id = $construction;
    }
    else if(is_array($construction))
    {
      $construction_array = array_merge($construction_array, $construction);
    }

    //use $construction_array to fill object with data
    foreach(static::$fields as $field => $datatype)
    {
      if($this->is_field_model($field))
      {
        //if the id of the child is given, only save the id to the object. The
        //child itself will be loaded upon request, using the __get magical
        //method. If the id is not given, add a new child
        $fieldname = $field."_id";
        if(isset($construction_array[$fieldname]))
        {
          //id defined, save id to object
          $this->$fieldname = $construction_array[$fieldname];
        }
        else
        {
          //not defined, so a new child
          $this->$field = new $datatype();
        }
      }
      else
      {
        if(isset($construction_array[$field]))
        {
          $this->$field = $construction_array[$field];
        }
        else
        {
          $this->$field = 0;
        }
      }
    }
  }
  public static function __construct_static()
  {
    //__static_construct() is called by the autoloader if defined. This code
    // is executed once, before the first instance of this class is created
    if(get_called_class() == "te_model")
    {
      //this code should only be executed for child classes
      return;
    }

    //verify that $table_name and $fields are set by the model
    if(!isset(static::$table_name))
    {
      throw new Exception(get_class($this) . ' does not define $table_name');
    }
    if(!isset(static::$fields))
    {
      throw new Exception(get_class($this) . ' does not define $fields');
    }

    //attempt to connect to database
    static::$conn = new PDO("mysql:host=".TE_DB_HOST.";dbname=".TE_DB_DB, TE_DB_USER, TE_DB_PASS);

    //set the PDO error mode to exception
    static::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    //check if the table exists
    if(!static::table_exists())
    {
      //table does not exist, attempt to create it
      static::create_table();
    }
    else
    {
      static::update_table();
    }
  }

  /* DATABASE INTERACTION METHODS */
  protected static function get_results($sql, $params = [])
  {
    $stmt = static::$conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
  }
  protected static function get_row($sql, $params = [])
  {
    $stmt = static::$conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
  }
  protected static function get_var($sql, $params = [])
  {
    return static::get_row($sql, $params)[0];
  }
  protected static function insert($values)
  {
    //compose sql query
    $sql = "INSERT INTO ".static::$table_name." ";
    $sql_columns = "";
    $sql_values = "";
    foreach($values as $colname => $value)
    {
      $sql_columns .= $colname . ",";
      $sql_values .= ":".$colname . ",";
    }
    $sql_columns = "(".substr($sql_columns,0,-1).")";
    $sql_values = "VALUES (".substr($sql_values,0,-1).")";

    $sql .= $sql_columns . " " . $sql_values;


    //prepare query
    $stmt = static::$conn->prepare($sql);

    //execute query, with values as parameters
    $stmt->execute($values);

    //return inserted id
    return static::$conn->lastInsertId();
  }
  protected static function update($values, $where)
  {
    //compose sql query
    $sql = "UPDATE ".static::$table_name." ";
    $params = [];

    //compose SET part
    $sql_set = "";
    foreach($values as $colname => $value)
    {
      $sql_set .= $colname."=:s".$colname.",";
      $params["s".$colname] = $value;
    }
    $sql_set = "SET ".substr($sql_set,0,-1);

    //compose WHERE part
    $sql_where = "";
    foreach($where as $colname => $value)
    {
      $sql_where .= $colname."=:w".$colname." AND ";
      $params["w".$colname] = $value;
    }
    $sql_where = "WHERE ".substr($sql_where,0,-5);

    $sql .= $sql_set . " " . $sql_where;

    //prepare query
    $stmt = static::$conn->prepare($sql);

    //execute query, with values as parameters
    $stmt->execute($params);
  }

  /* MAGIC METHODS */
  public function __get($field)
  {
    if($this->is_field_model($field))
    {
      $classname = static::$fields[$field];
      $fieldname = $field."_id";
      $this->$field = new $classname($this->$fieldname);
      return $this->$field;
    }
  }

  /* PRIVATE METHODS FOR INTERNAL USE */
  private static function table_exists()
  {
    $res = static::$conn->query("SHOW TABLES LIKE '".static::$table_name."'");
    if($res->fetch())
    {
      return true;
    }
    else
    {
      return false;
    }
  }
  private static function create_table()
  {
    //creates a table for this model
    $sql = "CREATE TABLE " . static::$table_name . " (";
    $sql .= "id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,";
    foreach(static::$fields as $field => $type)
    {
      $sql .= $field . " " . $type . ",";
    }
    $sql = substr($sql,0,-1) . ")";
    static::$conn->query($sql);
  }
  private static function update_table()
  {
    //updates an existing table for this model to the correct columns

    //get the columns from the database
    $db_columns = static::get_results('SELECT COLUMN_NAME, COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS'.
      ' WHERE TABLE_SCHEMA = "'.TE_DB_DB.'"'.
      ' AND TABLE_NAME = "'.static::$table_name.'"');

    //reshape $db_columns such that it resembles $fields
    $db_columns_assoc = [];
    foreach($db_columns as $column)
    {
      $db_columns_assoc[$column["COLUMN_NAME"]] = $column["COLUMN_TYPE"];
    }

    //compare defined fields with columns in db
    $sql = "";
    foreach(static::$fields as $column_name => $datatype)
    {
      if(substr($datatype,0,6) == "model_")
      {
        //this is a model
        $column_name = $column_name . "_id";
        $datatype = "int";
      }
      if(!isset($db_columns_assoc[$column_name]))
      {
        //column does not exist, create it
        $sql .= 'ALTER TABLE '.static::$table_name.' ADD '.$column_name.' '.$datatype.';';
      }
      else if(!static::is_equal_datatypes($datatype, $db_columns_assoc[$column_name]))
      {
        //column exitsts, but its not the correct datatype
        tank_engine::throw(ERROR,"The datatypes '".$datatype."' of field '".$column_name."' in '".get_called_class()."' and '".$db_columns_assoc[$column_name]."' in database table '".static::$table_name."' do not match");
      }
    }

    //compare db columns with field to see if any column should be dropped
    foreach($db_columns_assoc as $column_name => $datatype)
    {
      if(substr($column_name,-3) == "_id")
      {
        //column names suffixed with _id are reffering to other models
        $column_name = substr($column_name,0,-3);
      }
      if($column_name == "id")
      {
        //column id should never be defined in a model -> do nothing
      }
      else if(!isset(static::$fields[$column_name]))
      {
        //column not defined in model, so it can be dropped
        tank_engine::throw(WARNING,"Column '".$column_name."' in database table '".static::$table_name."' is not defined by '".get_class($this)."'. Can it be dropped?");
      }
    }

    //if there's anything in $sql, run it to update the database
    if($sql != "")
    {
      static::$conn->query($sql);
    }
  }
  private static function is_equal_datatypes($type1, $type2)
  {
    $info1 = preg_split("/[\(\)]/i",$type1);
    $info2 = preg_split("/[\(\)]/i",$type2);

    $i = 0;
    $max_iter = min(count($info1),count($info2));
    $is_equal = true;
    while ($i < $max_iter)
    {
      if(strtolower(trim($info1[$i]," ")) != strtolower(trim($info2[$i])))
      {
        $is_equal = false;
      }
      $i++;
    }
    return $is_equal;
  }
  private function get_assoc_array()
  {
    $array = [];
    foreach(static::$fields as $field => $datatype)
    {
      if($this->is_field_model($field))
      {
        $array[$field."_id"] = $this->$field->id;
      }
      else
      {
        $array[$field] = $this->$field;
      }
    }
    return $array;
  }
  private function is_field_model($column_name) //FIXME: should be static
  {
    return substr(static::$fields[$column_name],0,6) == "model_";
  }
}
