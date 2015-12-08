<?php
/**
 * 数组相关操作函数
 * User: sasumi
 * Date: 2015/2/28
 * Time: 14:35
 */
namespace Lite\func {
	/**
	 * Array group by function
	 * group array(); by by_key
	 * @author sasumi
	 * @param array $array
	 * @param string $by_key
	 * @param boolean $limit
	 * @return array $array handle result
	 */
	function array_group($array, $by_key, $limit = false) {
		if(empty ($array) || !is_array($array)) {
			return $array;
		}

		$_result = array();
		foreach ($array as $item) {
			$sub_keys = array_keys($item);
			if(in_array($by_key, $sub_keys)) {
				$_result[$item[$by_key]][] = $item;
			} else {
				$_result[count($_result)][] = $item;
			}
		}
		if(!$limit) {
			return $_result;
		}

		$result = array();
		foreach ($_result as $key => $item) {
			$result[$key] = $item[0];
		}
		return $result;
	}

    /**
     * 检测KEY合并数组，增强array_merge
     * @param array $array1
     * @param array $array2
     * @return array
     */
    function array_merge_recursive_distinct(array &$array1, array &$array2){
        $merged = $array1;
        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset ($merged [$key]) && is_array($merged [$key])) {
                $merged [$key] = array_merge_recursive_distinct($merged [$key], $value);
            } else {
                $merged [$key] = $value;
            }
        }

        return $merged;
    }

	/**
	 * 清理数组中null的元素
	 * @param array $data
	 * @param bool $recursive
	 * @return array
	 */
	function array_clear_null($data, $recursive = true) {
		if(empty($data) || !is_array($data)) {
			return $data;
		}
		foreach ($data as $k => $item) {
			if($item === null) {
				unset($data[$k]);
			}
			if($recursive && is_array($item)) {
				$data[$k] = array_clear_null($item);
			}
		}
		return $data;
	}

	/**
	 * 清理数组中empty的元素
	 * @param $data
	 * @param bool $recursive
	 * @return array
	 */
	function array_clear_empty($data, $recursive = true) {
		if(empty($data) || !is_array($data)) {
			return $data;
		}
		foreach ($data as $k => $item) {
			if(empty($item)) {
				unset($data[$k]);
			}
			if($recursive && is_array($item)) {
				$data[$k] = array_clear_empty($item);
				if(empty($data[$k])) {
					unset($data[$k]);
				}
			}
		}
		return $data;
	}

	/**
	 * 清理数组字段
	 * @param array $keep_fields
	 * keep_fields 格式：
	 * array(
	 * 'id',
	 * 'title',
	 * 'url',
	 * 'tags',
	 * 'categories' => function($data){
	 * if(!empty($data)){
	 * foreach($data as $k=>$cat){
	 * $data[$k] = array_clear_fields(array('id', 'name', 'url'), $cat);
	 * }
	 * }
	 * return $data;
	 * },
	 * 'album' => array(
	 * 'id',
	 * 'cover_image_id',
	 * 'cover_image'=>array(
	 * 'id',
	 * 'title',
	 * 'url',
	 * 'thumb_url'
	 * ),
	 * 'url'
	 * ),
	 * 'liked',
	 * 'like_url',
	 * 'fav_url',
	 * 'thumb_url',
	 * 'like_data_url',
	 * 'link',
	 * 'counter' => array(
	 * 'visit_count',
	 * 'like_count',
	 * 'share_count',
	 * 'collect_count'
	 * ),
	 * )
	 * @param array $data
	 * @return array
	 */
	function array_clear_fields(array $keep_fields, array $data) {
		foreach ($data as $k => $item) {
			$keep = false;
			foreach ($keep_fields as $fk => $cfg) {
				if(is_numeric($fk) && is_string($cfg) && $cfg == $k) {
					$keep = true;
					break;
				} else if(is_string($fk) && $fk == $k && $data[$fk]) {
					if(is_callable($cfg)) {
						$data[$k] = call_user_func($cfg, $item);
						$keep = true;
					} else if(is_array($cfg) && $data[$k]) {
						$data[$k] = array_clear_fields($cfg, $item);
						$keep = true;
					}
				}
			}
			if(!$keep) {
				unset($data[$k]);
			}
		}
		return $data;
	}

	/**
	 * trim数组
	 * @param $data
	 * @param bool $recursive
	 * @return mixed
	 */
	function array_trim($data, $recursive = true) {
		if(empty($data) || !is_array($data)) {
			return $data;
		}
		foreach ($data as $k => $item) {
			if(is_scalar($item)) {
				$data[$k] = trim($item);
			} else if(is_array($item) && $recursive) {
				$data[$k] = array_trim($item);
			}
		}
		return $data;
	}

	/**
	 * get first item of array
	 * @param array $data
	 * @param null &$key
	 * @return
	 */
	function array_first(array $data = array(), &$key = null) {
		foreach ($data as $key => $item) {
			return $item;
		}
		return null;
	}

	/**
	 * 获取数组最后一个数据
	 * @param array $data
	 * @param null $key
	 * @return null
	 */
	function array_last(array $data = array(), &$key = null) {
		if(!empty($data)) {
			$keys = array_keys($data);
			$key = array_pop($keys);
			return $data[$key];
		}
		return null;
	}

	/**
	 * 在数组开始位置压入关联数据
	 * @param &$arr
	 * @param $key
	 * @param $val
	 * @return int
	 */
	function array_unshift_assoc(&$arr, $key, $val) {
		$arr = array_reverse($arr, true);
		$arr[$key] = $val;
		$arr = array_reverse($arr, true);
		return count($arr);
	}

	/**
	 * array sort by specified key
	 * @example: array_orderby($data, 'volume', SORT_DESC, 'edition', SORT_ASC);
	 * @param mix
	 * @return mixed
	 */
	function array_orderby(&$src_arr) {
		if(empty($src_arr)){
			return $src_arr;
		}
		$args = func_get_args();
		$data = array_shift($args);
		foreach ($args as $n => $field) {
			if(is_string($field)) {
				$tmp = array();
				foreach ($data as $key => $row) {
					$tmp[$key] = $row[$field];
				}
				$args[$n] = $tmp;
			}
		}
		$args[] = &$data;
		call_user_func_array('array_multisort', $args);
		$src_arr = array_pop($args);
	}

	/**
	 * filter array by specified keys
	 * @deprecated 请使用 array_clear_fields
	 * @example array_filter_by_keys($data, array('key1','key2'));
	 * array_filter_by_keys($data, 'key1', 'key2');
	 * @param $arr
	 * @param $keys
	 * @return array
	 */
	function array_filter_by_keys($arr, $keys) {
		$args = is_array($keys) ? $keys : array_slice(func_get_args(), 1);
		$data = array();
		foreach ($args as $k) {
			$data[$k] = $arr[$k];
		}
		return $data;
	}

	/**
	 * 过滤子节点，以目录树方式返回
	 * @param $parent_id
	 * @param $all
	 * @param array $opt
	 * @param int $level
	 * @return array
	 */
	function array_filter_subtree($parent_id, $all, $opt = array(), $level = 0) {
		$opt = array_merge(array(
			'return_as_tree' => false,             //以目录树返回，还是以平铺数组形式返回
			'level_key' => 'tree_level',          //返回数据中是否追加等级信息,如果选项为空, 则不追加等级信息
			'id_key' => 'id',                     //主键键名
			'parent_id_key' => 'parent_id',       //父级键名
			'children_key' => 'children'          //返回子集key(如果是平铺方式返回,该选项无效
		), $opt);

		$pn_k = $opt['parent_id_key'];
		$lv_k = $opt['level_key'];
		$id_k = $opt['id_key'];
		$as_tree = $opt['return_as_tree'];
		$c_k = $opt['children_key'];

		$result = array();
		$has_children = array_group($all, $pn_k);

		foreach ($all as $k=>$item) {
			if($item[$pn_k] == $parent_id) {
				if($lv_k) {
					$item[$lv_k] = $level;
				}
				if(!$opt['return_as_tree']) {
					$result[] = $item;
				}
				if($has_children[$item[$id_k]]) {
					$sub = array_filter_subtree($item[$id_k], $all, $opt, $level + 1);
					if(!empty($sub)) {
						if($as_tree) {
							$item[$c_k] = $sub;
						} else {
							$result = array_merge($result, $sub);
						}
					}
				}
				if($as_tree) {
					$result[] = $item;
				}
			}
		}
		return $result;
	}

	/**
	 * 插入指定数组在指定位置
	 * @param array $src_array
	 * @param $data
	 * @param string $rel_key
	 * @return array|int
	 */
	function array_insert_after(array $src_array = array(), $data, $rel_key = '') {
		if(!in_array($rel_key, array_keys($src_array))) {
			return array_push($src_array, $data);
		} else {
			$tmp_array = array();
			$len = 0;

			foreach ($src_array as $key => $src) {
				$tmp_array[$key] = $src;
				$len++;
				if($rel_key === $key) {
					break;
				}
			}
			$tmp_array[] = $data;
			return array_merge($tmp_array, array_slice($src_array, $len));
		}
	}

	/**
	 * 合并数组到指定位置之后
	 * @param array $src_array
	 * @param array $new_array
	 * @param string $rel_key
	 * @return array
	 */
	function array_merge_after(array $src_array = array(), array $new_array = array(), $rel_key = '') {
		if(!in_array($rel_key, array_keys($src_array))) {
			return array_merge($src_array, $new_array);
		} else {
			$tmp_array = array();
			$len = 0;

			foreach ($src_array as $key => $src) {
				$tmp_array[$key] = $src;
				$len++;
				if($rel_key === $key) {
					break;
				}
			}
			$tmp_array = array_merge($tmp_array, $new_array);
			return array_merge($tmp_array, array_slice($src_array, $len));
		}
	}

	/**
	 * 检测数组是否为关联数组
	 * @param  array $array
	 * @return boolean
	 */
	function is_assoc_array($array) {
		return is_array($array) && array_values($array) != $array;
	}

	/**
	 * 函数  支持嵌套转换
	 * @param array $data
	 * @param array $rules = array(
	 * 'dddd' => array('aaaa', 'bbbb')
	 *                        ）,
	 * 转换为 : array['aaaaa']['bbb'] =  xxxx
	 * @return mixed
	 */
	function array_transform(array $data, array $rules) {
		$ret_array = array();
		foreach ($rules as $key => $value) {
			if(!is_int($key) && isset($data[$key])) {
				if(is_array($value) && !empty($value)) {
					$tmp = &$ret_array;
					foreach ($value as $v) {
						$tmp = &$tmp[$v];
					}
					$tmp = $data[$key];
				} elseif(is_string($value)) {
					$ret_array[$value] = $data[$key];
				}
			} else if(is_int($key) && isset($data[$value])) {
				$ret_array[$value] = $data[$value];
			}
		}
		return $ret_array;
	}
}