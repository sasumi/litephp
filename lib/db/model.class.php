<?php
abstract class DB_Model extends Data /** implements DB_IModel **/ {
	private $result = null;
	private $filters = array();
	private $query = null;
	private $limit = null;

	/**
	 * precheck
	 * @deprecated this method will overide ~~
	 * @throws LException
	 */
	public function __construct(){
		if(!$this->getTableName() || !$this->getPrimaryKey()){
			throw new LException("DB MODEL CONFIG ERROR");
		}
	}

	/**
	 * set filters
	 * @param array $filters
	 */
	protected function setFilters(array $filters){
		$this->filters = $filters;
	}

	/**
	 * set filter
	 * @param unknown $key
	 * @param unknown $filter
	 */
	protected function setFilter($key, $filter){
		$this->filters[$key] = $filter;
	}

	/**
	 * get filters
	 * @return multitype:
	 */
	protected function getFilters(){
		return $this->filters;
	}

	/**
	 * get filter
	 * @param unknown $key
	 * @return multitype:
	 */
	protected function getFilter($key){
		return $this->filters[$key];
	}

	/**
	 * get db record instance
	 * @return DB_Record
	 */
	protected function getDbRecord(){
		return DB_Record::init();
	}

	/**
	 * current model table name
	 */
	abstract protected function getTableName();

	/**
	 * current mode primary key
	*/
	abstract protected function getPrimaryKey();

	/**
	 * select function
	 * @param string $statement
	 * @param string $var1
	 * @param string $var2
	 * @return unknown|DB_Model
	 */
	public static function find($statement='', $var1=null, $var2=null){
		//add slashes
		$args = array_slice(func_get_args(), 1);

		$sel = new DB_Query();
		if($this){
			if(!empty($args)){
				$arr = explode('?', $statement);
				$rst = '';
				foreach($args as $key=>$val){
					$rst .= $arr[$key].$this->getDbRecord()->quote($val);
				}
				$rst .= array_pop($arr);
				$statement = $rst;
			}
			$query = $sel->select()->from($this->getTableName())->where($statement);
			$this->query = $query;
			return $this;
		} else {
			//PHP 5.3+
			$class = get_called_class();
			$obj = new $class;
			if(!empty($args)){
				$arr = explode('?', $statement);
				$rst = '';
				foreach($args as $key=>$val){
					$rst .= $arr[$key].$obj->getDbRecord()->quote($val);
				}
				$rst .= array_pop($arr);
				$statement = $rst;
			}
			$query = $sel->select()->from($obj->getTableName())->where($statement);
			$obj->query = $query;
			return $obj;
		}
	}

	/**
	 * get all records
	 * @return array
	 */
	final public function all(){
		$list = $this->getDbRecord()->getAll($this->query);
		$obj = null;
		if($list){
			$obj = clone $this;
			$obj->setItems($list);
		}
		return $obj;
	}

	/**
	 * get one record
	 * @return DB_Model|NULL
	 */
	final public function one(){
		$data = $this->getDbRecord()->getOne($this->query);
		if($data){
			$this->setItems($data);
			return $this;
		}
		return null;
	}

	/**
	 * get records by page
	 * @param string $page
	 * @return multitype:
	 */
	final public function page($page=null){
		return $this->getDbRecord()->getPage($this->query, $page);
	}

	/**
	 * update current object
	 * @throws LException
	 * @return number
	 */
	final public function update(){
		$data = $this->getItems();
		$pk_val = $data[$this->getPrimaryKey()];
		if(!$pk_val){
			throw new LException("primary value no found");
		}
		unset($data[$this->getPrimaryKey()]);

		$update_data = array();
		$filters = array();
		$item_change_keys = $this->getItemChangekeys();
		foreach($data as $key=>$pro){
			if(isset($item_change_keys[$key])){
				$update_data[$key] = $pro;
				$filters[$key] = $this->getFilter($key);
			}
		}

		if(!empty($update_data)){
			$result = Filter::init()->filteArray($update_data, $filters);
			if(!empty($result)){
				throw new LException(array_pop($result), $result);
			}
			return $this->getDbRecord()->update($this->getTableName(), $update_data, $this->getPrimaryKey().'='.$pk_val);
		}
		else {
			throw new LException("no properies update");
		}
	}

	/**
	 * insert new data
	 * @throws LException
	 * @return Ambigous <boolean, resource, PDOStatement>
	 */
	final public function insert(){
		$data = $this->getItems();
		$result = Filter::init()->filteArray($data, $filters);
		if(!empty($result)){
			throw new LException(array_pop($result));
		}
		return $this->getDbRecord()->insert($this->getTableName(), $data);
	}

	/**
	 * save current change
	 * @return bool
	 */
	final public function save(){
		$data = $this->getItems();
		$has_pk = !empty($data[$this->getPrimaryKey()]);

		if($has_pk){
			return $this->update();
		} else if(!empty($data)){
			return $this->insert();
		}
	}

	final public function __call($method_name, $params){
		if(method_exists($this->query, $method_name)){
			call_user_method_array($method_name, $this->query, $params);
			return $this;
		}
		throw new LException("METHOD NO EXIST:".$method_name);
	}
}