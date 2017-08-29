<?php
namespace app\index\controller;
use think\Controller;
use app\admin\model\Cate;

class Common extends Controller {
	public $db = null;
    public $issue_cates = null;
    public $share_cates = null;
    public function __construct() {
    	parent::__construct();
    	if(!$this->db) {
    		$this->db = db();
    	}
    	// 获取博客分类信息
        $this->issue_cates = $this->db->name('cate')->field('cate_id, cate_name, parent_id')->where(['target' => 'issue', 'is_show' => 1])->order('cate_sort')->select();
    	$this->share_cates = $this->db->name('cate')->field('cate_id, cate_name, parent_id')->where(['target' => 'share', 'is_show' => 1])->order('cate_sort')->select();
        $cate = new Cate();
    	$this->issue_cates = $cate->get_cate_tree(0, $this->issue_cates);
        // 开始构建导航栏的的无限级分类
        if(!($issue_cate_tem = cache('issue_cate_tem'))){
            $issue_cate_tem = '<ul class="dropdown-menu" role="menu">';
            $stack = [];
            for($i = 0; $i < count($this->issue_cates); $i++){
                if(isset($this->issue_cates[$i+1]) && $this->issue_cates[$i+1]['deep'] > $this->issue_cates[$i]['deep']){
                    // 下一级为这一级的子分类，将此时的deep入栈
                    array_push($stack, $this->issue_cates[$i]['deep']);
                    $issue_cate_tem .= '<li class="dropdown-submenu"><a href="%s" class="dropdown-submenu">%s</a><ul class="dropdown-menu">';
                }elseif(isset($this->issue_cates[$i+1]) && $this->issue_cates[$i+1]['deep'] == $this->issue_cates[$i]['deep']){
                    // 此时为叶子节点，直接结束
                    $issue_cate_tem .= '<li class="dropdown-submenu"><a href="%s" class="dropdown-submenu">%s</a></li>';
                }elseif(!isset($this->issue_cates[$i+1]) || $this->issue_cates[$i+1]['deep'] < $this->issue_cates[$i]['deep']) {
                    // 上一个父子关系结束
                    // 循环出栈，以确定输出关闭标签
                    $issue_cate_tem .= '<li class="dropdown-submenu"><a href="%s" class="dropdown-submenu">%s</a></li>';
                    while(!empty($stack) && end($stack) >= @$this->issue_cates[$i+1]['deep']){
                        array_pop($stack);
                        $issue_cate_tem .= '</ul></li>';
                    }
                }
                $issue_cate_tem = sprintf($issue_cate_tem, url('blog/index', ['cate_id' => $this->issue_cates[$i]['cate_id']]), $this->issue_cates[$i]['cate_name']);
            }
            $issue_cate_tem .= '</ul>';
            cache('issue_cate_tem', $issue_cate_tem);
        }
        $this->share_cates = $cate->get_cate_tree(0, $this->share_cates);
        // 开始构建导航栏的的无限级分类
        if(!($share_cates_tem = cache('share_cates_tem'))){
            $share_cates_tem = '<ul class="dropdown-menu" role="menu">';
            $stack = [];
            for($i = 0; $i < count($this->share_cates); $i++){
                if(isset($this->share_cates[$i+1]) && $this->share_cates[$i+1]['deep'] > $this->share_cates[$i]['deep']){
                    // 下一级为这一级的子分类，将此时的deep入栈
                    array_push($stack, $this->share_cates[$i]['deep']);
                    $share_cates_tem .= '<li class="dropdown-submenu"><a href="%s" class="dropdown-submenu">%s</a><ul class="dropdown-menu">';
                }elseif(isset($this->share_cates[$i+1]) && $this->share_cates[$i+1]['deep'] == $this->share_cates[$i]['deep']){
                    // 此时为叶子节点，直接结束
                    $share_cates_tem .= '<li class="dropdown-submenu"><a href="%s" class="dropdown-submenu">%s</a></li>';
                }elseif(!isset($this->share_cates[$i+1]) || $this->share_cates[$i+1]['deep'] < $this->share_cates[$i]['deep']) {
                    // 上一个父子关系结束
                    // 循环出栈，以确定输出关闭标签
                    $share_cates_tem .= '<li class="dropdown-submenu"><a href="%s" class="dropdown-submenu">%s</a></li>';
                    while(!empty($stack) && end($stack) >= @$this->share_cates[$i+1]['deep']){
                        array_pop($stack);
                        $share_cates_tem .= '</ul></li>';
                    }
                }
                $share_cates_tem = sprintf($share_cates_tem, url('share/index', ['cate_id' => $this->share_cates[$i]['cate_id']]), $this->share_cates[$i]['cate_name']);
            }
            $share_cates_tem .= '</ul>';
            cache('share_cates_tem', $share_cates_tem);
        }
        $this->assign('issue_cate_tem', $issue_cate_tem);
        $this->assign('share_cates_tem', $share_cates_tem);

        // 获取最新文章
        $recent_blogs = $this->db->name('issue')->field('id, title, img_origin, author')->where(['is_del' => 0])->order('add_time desc')->limit(3)->select();
        $this->assign('recent_blogs', $recent_blogs);
        // 获取最新作品
        $recent_works = $this->db->name('works')->field('id, title, img, link')->where(['is_del' => 0])->order('add_time desc')->limit(3)->select();
        $this->assign('recent_works', $recent_works);
    }
}
