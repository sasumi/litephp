<?php
namespace Lite\CRUD;
use Lite\Component\Paginate;
use Lite\Core\Controller as CoreController;
use Lite\Core\Result;
use Lite\Core\Router;
use Lite\DB\Model;
use Lite\DB\Query;
use Lite\Exception\Exception;
use Lite\CRUD\ControllerInterface as CI;
use function Lite\func\array_clear_fields;
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
	 * @return array
	 * @throws \Lite\Exception\Exception
	 */
	protected function getDisplayFields(){
		$ins = $this->getModelInstance();
		/** @var CI $this */
		$support_list = $this->supportCRUDList();
		//显示用的字段
		$fields = $support_list[CI::OP_INDEX]['fields'] ?: array_keys($ins->getEntityPropertiesDefine());

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
	 * get update field
	 * @return array
	 * @throws Exception
	 */
	protected function getUpdateFields(){
		$ins = $this->getModelInstance();
		$defines =  $ins->getEntityPropertiesDefine();
		$update_fields = array();

		/** @var CI $this */
		$support_list = $this->supportCRUDList();
		//get update field
		$tmp = $support_list[CI::OP_UPDATE]['fields'] ?: array_keys($defines);

		foreach($tmp as $k=>$v){
			if(is_string($k)){
				$field = $k;
				$alias = $v;
				$defines[$field]['alias'] = $alias;
			} else {
				$field = $v;
				$alias = $defines[$field]['alias'];
			}

			if(!$defines[$field]['readonly']){
				$update_fields[$field] = $alias;
			}
		}

		return $update_fields;
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

		$paginate = Paginate::instance();
		$query = $ins::find()->order("$pk DESC");

		if($support_quick_search && $search['kw']){
			$qs_fields = explode(',',$support_list[CI::OP_QUICK_SEARCH]['fields']);
			foreach($qs_fields as $field){
				$query->addWhere(Query::OP_OR, $field, 'like', '%'.str_replace('%', '', $search['kw']).'%');
			}
		}
		$list = $query->paginate($paginate);

		$defines = $ins->getPropertiesDefine();

		return array(
			'search' => $support_quick_search ? $search : null,
			'data_list' => $list,
			'paginate' => $paginate,

			'defines' => $defines,
			'display_fields' => $this->getDisplayFields(),
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

		$defines = $ins->getEntityPropertiesDefine();

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

		$extra_params = $get;
		unset($extra_params[Router::$ROUTER_KEY]);
		unset($extra_params['ref']);

		return array(
			'defines' => $defines,
			'update_fields' => $this->getUpdateFields(),
			'model_instance' => $ins,
			'extra_params' => $extra_params,
			'operation_list' => $operation_list,
		);
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

		//get update field
		$tmp = $support_list[CI::OP_INFO]['fields'] ?: array_keys($defines);
		$display_fields = array();
		foreach($tmp as $k=>$v){
			if(is_string($k)){
				$field = $k;
				$alias = $v;
				$defines[$field]['alias'] = $alias;
			} else {
				$field = $v;
				$alias = $defines[$field]['alias'];
			}

			if(!$defines[$field]['primary']){
				$display_fields[$field] = $alias;
			}
		}

		$pk_val = (int)$get[$pk];
		$ins = $ins::findOneByPk($pk_val);
		if(!$ins){
			throw new Exception('DATA NO FOUND');
		}

		return array(
			'defines' => $defines,
			'display_fields' => $display_fields,
			'model_instance' => $ins,
			'operation_list' => $operation_list,
		);
	}
}