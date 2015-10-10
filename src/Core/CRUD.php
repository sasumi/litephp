<?php
namespace Lite\Core;
use Lite\DB\Model;
use Lite\Component\Paginate;
use Lite\Exception\Exception;
use function Lite\func\dump;

/**
 * CRUD访问模式基类
 * User: sasumi
 * Date: 2014/11/18
 * Time: 9:49
 */
abstract class CRUD extends Controller {
	public $use_standard_output = true;

	const OP_ALL = 0x001;
	const OP_INDEX = 0x002;
	const OP_UPDATE = 0x003;
	const OP_STATE = 0x004;
	const OP_DELETE = 0x005;
	const OP_INFO = 0x006;

	/**
	 * 当前CRUD支持功能列表
	 * @return string|array
	 */
	protected function supportCRUDList(){
		return null;
	}

	/**
	 * 获取当前控制器关联模型名称
	 * @return string
	 */
	protected function getModelName(){
		return null;
	}

	/**
	 * 获取模型状态key，缺省为state
	 * @return string
	 */
	protected function getStateKey(){
		return 'state';
	}

	/**
	 * 获取模型对象
	 * @return Model
	 */
	private function getModelInstance(){
		$n = $this->getModelName();
		/** @var Model $n */
		return $n::meta();
	}

	/**
	 * 获取模型主键
	 * @return string
	 */
	public function getModelPk(){
		$mod = $this->getModelInstance();
		return $mod->getPrimaryKey();
	}

	/**
	 * 获取返回按钮url
	 * @return string
	 */
	protected function getBackUrl(){
		return $this->getUrl($this->getController().'/'.$this->getDefaultAction());
	}

	/**
	 * 检测操作项是否在CRUD配置允许的项目内
	 * @param string $op 操作项
	 * @throws Exception
	 * @return bool
	 */
	private function checkSupports($op){
		if($this->getModelName()) {
			$sps = $this->supportCRUDList();
			if (is_array($sps) && (in_array($op, $sps) || in_array(self::OP_ALL, $sps))) {
				return true;
			}
			if (!is_array($sps) && ($op == $sps || $sps == self::OP_ALL)) {
				return true;
			}
		} else {
			throw new Exception('requie getModelName method');
		}
		//throw new Exception('method not support yet');
		return false;
	}

	/**
	 * 列表
	 * @param $search
	 * @return Result
	 * @throws Exception
	 */
	public function index($search){
		$this->checkSupports(self::OP_INDEX);

		/** @var Model $model_name */
		$model_name = $this->getModelName();
		$pk = $this->getModelPk();
		$paginate = Paginate::instance();
		$list = $model_name::find()->order("$pk DESC")->paginate($paginate);

		return new Result(null, null, array(
			'search' => $search,
			'data_list' => $list,
			'model_pk' => $pk,
			'paginate' => $paginate
		));
	}

	/**
	 * 更新
	 * @param $get
	 * @param $post
	 * @return Result
	 * @throws Exception
	 */
	public function update($get, $post){
		$this->checkSupports(self::OP_UPDATE);

		/** @var Model $model_name */
		$model_name = $this->getModelName();
		$pk = $this->getModelPk();
		$pk_val = (int)$get[$pk];
		if(!$pk_val) {
			$instance = new $model_name();
		} else {
			$instance = $model_name::findOneByPk($pk_val);
			if(!$instance){
				return new Result('没有找到要更新的记录');
			}
		}

		if($post) {
			$instance->setValues($post);
			$instance->save();
			return new Result(($pk_val ? '编辑' : '新增').'成功', true, null, $this->getBackUrl());
		}
		return new Result(null, null, array(
			'data' => $instance->$pk ? $instance : null,
			'model_pk' => $pk,
		));
	}

	/**
	 * 更新状态
	 * @param $get
	 * @return Result
	 */
	public function state($get){
		$this->checkSupports(self::OP_STATE);

		/** @var Model $model_name */
		$model_name = $this->getModelName();
		$pk = $this->getModelPk();
		$pk_val = (int)$get[$pk];
		$toState = (int)$get['state'];
		$stateKey = $this->getStateKey();

		$instance = $model_name::findOneByPk($pk_val);
		if($instance) {
			$instance->{$stateKey} = $toState;
			$instance->save();
			return new Result('状态更新成功', true);
		}
		dump($pk_val, 1);
		return new Result('操作失败，请刷新页面后重试');
	}

	/**
	 * 删除记录
	 * @param $get
	 * @return Result
	 */
	public function delete($get){
		$this->checkSupports(self::OP_DELETE);

		/** @var Model $model_name */
		$model_name = $this->getModelName();
		$pk = $this->getModelPk();
		$pk_val = (int)$get[$pk];

		if($pk_val){
			$model_name::delByPk($pk_val);
			return new Result('操作成功',true, null, $this->getBackUrl());
		}
		return new Result('操作失败，请重试');
	}

	/**
	 * 显示单条记录
	 * @param $get
	 * @throws \Lite\Exception\Exception
	 * @return array
	 */
	public function info($get){
		$this->checkSupports(self::OP_INFO);

		/** @var Model $model_name */
		$model_name = $this->getModelName();
		$pk = $this->getModelPk();
		$pk_val = (int)$get[$pk];

		return new Result(null, null, array(
			'data' => $model_name::findOneByPk($pk_val)
		));
	}
}