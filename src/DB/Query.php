<?php
namespace Lite\DB;
use Lite\Exception\Exception;
use function Lite\func\dump;

/**
 * 数据库查询抽象类
 * User: sasumi
 * Date: 2015/01/06
 * Time: 9:49
 */
class Query {
	const SELECT = 'SELECT';
	const UPDATE = 'UPDATE';
	const DELETE = 'DELETE';
	const INSERT = 'INSERT';
	const REPLACE = 'REPLACE';

	const OP_OR = 1;
	const OP_AND = 2;

	private $sql = '';
	private $table_prefix = '';
	private $operation = self::SELECT;
	private $fields = array('*');
	private $tables = array();
	private $where = array();
	private $order = '';
	private $group = '';
	private $limit;
	private $data;

	/**
	 * 构造方法，初始化SQL语句
	 * @param string $sql
	 */
	public function __construct($sql=''){
		$this->sql = $sql;
	}

	/**
	 * 设置查询语句
	 * @param $sql
	 * @return $this
	 */
	public function setSql($sql){
		$this->sql = $sql;
		return $this;
	}

	/**
	 * 设置表前缀
	 * @param string $table_prefix
	 * @return $this
	 */
	public function setTablePrefix($table_prefix=''){
		$this->table_prefix = $table_prefix;
		return $this;
	}

	/**
	 * 当前查询是否为全行查询
	 */
	public function isFRQuery(){
		return !$this->sql && $this->fields == array('*');
	}

	/**
	 * 查询
	 * @param string $str
	 * @return $this
	**/
	public function select($str='*'){
		$this->operation = self::SELECT;
		$this->field($str);
		return $this;
	}

	/**
	 * 更新
	 * @return $this
	**/
	public function update(){
		$this->operation = self::UPDATE;
		return $this;
	}

	/**
	 * 插入
	 * @return $this
	 */
	public function insert(){
		$this->operation = self::INSERT;
		return $this;
	}

	/**
	 * 删除
	 * @return $this
	 */
	public function delete(){
		$this->operation = self::DELETE;
		return $this;
	}

	/**
	 * 设置数据（仅对update, replace, insert有效)
	 * @param array $data
	 * @return $this
	 */
	public function setData(array $data){
		$this->data = $data;
		return $this;
	}

	/**
	 * 字段
	 * @param string $p1
	 * @param string $p2
	 * @return Query|Model
	**/
	public function field($p1='*', $p2=''){
		$args = func_get_args();
		$str = join(',', $args);
		if($str == '*'){
			return $this;
		}
		$this->fields = explode(',', $str);
		$this->fields = $this->escapeKey($this->fields);
		return $this;
	}

	/**
	 * 表
	 * @param string $str
	 * @return $this
	**/
	public function from($str){
		$tables = explode(',', $str);
		foreach($tables as $key=>$table){
			$tables[$key] = $this->escapeKey($this->table_prefix.$table);
		}
		$this->tables = $tables;
		return $this;
	}

	/**
	 * 添加查询条件 <p>
	 * 调用范例：$query->addWhere(null, 'name', 'like', '%john%');
	 * $query->addWhere($conditions);
	 * </p>
	 * @param mixed $arg1 type为数组表示提交多个查询，如果为函数，则表示嵌套查询
	 * @param $field
	 * @param null $operator
	 * @param null $compare
	 */
	public function addWhere($arg1=self::OP_AND, $field, $operator=null, $compare=null){
		//嵌套子语句模式
		if(is_callable($field)){
			$ws = call_user_func($field);
			$this->where[] = array(
				'type' => $arg1,
				'field' => $this->getWhereStr($ws),
			);
		}

		//二维数组，循环添加
		else if(is_array($arg1) && count($arg1, COUNT_RECURSIVE) != count($arg1)){
			$this->where = array_merge($this->where, $arg1);
		}

		//普通数组模式
		else if(is_array($arg1)){
			$this->where = array_merge($this->where, $arg1);
		}

		//普通模式
		else if($field){
			$this->where[] = array(
				'type' => $arg1,
				'field' => $field,
				'operator' => $operator,
				'compare' => $compare
			);
		}
	}

	/**
	 * 设置AND查询条件 <p>
	 * 调用范例：$query->where('age', '>', 18)->where('gender', '=', 'male')->where('name', 'like', '%moon%');
	 * </p>
	 * @param string $field
	 * @param null $operator
	 * @param null $compare
	 * @return $this
	 */
	public function where($field, $operator=null, $compare=null){
		$this->addWhere(self::OP_AND, $field, $operator, $compare);
		return $this;
	}

	/**
	 * 设置OR查询条件
	 * @param $field
	 * @param null $operator
	 * @param null $compare
	 */
	public function orWhere($field, $operator=null, $compare=null){
		$this->addWhere(self::OP_OR, $field, $operator, $compare);
	}

	/**
	 * get where string
	 * @param array $wheres
	 * @return string
	 */
	private function getWhereStr(array $wheres=array()){
		$str = '';
		foreach($wheres?:$this->where as $w){
			$k = $w['type'] == self::OP_AND ? 'AND' : 'OR';
			if(!empty($w['operator']) && !empty($w['compare'])){
				$str .= ($str ? " $k ":'').'`'.$w['field'].'` '.$w['operator'].' \''.addslashes($w['compare']).'\'';
			} else {
				$str .= ($str ? " $k (":'(').$w['field'].')';
			}
		}
		return $str ? ' WHERE '.$str : '';
	}

	/**
	 * 排序
	 * @param string $str
	 * @return Query|Model
	**/
	public function order($str){
		$this->order = explode(',', $str);
		return $this;
	}

	/**
	 * 分组
	 * @param string $str
	 * @return Query|Model
	**/
	public function group($str){
		$this->group = $str;
		return $this;
	}

	/**
	 * 设置查询限制，如果提供的参数为0，则表示不进行限制
	 * @throws Exception
	 * @return $this
	 */
	public function limit(/**$p1,$p2**/){
		list($p1, $p2) = func_get_args();
		if($p2){
			$this->limit = array($p1,$p2);
		} else if(is_array($p1)){
			$this->limit = $p1;
		} else if (is_scalar($p1) && $p1 != 0) {
			$this->limit = array(0, $p1);
		}

		if($this->sql && $this->limit){
			if(preg_match('/\slimit\s/', $this->sql)){
				throw new Exception('SQL LIMIT SET:' . $this->sql);
			}
			$this->sql = $this->sql . ' LIMIT ' . $this->limit[0] . ',' . $this->limit[1];
		}
		return $this;
	}

	/**
	 * 判断当前操作语句是否为写入语句
	 * @param string $sql
	 * @return int
	 */
	public static function isWriteOperation($sql=''){
		return !preg_match('/^select\s/i', trim($sql));
	}

	/**
	 * 给字段名称添加保护（注意，该保护仅为保护SQL关键字，而非SQL注入保护
	 * 自动忽略存在空格、其他查询语句的情况
	 * @param $field
	 * @return string
	 */
	private function escapeKey($field){
		if(is_array($field)){
			$ret = array();
			foreach($field as $val){
				$ret[] = strpos($val, '`') === false && strpos($val, ' ') === false ? "`$val`" : $val;
			}
			return $ret;
		} else {
			return strpos($field, '`') === false && strpos($field, ' ') === false ? "`$field`" : $field;
		}
	}

	/**
	 * 输出SQL查询语句
	 * @return string
	 * @throws Exception
	 */
	public function __toString(){
		if($this->sql){
			return $this->sql;
		}

		switch($this->operation){
			case self::SELECT:
				$sql = 'SELECT '.implode(',', $this->fields).
					' FROM '.implode(',', $this->tables).
					$this->getWhereStr().
					($this->group ? ' GROUP BY '.$this->group : '').
					($this->order ? ' ORDER BY '.join(',',$this->order) : '');
				break;

			case self::DELETE:
				$sql = "DELETE FROM ".implode(',', $this->tables).$this->getWhereStr();
				break;

			case self::INSERT:
				if(!$this->data){
					throw new Exception("NO DATA IN DB.INSERT");
				}
				$data_list = count($this->data) == count($this->data, 1) ? array($this->data) : $this->data;
				$key_str = implode(",", $this->escapeKey(array_keys($data_list[0])));
				$sql = "INSERT INTO ".implode(',', $this->tables)."($key_str) VALUES ";
				$comma = '';
				foreach($data_list as $row){
					$str = array();
					foreach($row as $val){
						if(is_numeric($val)){
							$str[] = $val;
						} else {
							$str[] = $val;
						}
					}
					$value_str = implode(",", $str);
					$sql .= $comma."($value_str)";
					$comma = ',';
				}
				break;

			case self::REPLACE:
			case self::UPDATE:
				if(!$this->data){
					throw new Exception("NO DATA IN DB.UPDATE");
				}
				$data_list = count($this->data) == count($this->data, 1) ? array($this->data) : $this->data;
				$sets = array();
				foreach($data_list as $row){
					$sets = array();
					foreach($row as $field_name => $value){
						$field_name = $this->escapeKey($field_name);
						if($value === null){
							$sets[] = "$field_name = NULL";
						} else {
							$sets[] = "$field_name = ".$value;
						}
					}
				}
				$op_key = $this->operation == self::REPLACE ? 'REPLACE INTO' : 'UPDATE';
				$sql = "$op_key ".implode(',', $this->tables).' SET '.implode(',', $sets).$this->getWhereStr();
				break;

			default:
				throw new Exception("NO DB OPERATE SET");
		}
		if($this->limit && stripos(' LIMIT ', $sql) === false){
			if(!$this->limit[0]){
				$sql .= " LIMIT ".$this->limit[1];
			} else {
				$sql .= " LIMIT ".$this->limit[0].','.$this->limit[1];
			}
		}
		return $sql;
	}
}