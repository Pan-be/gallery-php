<?php

class Db_object
{
    public $errors = [];
    public $upload_errors_array = [
    
        UPLOAD_ERR_OK => "There is no error",
        UPLOAD_ERR_INI_SIZE => "The uploaded file exceeds the upload max filesize directive in php.ini",
        UPLOAD_ERR_FORM_SIZE =>"The uploaded file xceeds the MAX_FILE_SIZE directive that",
        UPLOAD_ERR_PARTIAL =>"The uploaded file was only partially uploaded.",
        UPLOAD_ERR_NO_FILE =>"No file was uploaded.",
        UPLOAD_ERR_NO_TMP_DIR =>"Missing a temporary folder.",
        UPLOAD_ERR_CANT_WRITE =>"Failed to write file to disk.",
        UPLOAD_ERR_EXTENSION =>"A PHP extension stopped the file upload."
    ];

    public function set_file($file)
    {
        if(empty($file) || !$file || !is_array($file)) {
            $this->errors[] = 'There was no file uploaded here.';
            return false;
        }elseif ($file['error'] != 0) {
            $this->errors[] = $this->upload_errors_array[$file['error']];
            return false;
        }else {

        $this->user_image = basename($file['name']);
        $this->tmp_path = $file['tmp_name'];
        $this->type = $file['type'];
        $this->size = $file['size'];
        }
    }

    public static function find_all()
    {
        return static::find_by_query("SELECT * FROM " . static::$db_table . " ");
    }

    public static function find_by_id($id)
    {
        global $database;
        $the_result_array = static::find_by_query("SELECT * FROM " . static::$db_table . " WHERE id=$id LIMIT 1");
        
        return !empty($the_result_array) ? array_shift($the_result_array) : false;
        
    }

    public static function find_by_query($base)
    {
        global $database;

        $result_set = $database->query($base);

        $the_object_array = array();

        while($row = mysqli_fetch_array($result_set)) {
            $the_object_array[] = static::instantation($row);
        }

        return $the_object_array;
    }

    public static function instantation($the_record)
    {
        $calling_class = get_called_class();
        $the_object = new $calling_class;

        foreach ($the_record as $the_attribute => $value) {
            if ($the_object->has_the_attribute($the_attribute)) {
                $the_object->$the_attribute = $value;
            }
        }

        return $the_object;

    }

    private function has_the_attribute($key)
    {
        $object_properties = get_object_vars($this);

        return array_key_exists($key,$object_properties);
    }

    protected function properties()
    {
        $properties = array();

        foreach (static::$db_table_fields as $db_field) {
            if (property_exists($this, $db_field)) {
                $properties[$db_field] = $this->$db_field;
            }
        }
        return $properties;
    }

    protected function clean_properties()
    {
        global $database;

        $clean_properties = [];

        foreach ($this->properties() as $key => $value) {
            $clean_properties[$key] = $database->escape_string($value);
        }

        return $clean_properties;
    }

    public function save()
    {
        return isset($this->id) ? $this->update() : $this->create();
    }

    public function create()
    {
        global $database;
        $properties = $this->clean_properties();

        $base = "INSERT INTO " . static::$db_table . "(" . implode(",", array_keys($properties)) . ")";
        $base .= "VALUES ('". implode("','", array_values($properties)) ."')";

        if ($database->query($base)) {
            $this->id = $database->insert_id();

            return true;
        } else {
            return false;
        }
    }

    public function update()
    {
        global $database;

        $properties = $this->clean_properties();

        $properties_pairs = [];

        foreach ($properties as $key => $value) {
            $properties_pairs[] = "{$key}='{$value}'";
        }

        $base = "UPDATE " . static::$db_table . " SET ";
        $base .= implode(",", $properties_pairs);
        $base .= " WHERE id= " . $database->escape_string($this->id);

        $database->query($base);

        return (mysqli_affected_rows($database->connection) == 1) ? true : false;
    }

    public function delete()
    {
        global $database;

        $base = "DELETE FROM  " . static::$db_table . " ";
        $base .= "WHERE id= " . $database->escape_string($this->id);
        $base .= " LIMIT 1";

        $database->query($base);
    }

    public static function count_all()
    {
        global $database;

        $base = "SELECT COUNT(*) FROM " . static::$db_table . " ";

        $result_set = $database->query($base);
        $row = mysqli_fetch_array($result_set);

        return array_shift($row);
    }

}