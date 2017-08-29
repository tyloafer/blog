<?php
namespace app\admin\controller;
use qcloudcos\Cosapi;
/**
* 分享操作类
*/
class Shadowsock extends Common {
    function __construct() {
        parent::__construct();
        $this->assign('admin', $this->admin);
    }

    function index() {
        $ssacounts = $this->db->name('ssacount')->select();
        $this->assign('ssacounts',$ssacounts);
        return $this->fetch();
    }

    // 删除ss
    function del(){
        if(empty(input("id/a"))){
            return $this->alert("参数错误", "请选择一篇分享", "javascript:history.go(-1)");
        }
        $id = input("id/a");
        $this->db->name('ssacount')->delete($id);
        return $this->alert("删除成功", '', $_SERVER['HTTP_REFERER']);
    }
}

