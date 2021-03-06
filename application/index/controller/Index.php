<?php
namespace app\index\controller;

class Index extends Common {
    public function index()
    {
    	// 获取最新作品
    	$works = $this->db->name('works')->field('id, img, title, link')->where(['is_del' => 0])->order('add_time desc')->limit(6)->select();
    	$this->assign('works', $works);

    	// 获取最新博客
    	$blogs = $this->db->name('issue')->field('id, title, abstract, add_time, img_origin')->where(['is_del' => 0])->order('add_time desc')->limit(3)->select();
    	$imgUrl = config('imgUrl');
    	foreach ($blogs as $key => $blog) {
    		$blogs[$key]['add_time'] = date("M\nd\nY", $blog['add_time']);
    		$blogs[$key]['img_origin'] = $blog['img_origin'] ? $imgUrl.$blog['img_origin'] : '';
    	}
    	$this->assign('blogs', $blogs);

    	// 获取最新分享
    	$shares = $this->db->name('share')->field('id, img, title, link, pass')->where(['is_del' => 0])->order('add_time desc')->limit(6)->select();
    	$this->assign('shares', $shares);

    	// 获取文章总数
    	$count['blogs'] = $this->db->name('issue')->where(['is_del' => 0])->count();

    	// 获取分享总数
    	$count['share'] = $this->db->name('share')->where(['is_del' => 0])->count();

    	// 获取作品总数
    	$count['works'] = $this->db->name('works')->where(['is_del' => 0])->count();

    	$this->assign('count', $count);
    	return $this->fetch();
    }

    public function index2(){
    	echo 333333;
    }
}
