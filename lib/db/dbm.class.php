<?php
class DBM implements Iterator {
	private $conn;
	private $result;
	private $db_table;
	private $properies;
	private $properies_change_keys;
	private $db_pk;
	private $query;
	private $limit;

	private function __construct($db_table, $properies, $db_pk){
		$this->db_table = $db_table;
		$this->properies = $properies;
		$this->db_pk = $db_pk;
	}

	public static function instance($db_table, $properies=null, $db_pk='id'){
		return new DBM($db_table,$properies,$db_pk);
	}

	public function setProperies($properies=array()){
		foreach($properies as $key=>$val){
			$this->setProperty($key, $val);
		}
	}

	public function setProperty($key, $val){
		$this->properies[$key] = $val;
	}

	public function find($statement, $var1=null, $var2=null){
		if(func_num_args() < 1){
			return $statement;
		}
		$args = array_slice(func_get_args(), 1);
		$count = substr_count($statement, '?');

		//TODO
		for($i=0; $i<$count; $i++){
			$statement = str_replace('?', "'".addslashes($args[$i])."'", $statement);
		}

		$sel = new DB_Query();
		$query = $sel->select()->from($this->db_table)->where($statement);
		$this->query = $query;
		return $this;
	}

	public function all(){
		$list = db_get_all($this->query, $this->conn);
		$ins = array();
		if($list){
			foreach($list as $item){
				array_push($ins, new DBM($this->db_table, $item, $this->db_pk));
			}
		}
		return $ins;
	}

	public function one(){
		$data = db_get_one($this->query);
		if($data){
			$this->setProperies($data);
			return $this;
		}
		return null;
	}

	public function asArray(){
		return $this->properies;
	}

	public function page($page=null){
		return db_get_page($this->query, $page);
	}

	/**
	 * save current change
	 * @return bool
	 */
	public function save(){
		//insert
		if(!empty($this->properies) && empty($this->properies[$this->db_pk])){
			return db_insert($this->db_table, $this->properies);
		}

		//update
		else if($this->properies[$this->db_pk]){
			$db_pk_val = $this->properies[$this->db_pk];
			unset($this->properies[$this->db_pk]);
			$data = array();
			foreach($this->properies as $key=>$pro){
				if(isset($this->properies_change_keys[$key])){
					$data[$key] = $pro;
				}
			}
			if(!empty($data)){
				return db_update($this->db_table, $data, $this->db_pk.'='.$db_pk_val);	
			}
			else {
				throw new Exception("no properies update");
			}
		}
		return false;
	}

	public function create($properies=null){
		$this->properies = $properies ?: $this->properies;
		return db_insert($this->db_table, $this->properies);
	}


	public function __call($method_name, $params){
		if(method_exists($this->query, $method_name)){
			call_user_method_array($method_name, $this->query, $params);
			return $this;
		}
		throw new Exception("METHOD NO EXIST:".$method_name);
	}

	public function __set($key, $val){
		$this->properies[$key] = $val;
		$this->properies_change_keys[$key] = true;
	}

	public function __get($key){
		return $this->properies[$key];
	}

	public function __isset($key){
		return isset($this->properies[$key]);
	}

	public function __unset($key){
		unset($this->properies[$key]);
	}

	public function rewind() {
        reset($this->properies);
    }

    public function current() {
        $var = current($this->properies);
        return $var;
    }

    public function key() {
        $var = key($this->properies);
        return $var;
    }

    public function next() {
        $var = next($this->properies);
        return $var;
    }

    public function valid() {
        $var = $this->current() !== false;
        return $var;
    }
}