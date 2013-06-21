<?php
class DBORM {
	protected $db_pk;
	protected $db_table;
	protected $data;

	protected function __construct($pk, $table, $data=null){
		$this->db_pk = $pk;
		$this->db_table = $table;
		$this->data = $data;
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