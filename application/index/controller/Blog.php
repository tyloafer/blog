<?php
namespace app\index\controller;
use app\admin\model\Cate;
/**
* 博客
*/
class Blog extends Common {

	function index($cate_id = 0, $title = '', $tag_id = 0){
		if($tag_id != 0){
			$issue_ids = $this->db->name('tags')->where(['tag_id' => $tag_id])->column('issue_ids');
			$issue_ids = implode(',', $issue_ids);
			$blogs     = $this->db->name('issue')->where(['id' => ['in', $issue_ids]])->order('sort asc, add_time desc')->paginate(10,false,['query' => request()->param()]);
		}else{
			if($cate_id != 0){
				// 获取其子分类
				$cateModel = new Cate();
				$cateArray = $this->db->name('cate')->field('cate_id, cate_name, parent_id')->where(['target' => 'issue'])->order('cate_sort')->select();
				$cateData  = $cateModel->get_cate_tree($cate_id, $cateArray);
				$cateIds   = implode(',', array_merge(array_column($cateData, 'cate_id'),array($cate_id)));
				$blogs     = $this->db->name('issue')->where(['is_del' => 0, 'cate_id' => ['in', $cateIds], 'title' => ['like', "%$title%"]])->order('sort asc, add_time desc')->paginate(10,false,['query' => request()->param()]);
			}else{
				$blogs = $this->db->name('issue')->where(['is_del' => 0, 'title' => ['like', "%$title%"]])->order('sort asc, add_time desc')->paginate(10,false,['query' => request()->param()]);
			}
		}
		
		$items = $blogs->all();
		foreach ($items as $key => $blog) {
			$items[$key]['add_time']   = date('Y-m-d', $blog['add_time']);
			$items[$key]['img_origin'] = $blog['img_origin'] ? config('imgUrl').$blog['img_origin'] : '';
			$items[$key]['abstract']   = $blog['abstract'] ? $blog['abstract'] : mb_substr($blog['content'], 0, 100, 'utf-8');
		}
		$blogs->setItems($items);
		// 获取tags
		$tags = $this->db->name('tags')->where('issue_ids != ""')->select();
		// 如果当前传入的页面大于总页数或小于第一页
		if(input("page") > $blogs->lastPage()){
			header("Location:".$blogs->url($blogs->lastPage()));
		}
		if(input('page') < 0){
			header("Location:".$blogs->url('1'));
		}
		// 添加是否是叶子节点标志
		for($i = 0; isset($this->issue_cates[$i]); $i++){
			if(isset($this->issue_cates[$i+1]) && $this->issue_cates[$i+1]['deep'] <= $this->issue_cates[$i]['deep'] || !isset($this->issue_cates[$i+1])){
				$this->issue_cates[$i]['leaf'] = 1;
			}else{
				$this->issue_cates[$i]['leaf'] = 0;
			}
		}

		$this->assign('blogCates', $this->issue_cates);
		$this->assign('tags', $tags);
		$this->assign('blogs', $blogs);

		return $this->fetch();
	}

	public function detail($id = 0){
		if(!$id){
			return "请选择一篇文章";
		}
		$blog = $this->db->name('issue')->where(['id' => $id])->find();
		$blog['add_time'] = date('Y-m-d', $blog['add_time']);
		$blog['img_origin'] = config('imgUrl').$blog['img_origin'];
		$this->assign('blog', $blog);
		return $this->fetch();
	}
}