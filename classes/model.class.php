<?php
class Model
{
  //abstract $table = null; //every model must define a table associated with it=
  public static function get_all($query_append = "")
  {
    return static::db_select(null,null,$query_append);
  }
  public static function get_all_by_condition($conditions,$operator = "AND")
  {
    return static::db_select($conditions,$operator);
  }

  public function save($recursion_top = true)
  {
    $retval = null;
    //first: check for child elements
    //if found, save child element\
    //update: we shouldn't be saving children
    /*foreach ($this->db_items as $key)
    {
      if($this->$key instanceof Model)
      {
        //found child
        $child_id = $this->$key->save(false);
        $child_key = $key."_id";
        $this->child_key = $child_id;
      }
    }*/

    if(isset($this->id))
    {
      //update existing object
      static::db_update($this);
      $retval = $this->id;
    }
    else
    {
      //insert new object
      $retval = static::db_insert($this);

      //since now defined, update own id
      $this->id = $retval;
    }
    if($recursion_top)
    {
      if(!$retval)
      {
        tank_engine::throw(WARNING, "Error occured during insertion");
      }
    }
    return $retval;
  }
  public function destroy($recursion_top = true)
  {
    //this destroys all child elements by default
    //first: check for child elements
    //if found, destroy child element
    $success = true;

    //update: don't destroy children
    /*foreach ($this as $key => $value)
    {
      if($value instanceof Model)
      {
        $success = $this->$key->destroy(false) == false ? false : $success;
      }
    }*/
    $success = static::db_delete($this) == false ? false : $success;
    if($recursion_top)
    {
      if($success)
      {
        tank_engine::throw(NOTICE, "Item deleted successfully from database");
      }
      else
      {
        tank_engine::throw(WARNING, "Error occured during deletion");
      }
    }
    return $success;
  }

  public function __construct($construction = null)
  {
    //check which properties are already defined, add them to the db_items list.
    //this list will be used when putting things to the database.
    $db_items;
    foreach ($this as $key => $value) {
      $db_items[] = $key;
    }
    $this->db_items = $db_items;

    if(is_array($construction))
    {
      //object constructed from array (e.g. provided by SQL)
      $properties = $construction;
    }
    else if(is_numeric($construction))
    {
      //object constructed from id, so we need to get data from Database
      //db_select returns an array, we need to get the first element of that
      $query = static::db_select(["id" => $construction]);
      if(isset($query[0]))
      {
        $properties = $query[0];
      }
    }
    else if($construction === null)
    {
      //no constructor given, so we create a new object. No further action required
      $properties = null;
    }
    else
    {
      //crap is provided as $construction. No idea what to do, throw a notice.
      tank_engine::throw(NOTICE,"Provided object as construction to Model class. We don't know how to handle that.");
    }
    if(isset($properties))
    {
      if($properties)
      {
        foreach ($properties as $key => $value)
        {
          if(substr($key,-3) == "_id")
          {
            //dealing with a child.
            $subkey = substr($key,0,-3);

            if(isset($this->$subkey))
            {
              $class = get_class($this->$subkey);
              $this->$subkey = new $class($value);
            }
            else
            {
              tank_engine::throw(WARNING,"$key specified in associative array, but property $subkey was not found.");
            }
          }
          else
          {
            $this->$key = $value;
          }
        }
      }
    }
  }

  //
  private static function get_instance($construction = null)
  {
    $classname = get_called_class();
    $obj = new $classname($construction);
    return $obj;
  }

  /*These functions interact directly with the database.
  * Static because not associated with an object. protected because child classes
  * must be able to use them, but not the outside world. Function only used
  * in parent class are private.
  */
  //FIXME: all functions must be protected against injection!
  private static function db_delete($obj)
  {
    $query = "DELETE FROM ".static::$table." WHERE id = ".$obj->id;
    return static::db_sql($query);
  }
  private static function db_update($obj)
  {
    $query = "UPDATE ".static::$table." SET ";
    foreach ($obj->db_items as $key)
    {
      //never update id, never update child elements
      if($key != "id" && !($obj->$key instanceof Model))
      {
        if($obj->$key != null)
        {
          $query .= $key . " = '" . $obj->$key . "',";
        }
        else
        {
          $query .= $key . " = null,";
        }
      }
    }

    //truncate last comma
    $query = substr($query,0,-1);

    $query .= " WHERE id = ".$obj->id;

    return static::db_sql($query);
  }

  private static function db_insert($obj)
  {
    $columns = "";
    $values = "";
    foreach ($obj->db_items as $key)
    {
      //do not insert id, insert datatypes and children differently
      if($obj->$key instanceof Model)
      {
        $columns .= $key . "_id,";
        $values .= "'" . $obj->$key->id . "',";
      }
      else
      {
        $columns .= $key . ",";
        if($obj->$key !== null)
        {
          $values .= "'" . $obj->$key . "',";
        }
        else
        {
          $values .= "null,";
        }
      }
    }
    $query = "INSERT INTO ".static::$table." (".substr($columns,0,-1).") VALUES (".substr($values,0,-1).")";

    return static::db_sql($query,true);
  }

  private static function db_select($conditions = null, $operator = "AND", $query_append = "")
  {
    $retval = [];
    $query = "SELECT * FROM ".static::$table;
    if($conditions)
    {
      $query .= " WHERE";
      foreach ($conditions as $column_name => $value)
      {
        $query .= " ".$column_name." = ";
        if(is_bool($value) || is_numeric($value))
        {
          $query .= $value;
        }
        else
        {
          $query .= "'".$value."'";
        }
        $query .= " $operator ";
      }
      $query = substr($query,0,-(strlen($operator)+2));
    }
    $query .= " " . $query_append;
    $result = static::db_sql($query);
    if($result)
    {
      while($row = $result->fetch_assoc())
      {
        $instance = static::get_instance($row);
        $retval[] = $instance;
      }
      return $retval;
    }
    else {
      //throw a nice error
      tank_engine::throw(WARNING,"An error occured executing ". $query);
      //FIXME: don't provide sensitive info when printing error.
      return false;
    }
  }

  private static function db_sql($query, $return_insert_id = false)
  {
    //create connection
    $mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_DB);

    if($mysqli->connect_errno)
    {
      //connection failed.
      tank_engine::throw(ERROR,"Database error: ".$mysqli->connect_error);
      //FIXME: do not display error on public site
      return false;
    }
    else
    {
      //connection worked, no try executing query

      //may be useful for debugging:
      //tank_engine::throw(NOTICE,$query);

      if(!$result = $mysqli->query($query))
      {
        //query resulted in an error
        tank_engine::throw(WARNING,"Query error: ".$mysqli->error);
        //FIXME: do not display error on public site
        return false;
      }
      else
      {
        //if insert / update query, return insert_id
        if($return_insert_id)
        {
          $result = $mysqli->insert_id;
        }

        //query succeded, now return result
        return $result;
      }
    }
  }
}
?>
