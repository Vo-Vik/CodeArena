<?php
define("CONFIG_DIR", '../config/');
class db_connect {
    private $link = Null;
    public function __construct(){
        $this->connect();
    }

    protected function connect()
    {
        if(file_exists(CONFIG_DIR.'db.local.php')) {
            $params = require CONFIG_DIR.'db.local.php';
        }
        else if(file_exists(CONFIG_DIR.'db.global.php')){
            $params = require CONFIG_DIR.'db.global.php';
        }
        if(!isset($params['host'])) {
            $params['host'] = 'localhost';
        }
        if(!isset($params['port'])) {
            $params['port'] = 3306;
        }
        if(!isset($params['user'])) {
            $params['user'] = '';
        }
        if(!isset($params['password'])) {
            $params['password'] = '';
        }
        if(!isset($params['dbname'])) {
            $params['dbname'] = 'CodeArena';
        }
        $this->link = new mysqli($params['host'], $params['user'], $params['password'], $params['dbname'], $params['port']);
        if($this->link->connect_errno > 0){
            die('Unable to connect to database [' . $this->link->connect_error . ']');
        }
    }

    public function sql_query($sql,$return_type = 'ASSOC'){
        if(!$result = $this->getLink()->query($sql)){
            die('There was an error running the query [' . $this->getLink()->error . ']');
        }
        if($return_type=='VAL'){
            $row = $result->fetch_array(MYSQLI_NUM);
            return isset($row[0])?$row[0]:NULL;
        }elseif($return_type=='ONE'){
            $row = $result->fetch_array(MYSQLI_ASSOC);
            return $row;
        }else if($return_type=='ARR'){
            $row = $result->fetch_array(MYSQLI_ASSOC);
            return $row;
        }else if($return_type=='ASSOC'){
            $row = $this->my_fetch_all($result);
            return $row;
        }else if($return_type=='VOID'){
            return NULL;
        }else{
            return NULL;
        }
    }
    public function free(){
        mysqli_close($this->getLink());
    }

    private function my_fetch_all($result){
        $res = array();
        while ($tmp = $result->fetch_assoc()) {
            $res[] = $tmp;
        }
        return $res;
    }

    public function getLink() {
        if(!($this->link)){
            $this->connect();
        }
        return $this->link;
    }
    public function escape_string($string)
    {
        return mysqli_real_escape_string($this->getLink(), $string);
    }
    public function insert_id()
    {
        return $this->getLink()->insert_id;
    }
}
