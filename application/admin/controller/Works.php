<?php
namespace app\admin\controller;
use qcloudcos\Cosapi;
/**
* 分享操作类
*/
class Works extends Common {
    function __construct() {
        parent::__construct();
        $this->assign('admin', $this->admin);
    }

    function index($cate_id = 0, $title = '') {
        error_reporting(E_ALL && ~E_NOTICE);
        $condition['is_del'] = 0;

        if($title){
            $condition['title'] = ['like','%'.$title.'%'];
        }
        $this->assign('title', $title);
        $works = $this->db->name('works')->where($condition)->order('add_time desc')->paginate(10,false,['query' => request()->param()]);
        // 如果当前传入的页面大于总页数或小于第一页
        if(input("page") > $works->lastPage()){
            header("Location:".$works->url($works->lastPage()));
        }
        if(input('page') < 0){
            header("Location:".$works->url('1'));
        }
        $this->assign('works', $works);
        return $this->fetch();
    }

    function add($id = 0){
        if($id){
            $works = $this->db->name('works')->where(['id' => $id])->find();
            if(!$works){
                return $this->alert('你所编辑的分享不存在', '', url('works/index'));
            }
            $works['showImg'] = empty($works['showImg']) ? array() : explode(';', $works['showImg']);
            $this->assign('works', $works);
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
        $data['is_top'] == 'on' ? $data['is_top'] = 1 : $data['is_top'] = 0;
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
                $works = $this->db->name('works')->where(['id' => $id])->find();
                // 如果上传了文件，更新图片信息
                if(!empty($_FILES['pic']) && $_FILES['pic']['error'] == 0){
                    if(!empty($works['img_origin'])){
                        $cosapi->delFile($bucket, $works['img_origin']);
                    }
                }
                // 删除可能已经删除的内容图片
                $imgdel = array_diff(explode(';', $works['showImg']), $data['showImg']);
                if(!empty($imgdel)){
                    foreach ($imgdel as $value) {
                        $cosapi->delFile($bucket, $value);
                    }
                }
                $data['showImg'] = empty($data['showImg']) ? '' : implode(';', $data['showImg']);
                if(!$this->db->name('works')->where(['id' => $id])->update($data)){
                    throw new \Exception('更新分享失败');
                }
            }else{
                $data['showImg'] = empty($data['showImg']) ? '' : implode(';', $data['showImg']);
                if(!$this->db->name('works')->insert($data)){
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
        return $this->alert('编辑分享成功', '', url("works/index"));
    }

    // 删除分享
    function del(){
        if(empty(input("id/a"))){
            return $this->alert("参数错误", "请选择一篇分享", "javascript:history.go(-1)");
        }
        $id = input("id/a");
        $this->db->name('works')->where('id', 'in', $id)->update(['is_del' => 1]);
        return $this->alert("删除成功", '', $_SERVER['HTTP_REFERER']);
    }
}

