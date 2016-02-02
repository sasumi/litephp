<?php
namespace Lite\CRUD;
use Lite\Component\Paginate;
use Lite\Core\Controller as CoreController;
use Lite\Core\Result;
use Lite\Core\Router;
use Lite\CRUD\ControllerInterface as CI;
use Lite\DB\Model;
use Lite\DB\Query;
use Lite\Exception\Exception;
use function Lite\func\array_clear_fields;
use function Lite\func\array_filter_subtree;
use function Lite\func\dump;

/**
 * CRUD访问模式基类
 * User: sasumi
 * Date: 2014/11/18
 * Time: 9:49
 */
abstract class AbstractController extends CoreController{
	/**
	 * 获取返回按钮url
	 * @return string
	 */
	protected function getBackUrl(){
		return '';
	}

	/**
	 * 检测操作项是否在CRUD配置允许的项目内
	 * @param string $op 操作项
	 * @return bool
	 * @throws \Lite\Exception\Exception
	 */
	private function checkSupport($op){
		$ins = $this->getModelInstance();
		if($this instanceof CI){
			$sps = $this->supportCRUDList();
			$sps = array_keys($sps);
			if(in_array(CI::OP_ALL, $sps, true) || in_array($op, $sps, true)) {
				if($op == CI::OP_STATE && !$ins->getStateKey()){
					throw new Exception('no state key return');
				}
				return true;
			}
		}
		throw new Exception('CRUD NO SUPPORT CURRENT OPERATION');
	}

	/**
	 * 获取关联模型实例
	 * @return Model|ModelInterface
	 * @throws Exception
	 */
	protected function getModelInstance(){
		/** @var Model $model */
		if($this instanceof CI){
			$model = $this->getModel();
			$ins = $model::meta();
			if($ins instanceof ModelInterface){
				return $ins;
			}
			throw new Exception('mode should inherit interface model');
		}
		throw new Exception('controller should inherit interface CI');
	}

	/**
	 * 获取可以显示的字段列表
	 * @param $OPERATE_TYPE
	 * @return array
	 * @throws \Lite\Exception\Exception
	 */
	protected function getOpFields($OPERATE_TYPE){
		$ins = $this->getModelInstance();

		/** @var CI $this */
		$support_list = $this->supportCRUDList();

		$all_fields = array_keys($ins->getEntityPropertiesDefine());

		//显示用的字段
		$tmp = $support_list[$OPERATE_TYPE]['fields'] ?: $all_fields;

		if(in_array('*', $tmp)){
			$fields = $all_fields;
			foreach($tmp as $f){
				if(strpos($f, '-') === 0){
					$fields = array_diff($fields, array(substr($f, 1)));
				}
			}
		} else {
			$fields = $tmp;
		}

		$defines = $ins->getPropertiesDefine();
		$display_fields = array();
		foreach($fields as $k=>$v){
			$alias = '';
			if(is_string($k)){
				$field = $k;
				$alias = $v;
			} else {
				$field = $v;
			}
			if(!$alias){
				$alias = $defines[$field]['alias'];
			}
			//remove primary or undefined field
			if($defines[$field] && !$defines[$field]['primary']){
				$display_fields[$field] = $alias;
			}
		}
		return $display_fields;
	}

	/**
	 * get quick update fields
	 * @param string $op
	 * @return mixed
	 */
	protected function getQuickUpdateFields($op = CI::OP_INDEX){
		/** @var CI $this */
		$support_list = $this->supportCRUDList();
		$fields = $support_list[$op]['quick_update_fields'];
		if(!$fields){
			return array();
		}
		if(in_array('*', $fields)){
			/** @var Model $mod */
			$mod = $this->getModel();
			$ins = $mod::meta();
			$fields = $ins->getAllPropertiesKey();
		}
		return $fields;
	}

	protected function getSupportOperationList(){
		/** @var CI $this */
		$support_list = $this->supportCRUDList();
		return array_keys($support_list);
	}

	/**
	 * 检测CRUD页面是否需要在新窗口打开
	 * @param string $operate_type 操作类型
	 * @param Model $instance
	 * @param int $threshold 阀值（行数）
	 * @param array $factors 因子（普通表单、textarea输入框、简单富文本、复杂富文本）
	 * @return bool
	 */
	protected function checkNewWindowFlag($operate_type, $instance, $threshold=20, $factors=array(1, 4, 20, 20)){
		$counts = $this->getOperateFieldCount($operate_type, $instance);
		$sum = 0;
		foreach($counts as $k=>$c){
			$sum += $c*$factors[$k];
		}
		return $sum>=$threshold;
	}

	/**
	 * 获取操作字段数量
	 * @param $operate_type
	 * @param Model $instance
	 * @return array
	 */
	protected function getOperateFieldCount($operate_type, $instance){
		/** @var ControllerInterface|self $this*/
		$support = $this->supportCRUDList()[$operate_type];
		if($support){
			$op_fields = $this->getOpFields($operate_type);
			$def = $instance->getPropertiesDefine();
			$nc = $tc = $sc = $lc = 0;
			foreach($op_fields as $field=>$n){
				switch($def[$field]['type']){
					case 'text':
						$tc++;
						break;

					case 'simple_rich_text':
						$sc++;
						break;

					case 'rich_text':
						$lc++;
						break;

					default:
						$nc++;
				}
			}
			return array($nc, $tc, $sc, $lc);
		}
		return array();
	}

	/**
	 * 列表
	 * @param $search
	 * @return Result
	 * @throws Exception
	 */
	public function index($search){
		/** @var CI|self $this */
		$this->checkSupport(CI::OP_INDEX);

		$ins = $this->getModelInstance();
		$pk = $ins->getPrimaryKey();
		$support_list = $this->supportCRUDList();
		$operation_list = $this->getSupportOperationList();
		$support_quick_search = in_array(CI::OP_QUICK_SEARCH,$operation_list);
		$defines = $ins->getPropertiesDefine();

		$paginate = Paginate::instance();
		$query = $ins::find()->order("$pk DESC");

		$quick_search_defines = array();
		foreach($support_list[CI::OP_QUICK_SEARCH]['fields']?:array() as $field){
			if($defines[$field]){
				$quick_search_defines[$field] = $defines[$field];
			}
		}

		/**
		 * 快速搜索
		 */
		if($support_quick_search){
			foreach($quick_search_defines as $field=>$def){
				if($search[$field] || strlen($search[$field])){
					switch($def['type']){
						case 'string':
						case 'text':
						case 'simple_rich_text':
							$query->addWhere(Query::OP_AND, $field, 'like', '%'.str_replace('%', '', $search[$field]).'%');
							break;

						case 'timestamp':
						case 'date':
						case 'datetime':
							if($search[$field][0]){
								$query->addWhere(Query::OP_AND, $field, '>=', $search[$field][0]);
							}
							if($search[$field][1]){
								$query->addWhere(Query::OP_AND, $field, '<=', $search[$field][1]);
							}
							break;


						case 'microtime':
							if($search[$field][0]){
								$query->addWhere(Query::OP_AND, $field, '>=', strtotime($search[$field][0]));
							}
							if($search[$field][1]){
								$query->addWhere(Query::OP_AND, $field, '<=', strtotime($search[$field][1]));
							}
							break;

						default:
							$query->addWhere(Query::OP_AND, $field, '=', $search[$field]);
					}
				}
			}
		}

		/** @var MultiLevelModelInterface|ModelInterface|Model $ins */
		if($ins instanceof MultiLevelModelInterface){
			$list = $query->all(true);
			$display_field = $ins->getDisplayField();
			$parent_id_field = $ins->getParentIdField();

			$list = array_filter_subtree(0, $list, array(
				'parent_id_key' => $parent_id_field,
				'id_key' => $ins->getPrimaryKey()
			));

			$tmp_list = array();
			foreach($list as $item){
				$item[$display_field] = static::buildMultiLevelDisplay($item[$display_field], $item['tree_level']);
				$tmp = clone($ins);
				$tmp->setValues($item);
				$tmp_list[] = $tmp;
			}
			$list = $tmp_list;
		} else {
			$list = $query->paginate($paginate);
		}

		$update_in_new_page = $this->checkNewWindowFlag(CI::OP_UPDATE, $ins);
		return array(
			'search' => $support_quick_search ? $search : null,
			'data_list' => $list,
			'paginate' => $paginate,
			'quick_search_defines' => $quick_search_defines,
			'update_in_new_page' => $update_in_new_page,
			'defines' => $defines,
			'display_fields' => $this->getOpFields(CI::OP_INDEX),
			'quick_update_fields' => $this->getQuickUpdateFields(CI::OP_INDEX),
			'model_instance' => $ins,
			'operation_list' => $operation_list,
		);
	}

	/**
	 * 更新单个字段
	 * @param $get
	 * @param $post
	 * @return \Lite\Core\Result
	 * @throws \Lite\Exception\Exception
	 */
	public function updateField($get, $post){
		/** @var self|CI $this */
		$quick_update_fields = $this->getQuickUpdateFields(CI::OP_INDEX);
		$quick_update_fields = array_merge($quick_update_fields, $this->getQuickUpdateFields(CI::OP_INFO));

		/** @var AbstractController $this */
		$ins = $this->getModelInstance();
		$pk_val = $post['pk_val'];
		$field = $post['field'];
		$val = $post['value'];

		if(in_array($field, $quick_update_fields)){
			$ins = $ins::findOneByPk($pk_val);
			$ins->setValue($field, $val);
			$ins->save();
			return $this->getCommonResult(true);
		}
		return new Result('非法操作');
	}

	/**
	 * 更新
	 * @param $get
	 * @param $post
	 * @return Result
	 * @throws Exception
	 */
	public function update($get, $post){
		/** @var CI|self $this */
		$this->checkSupport(CI::OP_UPDATE);

		$ins = $this->getModelInstance();
		$pk = $ins->getPrimaryKey();

		$support_list = $this->supportCRUDList();
		$operation_list = array_keys($support_list);

		$pk_val = (int)$get[$pk];
		if($pk_val) {
			$ins = $ins::findOneByPk($pk_val);
		}
		if($post) {
			$ins->setValues($post);
			$ins->save();
			return new Result(($pk_val ? $ins->getModelDesc().'更新' : '新增').'成功', true, array(
				$pk => $ins->$pk,
			), $this->getBackUrl());
		}

		/** @var MultiLevelModelInterface|ModelInterface|Model $ins */
		if($ins instanceof MultiLevelModelInterface){
			$parent_id_field = $ins->getParentIdField();
			$def = $ins->getPropertiesDefine($parent_id_field);
			if(!isset($def['options'])){
				$list = $ins::find()->all(true);
				$parent_id_field = $ins->getParentIdField();
				$display_field = $ins->getDisplayField();
				$list = array_filter_subtree(0, $list, array(
					'parent_id_key' => $parent_id_field,
					'id_key' => $ins->getPrimaryKey()
				));
				foreach($list as $k=>$item){
					$list[$k][$display_field] = static::buildMultiLevelDisplay($item[$display_field], $item['tree_level']);
				}
				$options = array_combine(array_column($list, $ins->getPrimaryKey()), array_column($list, $display_field));
				$ins->setPropertiesDefine(array(
					$parent_id_field => array(
						'options' => $options
					)
				));
			}
		}

		$extra_params = $get;
		unset($extra_params[Router::$ROUTER_KEY]);
		unset($extra_params['ref']);
		$defines = $ins->getEntityPropertiesDefine();

		return array(
			'defines' => $defines,
			'update_fields' => $this->getOpFields(CI::OP_UPDATE),
			'model_instance' => $ins,
			'extra_params' => $extra_params,
			'operation_list' => $operation_list,
		);
	}

	/**
	 * @param $string
	 * @param $level
	 * @return string
	 */
	public static function buildMultiLevelDisplay($string, $level){
		return str_repeat('&nbsp;', $level*5).'|-- '.$string;
	}

	/**
	 * 更新状态
	 * @param $get
	 * @return Result
	 */
	public function state($get){
		$this->checkSupport(CI::OP_STATE);

		$ins = $this->getModelInstance();
		$pk = $ins->getPrimaryKey();
		$pk_val = (int)$get[$pk];

		$stateKey = $ins->getStateKey();
		$toState = (int)$get[$stateKey];
		$instance = $ins::findOneByPk($pk_val);

		if($instance) {
			$instance->{$stateKey} = $toState;
			$instance->save();
			return new Result($ins->getModelDesc().'状态更新成功', true);
		}
		return new Result('操作失败，请刷新页面后重试');
	}

	/**
	 * 删除记录
	 * @param $get
	 * @return Result
	 */
	public function delete($get){
		$this->checkSupport(CI::OP_DELETE);

		$ins = $this->getModelInstance();
		$pk = $ins->getPrimaryKey();
		$pk_val = (int)$get[$pk];

		if($pk_val){
			$ins::delByPk($pk_val);
			return new Result($ins->getModelDesc().'删除成功',true, null, $this->getBackUrl());
		}
		return new Result('操作失败，请刷新页面后重试');
	}

	/**
	 * 显示单条记录
	 * @param $get
	 * @throws \Lite\Exception\Exception
	 * @return array
	 */
	public function info($get){
		/** @var CI|self $this */
		$this->checkSupport(CI::OP_INFO);

		$ins = $this->getModelInstance();
		$pk = $ins->getPrimaryKey();

		$support_list = $this->supportCRUDList();
		$operation_list = array_keys($support_list);
		$defines = $ins->getEntityPropertiesDefine();

		$pk_val = (int)$get[$pk];
		$ins = $ins::findOneByPk($pk_val);
		if(!$ins){
			throw new Exception('DATA NO FOUND');
		}

		return array(
			'defines' => $defines,
			'display_fields' => $this->getOpFields(CI::OP_INFO),
			'model_instance' => $ins,
			'operation_list' => $operation_list,
		);
	}
}