<?php
namespace app\admin\controller;
use qcloudcos\Cosapi;
/**
* 分享操作类
*/
class Share extends Common {
    function __construct() {
        parent::__construct();
        $this->assign('admin', $this->admin);
    }

    function index($cate_id = 0, $title = '') {
        error_reporting(E_ALL && ~E_NOTICE);
        $condition['is_del'] = 0;
        // 获取分类
        $cateModel = Model('Cate');
        $cateInfo = $this->db->name('cate')->where(['target' => 'share'])->select();
        if(empty($cateInfo)) {
            return $this->fetch();
        }
        $cates = $cateModel->get_cate_tree(0, $cateInfo);
        $this->assign('cates', $cates);

        if($cate_id){
            $cate_ids = array_merge(array_column($cateModel->get_cate_tree($cate_id, $cateInfo), 'cate_id'), array($cate_id));
            $condition['c.cate_id'] = ['in', implode(',', $cate_ids)];
        }
        if($title){
            $condition['title'] = ['like','%'.$title.'%'];
        }
        $this->assign('cate_id', $cate_id);
        $this->assign('title', $title);
        $shares = $this->db->name('share')->field('i.*, c.cate_name')->alias('i')->join('cate c', 'i.cate_id = c.cate_id', 'left')->where($condition)->order('add_time desc')->paginate(10,false,['query' => request()->param()]);
        // 如果当前传入的页面大于总页数或小于第一页
        if(input("page") > $shares->lastPage()){
            header("Location:".$shares->url($shares->lastPage()));
        }
        if(input('page') < 0){
            header("Location:".$shares->url('1'));
        }
        $this->assign('shares', $shares);
        return $this->fetch();
    }

    function add($id = 0){
        // error_reporting(0);
        // 获取分类
        $cateModel = Model('Cate');
        $cateInfo = $this->db->name('cate')->where(['target' => 'share'])->select();
        $cates = $cateModel->get_cate_tree(0, $cateInfo);
        $this->assign('cates', $cates);
        if($id){
            $share = $this->db->name('share')->where(['id' => $id])->find();
            if(!$share){
                return $this->alert('你所编辑的分享不存在', '', url('share/index'));
            }
            $share['showImg'] = empty($share['showImg']) ? array() : explode(';', $share['showImg']);
            $this->assign('share', $share);
        }
        $this->assign('id', $id);
        return $this->fetch();
    }

    // 分享上传
    function update($id = 0){
        set_time_limit(180);
        $cosapi = new Cosapi(config('monitor.appId'), config('monitor.secretId'), config('monitor.secretKey'));
        $cosapi->setRegion('sh');
        $cosapi->setTimeout(180);
        $bucket = 'blog';
        $data = $_POST;
        $data['add_time'] = time();
        $this->db->startTrans();
        try{
            if(!empty($_FILES['pic']) && $_FILES['pic']['error'] == 0){
                // 存在文件，则上传
                // 获取images下是否有当天的文件夹
                $td_fold = date("Ymd", time());
                $ret = $cosapi->statFolder($bucket, '/images'.'/'.$td_fold);
                if($ret['code'] != 0){
                    // 说明此文件夹不存在，新建
                    $cosapi->createFolder($bucket, '/images'.'/'.$td_fold);
                }
                // 将文件上传到该文件夹下
                while(true){
                    $type = 'png';
                    if($_FILES['pic']['type'] == 'image/jpeg'){
                        $type = 'jpg';
                    }
                    $dst = '/images'.'/'.$td_fold.'/'.time().mt_rand(0,100).'.'.$type;
                    $ret = $cosapi->stat($bucket, $dst);
                    if($ret['code'] != 0){
                        // 文件不存在的情况下上传
                        $ret = $cosapi->upload($bucket, $_FILES['pic']['tmp_name'], $dst);
                        if($ret['code'] != 0){
                            throw new \Exception($ret['message']);
                            break;
                        }else{
                            $data['img'] = $dst;
                            break;
                        }
                    }
                }
            }
            // 上传内容轮播图
            for ($i = 0; isset($_FILES['file']['error'][$i]) && $_FILES['file']['error'][$i] == 0; $i++) {
                // 获取images下是否有当天的文件夹
                $td_fold = date("Ymd", time());
                $ret = $cosapi->statFolder($bucket, '/images'.'/'.$td_fold);
                if($ret['code'] != 0){
                    // 说明此文件夹不存在，新建
                    $cosapi->createFolder($bucket, '/images'.'/'.$td_fold);
                }
                // 将文件上传到该文件夹下
                while(true){
                    $type = 'png';
                    if($_FILES['pic']['type'] == 'image/jpeg'){
                        $type = 'jpg';
                    }
                    $dst = '/images'.'/'.$td_fold.'/'.time().mt_rand(0,100).'.'.$type;
                    $ret = $cosapi->stat($bucket, $dst);
                    if($ret['code'] != 0){
                        // 文件不存在的情况下上传
                        $ret = $cosapi->upload($bucket, $_FILES['file']['tmp_name'][$i], $dst);
                        if($ret['code'] != 0){
                            throw new \Exception($ret['message']);
                            break;
                        }else{
                            $data['showImg'][] = $dst;
                            break;
                        }
                    }
                }
            }
            if($id){
                $share = $this->db->name('share')->where(['id' => $id])->find();
                // 如果上传了文件，更新图片信息
                if(!empty($_FILES['pic']) && $_FILES['pic']['error'] == 0){
                    if(!empty($share['img_origin'])){
                        $cosapi->delFile($bucket, $share['img_origin']);
                    }
                }
                // 删除可能已经删除的内容图片
                $imgdel = array_diff(explode(';', $share['showImg']), $data['showImg']);
                if(!empty($imgdel)){
                    foreach ($imgdel as $value) {
                        $cosapi->delFile($bucket, $value);
                    }
                }
                $data['showImg'] = empty($data['showImg']) ? '' : implode(';', $data['showImg']);
                if(!$this->db->name('share')->where(['id' => $id])->update($data)){
                    throw new \Exception('更新分享失败');
                }
            }else{
                $data['showImg'] = empty($data['showImg']) ? '' : implode(';', $data['showImg']);
                if(!$this->db->name('share')->insert($data)){
                    throw new \Exception("新增分享失败", 1);
                }
                $id = $this->db->getLastInsID();
            }
            $this->db->commit();
        }catch (\Exception $e) {
            $this->db->rollback();
            if(!empty($data['img_origin'])){
                $cosapi->delFile($bucket, $data['img_origin']);
            }
            return $this->alert($e->getMessage(), '', 'javascript:history.go(-1)');
        }
        return $this->alert('编辑分享成功', '', url("share/index"));
    }

    // 删除分享
    function del(){
        if(empty(input("id/a"))){
            return $this->alert("参数错误", "请选择一篇分享", "javascript:history.go(-1)");
        }
        $id = input("id/a");
        $this->db->name('share')->where('id', 'in', $id)->update(['is_del' => 1]);
        return $this->alert("删除成功", '', $_SERVER['HTTP_REFERER']);
    }
}

