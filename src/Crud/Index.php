<?php
/**
 * 列表操作
 */

namespace Lite\Crud;

use Lite\Component\UI\Paginate;
use Lite\Core\View;
use Lite\DB\Model;
use Lite\DB\Query;
use function Lite\func\class_uses_recursive;

trait Index {
	use CRUDInterface;

	/**
	 * 获取列表搜索字段，默认空表示不提供搜索功能
	 */
	protected function getIndexSearchFields(){
		return [];
	}

	/**
	 * 获取列表显示字段，默认为所有字段（除了pk）
	 * @return array
	 */
	protected function getIndexDisplayFields(){
		return [];
	}

	/**
	 * 获取列表排序字段，默认为主键降序
	 * @return array [排序字段列表，默认排序字段，默认排序方向]
	 */
	protected function getIndexOrderConfig(){
		/** @var Model $class */
		$class = $this->getModelClass();
		$ins = $class::meta();
		$pk = $ins->getPrimaryKey();
		return [[$pk], $pk, 'desc'];
	}

	/**
	 * 绑定查询
	 * @param \Lite\DB\Model|\Lite\DB\Query $model
	 * @param $fields
	 * @param array $values
	 */
	private static function bindIndexSearchFieldsToInstance($model, $fields, $values = []){
		$defines = $model->getPropertiesDefine();
		foreach($fields as $field){
			$def = $defines[$field];
			if($def && $values[$field] || strlen($values[$field])){
				switch($def['type']){
					case 'string':
					case 'text':
					case 'simple_rich_text':
						$model->addWhere(Query::OP_AND, $field, 'like', '%'.str_replace('%', '', $values[$field]).'%');
						break;

					case 'timestamp':
					case 'date':
					case 'datetime':
						$model->between($field, $values[$field][0], $values[$field][1]);
						break;

					case 'microtime':
						$model->between($field, strtotime($values[$field][0]), strtotime($values[$field][1]));
						break;

					default:
						$model->addWhere(Query::OP_AND, $field, '=', $values[$field]);
				}
			}
		}
	}

	/**
	 * 编辑
	 * @param $get
	 * @param null $post
	 * @return array
	 * @throws \Lite\Exception\Exception
	 */
	public function index($get, $post = null){
		/** @var Model $class */
		$class = $this->getModelClass();
		$model = $class::meta();
		$query = $model::find();
		$defines = $model->getPropertiesDefine();
		$pk = $model->getPrimaryKey();
		$paginate = Paginate::instance();

		$operation_list = class_uses_recursive($this);

		$search_fields = $this->getIndexSearchFields();
		$display_fields = $this->getIndexDisplayFields();

		if(!$display_fields){
			foreach($defines as $field=>$def){
				if($field !== $pk){
					$display_fields[] = $field;
				}
			}
		}
		$query->field(array_merge($display_fields, [$pk]));

		if($search_fields){
			self::bindIndexSearchFieldsToInstance($model, $search_fields, $get);
		}

		//排序
		list($order_fields, $default_order_field, $default_order_dir) = $this->getIndexOrderConfig();
		if($order_fields){
			View::setOrderConfig($order_fields, $default_order_field, $default_order_dir);
			list($order_field, $order_dir) = View::getCurrentOrderSet();
			if($order_field && $order_dir){
				$query->order("`".addslashes($order_field)."` $order_dir");
			}
		}

		/** @var MultiLevelModelInterface|Model $model */
		$list = $query->paginate($paginate);
		return array(
			'search'         => $search_fields,
			'data_list'      => $list,
			'paginate'       => $paginate,
			'defines'        => $defines,
			'display_fields' => $display_fields,
			'order_fields'   => $order_fields,
			'model_instance' => $model,
			'operation_list' => $operation_list,
		);
	}
}
