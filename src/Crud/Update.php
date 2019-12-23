<?php

namespace Lite\Crud;

use Lite\Core\Result;
use Lite\DB\Model;

/**
 * 编辑操作
 */
trait Update {
	use CRUDInterface;

	/**
	 * 更新字段列表，默认为更新readonly=false字段
	 */
	protected function getUpdateFields(){
		return [];
	}

	/**
	 * 更新面板显示字段，默认为与更新字段列表一致
	 * @return array
	 */
	protected function getUpdateDisplayFields(){
		return [];
	}

	/**
	 * 编辑
	 * @param $get
	 * @param null $post
	 * @return array|\Lite\Core\Result
	 */
	public function update($get, $post = null){
		/** @var Model $class */
		$class = $this->getModelClass();
		$pk = $class::meta()->getPrimaryKey();
		$pk_val = (int)$get[$pk];
		$instance = $class::findOneByPkOrFail($pk_val);
		$defines = $instance->getEntityPropertiesDefine();

		$update_fields = $this->getUpdateFields();
		if(!$update_fields){
			foreach($defines as $field => $def){
				if(!$def['readonly'] && $def['entity']){
					$update_fields[] = $field;
				}
			}
		}

		if($post){
			$instance->setValues($post);
			$instance->save();
			return new Result(($pk_val ? $instance->getModelDesc().'更新' : '新增').'成功', true, $instance->toArray());
		}

		return array(
			'defines'        => $defines,
			'display_fields' => array_merge($this->getUpdateDisplayFields(), $update_fields),
			'update_fields'  => $update_fields,
			'model_instance' => $instance,
		);
	}
}
