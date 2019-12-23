<?php

namespace Lite\Crud;

use Lite\Core\Result;
use Lite\DB\Model;

/**
 * 新增操作
 */
trait Create {
	use CRUDInterface;

	/**
	 * 获取创建字段列表，默认为非readonly字段
	 * @return array
	 */
	protected function getCreateFields(){
		return [];
	}

	/**
	 * @access 新增
	 * @param $get
	 * @param null $post
	 * @return array|\Lite\Core\Result
	 */
	public function create($get, $post = null){
		/** @var Model $class */
		$class = $this->getModelClass();
		$instance = $class::meta();
		$defines = $instance->getEntityPropertiesDefine();

		$create_fields = $this->getCreateFields();
		if(!$create_fields){
			foreach($defines as $field => $def){
				if(!$def['readonly'] && $def['entity']){
					$create_fields[] = $field;
				}
			}
		}

		if($post){
			$instance->setValues($post);
			$instance->save();
			return new Result('新增成功', true, $instance->toArray());
		}

		return array(
			'defines'        => $defines,
			'create_fields'  => $create_fields,
			'model_instance' => $instance,
		);
	}
}
