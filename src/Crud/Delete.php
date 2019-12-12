<?php
/**
 * 删除操作
 */
namespace Lite\Crud;
use Lite\Core\Result;
use Lite\DB\Model;

trait Delete {
	use CRUDInterface;

	/**
	 * @access 删除
	 * @param $get
	 * @param null $post
	 * @return \Lite\Core\Result
	 */
	public function delete($get, $post = null){
		/** @var Model $class */
		$class = $this->getModelClass();
		$pk = $class::meta()->getPrimaryKey();
		$pk_val = (int)$get[$pk];

		/** @var Model $ins */
		$ins = $class::findOneByPkOrFail($pk_val);
		$ins->delete();
		return new Result($ins->getModelDesc().'删除成功', true);
	}
}
