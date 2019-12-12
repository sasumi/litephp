<?php
/**
 * 详情操作
 */

namespace Lite\Crud;

use function Lite\func\class_uses_recursive;

trait Info {
	use CRUDInterface;

	/**
	 * 获取详情显示字段列表，默认为非pk所有字段
	 * @return array
	 */
	protected function getInfoDisplayFields(){
		return [];
	}

	public function info($get, $post = null){
		/** @var \Lite\DB\Model $class */
		$class = $this->getModelClass();
		$pk = $class::meta()->getPrimaryKey();
		$pk_val = (int)$get[$pk];
		$instance = $class::findOneByPkOrFail($pk_val);
		$defines = $instance->getEntityPropertiesDefine();
		$display_fields = $this->getInfoDisplayFields();
		$operation_list = class_uses_recursive($this);

		if(!$display_fields){
			foreach($defines as $field => $def){
				if($field !== $pk){
					$display_fields[] = $field;
				}
			}
		}

		return array(
			'defines'        => $defines,
			'display_fields' => $display_fields,
			'model_instance' => $instance,
			'operation_list' => $operation_list,
		);
	}
}
