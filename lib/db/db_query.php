<?php
class DB_Query {
	private $operation = 'SELECT';
	private $fields = array('*');
	private $tables = array();
	private $where = '';
	private $order = '';
	private $group = '';
	private $limit;
	private $data;
	private $sql;

	public function __construct(){
	}

	/**
	 * 查询
	 * @param string $str
	 * @return $this
	**/
	public function select($str='*'){
		$this->operation = 'SELECT';
		$this->field($str);
		return $this;
	}

	/**
	 * 更新
	 * @param string $str
	 * @return $this
	**/
	public function update($str){
		$this->operation = 'UPDATE';
		$this->from($str);
		return $this;
	}

	/**
	 * 插入
	 * @param string $str
	 * @return $this
	**/
	public function insert($str){
		$this->operation = 'INSERT';
		$this->from($str);
		dump($str);
		return $this;
	}

	/**
	 * 删除
	 * @param string $str
	 * @return $this
	**/
	public function delete($str){
		$this->operation = 'DELETE';
		$this->from($str);
		return $this;
	}

	/**
	 * 设置数据（仅对update, replace, insert有效)
	 * @param string $str
	 * @return $this
	**/
	public function setData(array $data){
		$this->data = $data;
		return $this;
	}

	/**
	 * 字段
	 * @param string $str
	 * @return $this
	**/
	public function field($str='*'){
		$this->fields = explode(',', $str);
		return $this;
	}

	/**
	 * 表
	 * @param string $str
	 * @return $this
	**/
	public function from($str){
		$this->tables = explode(',', $str);
		return $this;
	}

	/**
	 * 条件
	 * @param string $str
	 * @return $this
	**/
	public function where($str){
		$this->where = $str;
		return $this;
	}

	/**
	 * 排序
	 * @param string $str
	 * @return $this
	**/
	public function order($str){
		$this->order = explode(',', $str);
		return $this;
	}

	/**
	 * 分组
	 * @param string $str
	 * @return $this
	**/
	public function group($str){
		$this->group = $str;
		return $this;
	}

	/**
	 * 设置SQL语句
	 * @param string $sql
	 * @return $this
	 **/
	public function setSql($sql){
		$this->sql = $sql;
		return $this;
	}

	/**
	 * 设置限定
	 * @param number $p1
	 * @param number $p2
	 * @return $this
	 */
	public function limit(/**$p1,$p2**/){
		list($p1, $p2) = func_get_args();
		if($p2){
			$this->limit = array($p1,$p2);
		} else {
			$this->limit = array(0, $p1);
		}
		return $this;
	}

	/**
	 * 输出字符串
	 * @return string
	 **/
	public function __toString(){
		if(!$this->sql){
			$sql = '';
			switch($this->operation){
				//查询
				case 'SELECT':
					$sql = 'SELECT '.implode(',', $this->fields).
						' FROM '.implode('`,`', $this->tables).
						($this->where ? ' WHERE '.$this->where : '').
						($this->order ? ' ORDER BY '.$this->order : '').
						($this->group ? ' GROUP BY '.$this->group : '');
					break;

				//更新
				case 'UPDATE':
					if(!$this->data){
						db_exception("NO DATA IN DB.UPDATE");
					}
					$datas = count($this->data) == count($this->data, 1) ? array($this->data) : $this->data;
					foreach($datas as $row){
						$sets = array();
						foreach($row as $field_name => $value){
							if($value === null){
								$sets[] = "$field_name = null";
							} else {
								$sets[] = "$field_name = '$value'";
							}

						}
					}
					$sql = 'UPDATE '.implode('`,`', $this->tables).' SET '.implode(',', $sets).
						($this->where ? ' WHERE '.$this->where : '').
						($this->order ? ' ORDER BY '.$this->order : '').
						($this->group ? ' GROUP BY '.$this->group : '');
					break;

				//删除
				case 'DELETE':
					$sql = "DELETE FROM ".implode('`,`', $this->tables).($this->where ? ' WHERE '.$this->where : '');
					break;

				//插入
				case 'INSERT':
					if(!$this->data){
						db_exception("NO DATA IN DB.INSERT");
					}
					$datas = count($this->data) == count($this->data, 1) ? array($this->data) : $this->data;
					$key_str = implode(",", array_keys($datas[0]));
					$sql = "INSERT INTO ".implode('`,`', $this->tables)."($key_str) VALUES ";
					$comma = '';
					foreach($datas as $row){
						$value_str = implode("','", array_values($row));
						$sql .= $comma."('$value_str')";
						$comma = ',';
					}
					break;

				//替换
				case 'REPLACE':
					if(!$this->data){
						db_exception("NO DATA IN DB.UPDATE");
					}
					$datas = count($this->data) == count($this->data, 1) ? array($this->data) : $this->data;
					foreach($datas as $row){
						$sets = array();
						foreach($row as $field_name => $value){
							if($value === null){
								$sets[] = "$field_name = null";
							} else {
								$sets[] = "$field_name = '$value'";
							}

						}
					}
					$sql = "REPLACE INTO ".implode('`,`', $this->tables)." SET ".implode(',', $sets).
						($this->where ? ' WHERE '.$this->where : '');
					break;

				default:
					db_exception("NO DB OPERATE SETTED");

			}
			$this->sql = $sql;
		}
		if($this->limit){
			$this->sql .= $this->limit ? " LIMIT ".$this->limit[0].','.$this->limit[1] : '';
		}
		return $this->sql;
	}
}
?>