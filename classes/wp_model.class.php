<?php
abstract class wp_model
{
  //child classes should override this
  //protected static $table_name = null;

  //basic
  public function __construct($construction = null)
  {
    /* $construction can be
     * - an integer => fetch that id from database
     * - an associative array (for example $_POST data) => construct object from data
     * - null => default values
     */
    $default_values_array = $this->get_default_values_array();

    //look into the type of constructor, and get the construction array
    if(is_numeric($construction))
    {
      if(!($object_data = static::get_row("SELECT * FROM " . static::$table_name . " WHERE id = " . $construction)))
      {
        //non existent id
        tank_engine::throw(WARNING,"ID ".$construction." does not exist in ".static::$table_name. ". Empty object generated instead.");
        $object_data = $default_values_array;
      }
    }
    else if(is_array($construction))
    {
      $object_data = array_merge($default_values_array,$construction);
    }
    else if($construction === null)
    {
      $object_data = $default_values_array;
    }
    else
    {
      //just hope for the best...
      //FIXME this is not really neat...
      $object_data = $construction;
    }

    //on the basis of the array, fill all fields with the correct info
    foreach($object_data as $key => $value)
    {
      if(substr($key,-3) == "_id")
      {
        //dealing with a child object. Assign child object to key, minus _id
        $key = substr($key, 0, -3);
        $model_name = get_class($default_values_array[$key]);
        $class = new \ReflectionClass($model_name);

        $this->$key = $class->newInstanceArgs([$value]);
      }
      else
      {
        $this->$key = $value;
      }
    }
  }

  public abstract function get_default_values_array();

  //private methods
  protected static function get_instance($construction = null)
  {
    $classname = get_called_class();
    $obj = new $classname($construction);
    return $obj;
  }
  protected function get_associative_array()
  {
    $array = $this->get_default_values_array();
    $ret_array = [];
    foreach ($array as $key => $value) {
      if($value instanceof wp_model)
      {
        $ret_array[$key."_id"] = $this->$key->id;
      }
      else
      {
        $ret_array[$key] = $this->$key;
      }
    }
    return $ret_array;
  }

  //standard non static methods
  public function save()
  {
    if(isset($this->id))
    {
      static::update($this->get_associative_array(), array("id" => $this->id));
    }
    else
    {
      static::insert($this->get_associative_array());
      $this->id = static::get_insert_id();
    }
  }
  public function destroy()
  {
    static::delete(array("id" => $this->id));
  }

  //custom static methods
  public static function get_all()
  {
    $data = static::get_results("SELECT * FROM " . static::$table_name);
    foreach ($data as $key => $value) {
      $data[$key] = static::get_instance($value);
    }
    return $data;
  }
  public static function get_all_by($conditions)
  {
    $where_clause = "WHERE ";
    foreach ($conditions as $key => $value) {
      $where_clause .= $key . " = \"" . $value . "\" AND ";
    }
    $where_clause = substr($where_clause, 0, -5);
    $data = static::get_results("SELECT * FROM " . static::$table_name . " " . $where_clause);
    foreach ($data as $key => $value) {
      $data[$key] = static::get_instance($value);
    }
    return $data;
  }

  //count all records in table
  public static function get_amount()
  {
    return static::get_var("SELECT count(id) FROM ".static::$table_name);
  }

  //static methods which use a table_name
  public static function insert($data, $format = null)
  {
    return static::wpdb_insert(static::$table_name, $data, $format);
  }
  public static function replace($data, $format = null)
  {
    return static::wpdb_replace(static::$table_name, $data, $format);
  }
  public static function update($data, $where, $format = null, $where_format = null)
  {
    return static::wpdb_update(static::$table_name, $data, $where, $format = null, $where_format = null);
  }
  public static function delete($where, $where_format = null)
  {
    return static::wpdb_delete(static::$table_name, $where, $where_format);
  }

  //static methods
  public static function get_insert_id()
  {
    global $wpdb;
    return $wpdb->insert_id;
  }
  public static function get_var($query, $column_offset = 0, $row_offset = 0)
  {
    global $wpdb;
    return $wpdb->get_var($query, $column_offset, $row_offset);
  }
  public static function get_row($query, $output_type = OBJECT, $row_offset = 0)
  {
    global $wpdb;
    return $wpdb->get_row($query, $output_type, $row_offset);
  }
  public static function get_col($query, $column_offset = 0)
  {
    global $wpdb;
    return $wpdb->get_col($query, $column_offset);
  }
  public static function get_results($query, $output_type = OBJECT)
  {
    global $wpdb;
    return $wpdb->get_results($query, $output_type);
  }
  public static function wpdb_insert($table, $data, $format = null)
  {
    global $wpdb;
    return $wpdb->insert($table, $data, $format);
  }
  public static function wpdb_replace($table, $data, $format = null)
  {
    global $wpdb;
    return $wpdb->replace($table, $data, $format);
  }
  public static function wpdb_update($table, $data, $where, $format = null, $where_format = null)
  {
    global $wpdb;
    return $wpdb->update($table, $data, $where, $format, $where_format);
  }
  public static function wpdb_delete($table, $where, $where_format = null)
  {
    global $wpdb;
    return $wpdb->delete($table, $where);
  }
  public static function query($query)
  {
    global $wpdb;
    return $wpdb->query($query);
  }
}
