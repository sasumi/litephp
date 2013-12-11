<?php
class DBM {
	protected $db_pk;
	protected $db_table;
	protected $data;

	protected function __construct($table, $pk, $data=null){
		$this->db_table = $table;
		$this->data = $data;
	}

	public static function instance($table, $pk=''){
		return new DBM($table, $pk);
	}

	public function create($data=null){
		$this->data = $data ?: $this->data;
		return db_insert($this->db_table, $this->data);
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
		$sql = $sel->select()->from($this->db_table)->where($statement);
		return db_get_all($sql);
	}

	/**
	 * save current change
	 * @return bool
	 */
	public function save(){
		if(!empty($this->data) && empty($this->data[$this->db_pk])){
			return db_insert($this->db_table, $this->data);
		}
		return false;
	}

	public function getByPage($cond=null, $page){
		return db_get_page(db_sql()->from($this->db_table), $page);
	}
}