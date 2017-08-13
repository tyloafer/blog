<?php
namespace app\admin\controller;

/**
* 分类操作类
*/
class Cate extends Common {
	function __construct() {
		parent::__construct();
		$this->assign('admin', $this->admin);
	}

	// 分类列表
	function index(){
		echo input('target');
		error_reporting(E_ALL & ~E_NOTICE);
		$cateModel = Model('Cate');
		$target = input('target');
		$array = $this->db->name('cate')->where(['target' => $target])->order('cate_sort')->select();
		$cates = $cateModel->get_cate_tree(0, $array);
		// 获取全部文章的cate
		$issues = $this->db->name('issue')->field('id, cate_id')->where(['is_del' => 0])->select();
		// 循环获取每个分类下的子分类
		foreach ($cates as $key => $cate) {
			$sub_cate = $cateModel->get_cate_tree($cate['cate_id'], $array);
			$sub_cate = array_merge(array($cate['cate_id']), array_column($sub_cate, 'cate_id'));
			$count = 0;
			// 循环查找分类下的文章总数
			foreach ($issues as $issue) {
				if(in_array($issue['cate_id'], $sub_cate)){
					$count ++;
				}
			}
			// 判断是否有下级
			if($cates[$key+1]['deep'] > $cate['deep']){
				$cates[$key]['sub'] = 1;
			}else{
				$cates[$key]['sub'] = 0;
			}
			$cates[$key]['count'] = $count;
		}
		$this->assign('cates', $cates);
		return $this->fetch();
	}

	function get_cate_data($cate_id){
		if(!$cate_id){
			error('缺少cate_id');
		}
		$data = $this->db->name('cate')->where(['cate_id' => $cate_id])->find();
		if(empty($data)){
			error('暂无此分类信息');
		}else{
			output($data);
		}
	}

	/**
	 * 修改添加分类信息
	 * @return [type] [description]
	 */
	function update(){
		$args = ['cate_id', 'cate_name', 'parent_id', 'cate_sort', 'is_show'];
		$data = input();
		foreach ($data as $key => $value) {
			if(!in_array($key, $args)){
				unset($data[$key]);
			}
		}
		if(empty($data['cate_id'])){
			$this->db->name('cate')->insert($data);
			return $this->alert('添加分类成功', '', url('cate/index', ['target' => 'issue']));
		}else{
			$this->db->name('cate')->update($data);
			return $this->alert('修改分类成功', '', url('cate/index', ['target' => 'issue']));
		}
	}

	/**
	 * 删除分类
	 */
	function del($cate_id = 0){
		if(!$cate_id){
			error('缺少分类信息');
		}
		// 获取子分类
		$cate = $cateModel->get_cate_tree($cate_id);
		$cates = array_column($cate, 'cate_id');
		$cates[] = $cate_id;
		$this->db->name('cate')->delete($cates);
		return $this->alert("删除分类成功", "", url('cate/index', ['target' => 'issue']));
	}

}