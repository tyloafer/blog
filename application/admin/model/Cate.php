<?php

namespace app\admin\model;
use think\Model;

/**
* 分类操作
*/
class Cate extends Model {
	/**
	 * 根据parent_id获取其子分类
	 * @param  integer $cate_id [description]
	 * @param  array   $array   要寻找的数组，不传则从数据库获取
	 * @param  string  $type    [返回的类型  tree:array]
	 * @return [type]           [description]
	 */
	function get_cate_tree($cate_id = 0, $array = array(), $type = 'array'){
		if(empty($array)){
			$array = $this->db->name('cate')->order('cate_sort')->select();
		}
		$rows = array_change_key($array, 'cate_id');
		$tree = [];
		foreach ($rows as $key => $value) {
			if($value['parent_id'] != $cate_id){
				$rows[$value['parent_id']]['sub'][] = &$rows[$key];
			}else{
				// 此时寻找到我们需要的最深处 parent_id = 0
				$tree[] = &$rows[$key];
			}
		}
		if($type == 'array'){
			$tree_to_array = [];
			foreach ($tree as $key => $value) {
				tree_to_array($value, 0, 'sub', $tree_to_array);
			}
			return $tree_to_array;
		}elseif($type == 'tree'){
			return $tree;
		}else{
			return null;
		}
	}
}

