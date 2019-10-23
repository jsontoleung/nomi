<?php
namespace app\common\model;
use think\Model;
use app\common\config\Upload;
use think\facade\Env;
use think\File;

/*
** 主播模型
*/

class Anchor extends Model {


	public function anchorInfo () {

		$list = $this
			->field('an.anchor_id, u.nickname, an.anchor_name, an.anchor_job, an.anchor_school, an.anchor_headimg, an.anchor_info, an.care_num, an.status, an.create_time')
			->alias('an')
			->leftJoin('user u', 'u.user_id = an.uid')
			->order('an.anchor_id desc')
			->select();
		return $list;
	}



	/**
	 * 保存添加、修改
	 */
	public function anchorSave ($inputs, $file) {

		if (empty($inputs['anchor_id'])) {
			
			if (empty($file)) {
				return array('status' => 0, 'msg' => '请选择上传图片');
			} else {
				
				// 处理上传图片
				$cover = Upload::uploadOne($file, 'anchor', $inputs['anchor_name']);
				$inputs['anchor_headimg'] = $cover;
				$inputs['create_time'] = time();
				if ($this->data($inputs)->save()) {
					return array('status' => 1, 'msg' => '操作成功');
				}
				return array('status' => 0, 'msg' => '操作失败');
			}

		} else {

			if (empty($file)) {
				unset($inputs['anchor_headimg']);
				$save = $this->isUpdate(true)->save($inputs, ['anchor_id'=>$inputs['anchor_id']]);
				if ($save) {
					return array('status' => 1, 'msg' => '操作成功');
				}
				return array('status' => 0, 'msg' => '操作失败');

			} else {

				// 删除原有的图片
				$photo = $this->where(['anchor_id'=>$inputs['anchor_id']])->value('anchor_headimg');
				$len = substr($photo,0, 5);

				if (($len != 'https') || ($len != 'http')) {
					
					if (!empty($photo)) {
						$filePath = Env::get('root_path').$photo;
						if (is_file($filePath)) {
							unlink($filePath);
						}
					}

				}

				// 处理上传图片
				$cover = Upload::uploadOne($file, 'user', $inputs['anchor_name']);
				$inputs['anchor_headimg'] = $cover;
				$save = $this->isUpdate(true)->save($inputs, ['anchor_id'=>$inputs['anchor_id']]);
				if ($save) {
					return array('status' => 1, 'msg' => '操作成功');
				}
				return array('status' => 0, 'msg' => '操作失败');

			}
			
		}

	}


	/**
	 * 删除
	 */
	public function deletes ($id) {

		$this->startTrans();
		try {

			$photo = $this->where('anchor_id=:id', ['id'=>$id])->value('anchor_headimg');
			$len = substr($photo,0, 5);

				if (($len != 'https') || ($len != 'http')) {
					
					if (!empty($photo)) {
						$filePath = Env::get('root_path').'public'.$photo;
						if (is_file($filePath)) {
							unlink($filePath);
						}
					}

				}
			$del = $this->where(['anchor_id'=>$id])->delete();
			if ($del) {
				Db::commit();
				return (['status'=>1, 'msg'=>'删除成功']);
			}
			
		} catch (Exception $e) {
			Db::rollback();
			return (['status'=>0, 'msg'=>'等待事物提交']);
		}
		

	}


	
}
