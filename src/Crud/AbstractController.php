<?php
namespace Lite\CRUD;
use Lite\Component\Paginate;
use Lite\Core\Config;
use Lite\Core\Controller as CoreController;
use Lite\Core\Result;
use Lite\Core\View;
use Lite\DB\Model;
use Lite\Exception\Exception;
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
		return $this->getUrl($this->getController().'/'.$this->getDefaultAction());
	}

	/**
	 * 获取默认CRUD模版
	 * @return string
	 */
	protected function getDefaultCRUDTemplate(){
		$def_tpl = $this->getController().'/'.$this->getAction().'.php';
		$def_tpl = strtolower($def_tpl);

		/** @var View $viewer */
		$viewer = Config::get('app/render');
		return $viewer::getTemplate($def_tpl);
	}

	/**
	 * 检测操作项是否在CRUD配置允许的项目内
	 * @param string $op 操作项
	 * @throws Exception
	 * @return bool
	 */
	private function checkSupport($op){
		$this->getModelInstance();
		if($this instanceof ControllerInterface){
			$sps = $this->supportCRUDList();
			$sps = array_keys($sps);
			if(in_array(ControllerInterface::OP_ALL, $sps, true) || in_array($op, $sps, true)) {
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
	public function getModelInstance(){
		if($this instanceof ControllerInterface){
			/** @var Model $model */
			$model = $this->getModel();
			$ins = $model::meta();
			if($ins instanceof ModelInterface){
				return $ins;
			}
			throw new Exception('mode should inherit interface model');
		}
		throw new Exception('controller should inherit interface ControllerInterface');

	}

	/**
	 * 列表
	 * @param $search
	 * @return Result
	 * @throws Exception
	 */
	public function index($search){
		/** @var ControllerInterface|self $this */
		$this->checkSupport(ControllerInterface::OP_INDEX);

		$ins = $this->getModelInstance();
		$pk = $ins->getPrimaryKey();
		$support_list = $this->supportCRUDList();
		$operation_list = array_keys($support_list);

		$paginate = Paginate::instance();
		$list = $ins::find()->order("$pk DESC")->paginate($paginate);

		//显示用的字段
		$fields = $support_list[ControllerInterface::OP_INDEX]['fields'] ?: $ins->getAllPropertiesKey();
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

		/** @var View $viewer */
		$view = Config::get('app/render');
		$viewer = new $view(array(
			'search' => $search,
			'data_list' => $list,
			'paginate' => $paginate,

			'defines' => $defines,
			'display_fields' => $display_fields,

			'model_instance' => $ins,
			'operation_list' => $operation_list,
		));

		return $viewer->render($this->getDefaultCRUDTemplate(), true);
	}

	/**
	 * 更新
	 * @param $get
	 * @param $post
	 * @return Result
	 * @throws Exception
	 */
	public function update($get, $post){
		/** @var ControllerInterface|self $this */
		$this->checkSupport(ControllerInterface::OP_UPDATE);

		$ins = $this->getModelInstance();
		$pk = $ins->getPrimaryKey();

		$support_list = $this->supportCRUDList();
		$operation_list = array_keys($support_list);

		$defines = $ins->getEntityPropertiesDefine();

		//get update field
		$tmp = $support_list[ControllerInterface::OP_UPDATE]['fields'] ?: array_keys($defines);
		$update_fields = array();
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

		/** @var View $viewer */
		$view = Config::get('app/render');
		$viewer = new $view(array(
			'defines' => $defines,
			'update_fields' => $update_fields,
			'model_instance' => $ins,
			'operation_list' => $operation_list,
		));
		return $viewer->render($this->getDefaultCRUDTemplate(), true);
	}

	/**
	 * 更新状态
	 * @param $get
	 * @return Result
	 */
	public function state($get){
		$this->checkSupport(ControllerInterface::OP_STATE);

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
		$this->checkSupport(ControllerInterface::OP_DELETE);

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
		/** @var ControllerInterface|self $this */
		$this->checkSupport(ControllerInterface::OP_UPDATE);

		$ins = $this->getModelInstance();
		$pk = $ins->getPrimaryKey();

		$support_list = $this->supportCRUDList();
		$operation_list = array_keys($support_list);

		$defines = $ins->getEntityPropertiesDefine();

		//get update field
		$tmp = $support_list[ControllerInterface::OP_UPDATE]['fields'] ?: array_keys($defines);
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

		/** @var View $viewer */
		$view = Config::get('app/render');
		$viewer = new $view(array(
			'defines' => $defines,
			'display_fields' => $display_fields,
			'model_instance' => $ins,
			'operation_list' => $operation_list,
		));
		return $viewer->render($this->getDefaultCRUDTemplate(), true);
	}
}