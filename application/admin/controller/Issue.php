<?php
namespace app\admin\controller;
use qcloudcos\Cosapi;
/**
* 文章操作类
*/
class Issue extends Common {
	function __construct() {
		parent::__construct();
		$this->assign('admin', $this->admin);
	}

	function index() {
		$issues = $this->db->name('issue')->where(['is_del' => 0])->order('add_time desc')->paginate(10,false,['query' => request()->param()]);
		// 如果当前传入的页面大于总页数或小于第一页
		if(input("page") > $issues->lastPage()){
			header("Location:".$issues->url($issues->lastPage()));
		}
		if(input('page') < 0){
			header("Location:".$issues->url('1'));
		}
		$this->assign('issues', $issues);
		return $this->fetch();
	}

	function add($id = 0){
		if($id){
			$issue = $this->db->name('issue')->where(['id' => $id])->find();
			if(!$issue){
				return $this->alert('你所编辑的文章不存在', '', url('issue/index'));
			}
			$this->assign('issue', $issue);
		}
		$this->assign('id', $id);
		return $this->fetch();
	}

	// 获取全部标签
	function get_tag_list(){
		$tags = $this->db->name('tags')->column('value');
		output($tags);
	}

	// 文章上传
	function update($id = 0){
		set_time_limit(180);
		// echo "<pre>";
		// print_r($_POST);
		// exit;
		$cosapi = new Cosapi(config('monitor.appId'), config('monitor.secretId'), config('monitor.secretKey'));
		$cosapi->setRegion('sh');
		$cosapi->setTimeout(180);
		$bucket = 'blog';
		$data = $_POST;
		$data['is_top_time'] = strtotime($_POST['is_top_time']);
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
							$data['img_origin'] = $dst;
							break;
						}
					}
				}
			}
			if($id){
				$issue = $this->db->name('issue')->where(['id' => $id])->find();
				// 如果上传了文件，更新图片信息
				if(!empty($_FILES['pic']) && $_FILES['pic']['error'] == 0){
					if(!empty($issue['img_origin'])){
						$cosapi->delFile($bucket, $issue['img_origin']);
					}
				}
				if(!$this->db->name('issue')->where(['id' => $id])->update($data)){
					throw new \Exception('更新文章失败');
				}
			}else{
				if(!$this->db->name('issue')->insert($data)){
					throw new \Exception("新增文章失败", 1);
				}
				$id = $this->db->getLastInsID();
			}

			// 处理标签`
			$tags = explode(',', $_POST['tag']);
			$tags = trimarray($tags);
			$tag_data = $this->db->name('tags')->where('value in ("'.implode('","',$tags).'")')->select();
			if($tag_data){
				foreach ($tag_data as $key => $value) {
					$tag_data[$value['value']] = explode(',',$value['issue_ids']);
				}
			}
			// 生成更新数据
			$insert_data = [];
			foreach ($tags as $key => $tag) {
				$tag_data[$tag][] = $id;
				// id去重
				$tag_data[$tag] = implode(',',array_unique($tag_data[$tag]));
				$insert_data[] = "('$tag', '$tag_data[$tag]')";
			}
			$insertsql = "insert into ".config('database.prefix')."tags (value, issue_ids) values ".implode(',', $insert_data)." on duplicate key update issue_ids = values(issue_ids)";
			if($this->db->query($insertsql) === false){
				throw new \Exception("标签更新失败");
			}
			$this->db->commit();
		}catch (\Exception $e) {
			$this->db->rollback();
			if(!empty($data['img_origin'])){
				$cosapi->delFile($bucket, $data['img_origin']);
			}
			return $this->alert($e->getMessage(), '', 'javascript:history.go(-1)');
		}
		return $this->alert('添加文章成功', '', url("issue/index"));
	}

	// 删除文章
	function del(){
		if(empty(input("id/a"))){
			return $this->alert("参数错误", "请选择一篇文章", "javascript:history.go(-1)");
		}
		$id = input("id/a");
		$this->db->name('issue')->where('id', 'in', $id)->update(['is_del' => 1]);
		return $this->alert("删除成功", '', $_SERVER['HTTP_REFERER']);
	}
}

