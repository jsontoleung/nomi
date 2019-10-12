<?php
namespace app\common\model;
use think\Model;
use app\common\config\Upload;
use think\facade\Env;
use think\File;

/*
** 音频详情模型
*/

class VoiceDetail extends Model {
	protected $table = 'nomi_voice_detail';


	public function detailInfo ($id) {

		$list = $this->where('voiceid=:id', ['id'=>$id])
			->order('detail_id asc')
			->select();
		return $list;
	}


	/**
	 * 保存添加、修改
	 */
	public function detailSave ($inputs, $file, $video='') {

		if (empty($inputs['detail_id'])) {

			if (empty($file)) { // 没有上传图片
				return array('status' => 0, 'msg' => '请选择上传图片');
			} else {
				
				if (empty($video)) {//没有上传视频
					// 处理上传图片
					$cover = Upload::uploadOne($file, 'voice', 'detail');
					$inputs['cover'] = $cover;
					$inputs['create_time'] = time();
					if ($this->data($inputs)->save()) {
						return array('status' => 1, 'msg' => '操作成功');
					}
				} else {
					// 获取视频尺寸
					$size = $video->getSize();
					// 对长度四舍五入保留3位小数
					$inputs['longtime'] = round($inputs['longtime'], 3);
					
					$detailvideo = Upload::uploadVideo($video);
					$cover = Upload::uploadOne($file, 'voice', 'detail');
					$inputs['video'] = $detailvideo;
					$inputs['size'] = $size;
					$inputs['cover'] = $cover;
					$inputs['create_time'] = time();
					if ($this->data($inputs)->save()) {
						return array('status' => 1, 'msg' => '操作成功');
					}
				}

				
				return array('status' => 0, 'msg' => '操作失败');
			}

		} else {

			if (empty($file) && empty($video)) { // 没有上传
				// 删除input传过来的空值
				unset($inputs['cover']);
				unset($inputs['video']);
				unset($inputs['longtime']);
				$save = $this->isUpdate(true)->save($inputs, ['detail_id'=>$inputs['detail_id']]);
				if ($save) {
					return array('status' => 1, 'msg' => '操作成功');
				}
				return array('status' => 0, 'msg' => '操作失败');

			} elseif (empty($file) && !empty($video)) {// 只上传视频
				
				// 删除input上传图片
				unset($inputs['cover']);
				// 查找数据库视频，删除原有的视频再上传
				$shiping = $this->where(['detail_id'=>$inputs['detail_id']])->value('video');
				if (!empty($shiping)) {
					$len = substr($shiping,0, 5);
					if (($len != 'https') || ($len != 'http')) {
						if (!empty($shiping)) {
							$filePath = Env::get('root_path').$shiping;
							if (is_file($filePath)) {
								unlink($filePath);
							}
						}
					}
				}

				// 获取视频尺寸
				$size = $video->getSize();
				// 对长度四舍五入保留3位小数
				$inputs['longtime'] = round($inputs['longtime'], 3);

				// 处理上传视频
				$detailvideo = Upload::uploadVideo($video);
				$inputs['video'] = $detailvideo;
				$inputs['size'] = $size;
				$save = $this->isUpdate(true)->save($inputs, ['detail_id'=>$inputs['detail_id']]);
				if ($save) {
					return array('status' => 1, 'msg' => '操作成功');
				}
				return array('status' => 0, 'msg' => '操作失败');

			} elseif (!empty($file) && empty($video)) {// 只上传图片

				unset($inputs['video']);
				unset($inputs['longtime']);
				// 删除原有的图片
				$photo = $this->where(['detail_id'=>$inputs['detail_id']])->value('cover');
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
				$cover = Upload::uploadOne($file, 'voice', 'detail');
				$inputs['cover'] = $cover;
				$save = $this->isUpdate(true)->save($inputs, ['detail_id'=>$inputs['detail_id']]);
				if ($save) {
					return array('status' => 1, 'msg' => '操作成功');
				}
				return array('status' => 0, 'msg' => '操作失败');

			} elseif (!empty($file) && !empty($video)) {// 全部上传
				
				// 查找数据库，删除原有的再上传
				$shiping = $this->where(['detail_id'=>$inputs['detail_id']])->value('video');
				$photo = $this->where(['detail_id'=>$inputs['detail_id']])->value('cover');
				// 删除原有视频
				if (!empty($shiping)) {
					$lenVideo = substr($shiping,0, 5);
					if (($lenVideo != 'https') || ($lenVideo != 'http')) {
						if (!empty($shiping)) {
							$photoPath = Env::get('root_path').$shiping;
							if (is_file($photoPath)) {
								unlink($photoPath);
							}
						}
					}
				}
				// 删除原有图片
				if (!empty($photo)) {
					$lenPhoto = substr($photo,0, 5);
					if (($lenPhoto != 'https') || ($lenPhoto != 'http')) {
						if (!empty($photo)) {
							$videoPath = Env::get('root_path').$photo;
							if (is_file($videoPath)) {
								unlink($videoPath);
							}
						}
					}
				}

				// 获取视频尺寸
				$size = $video->getSize();
				// 对长度四舍五入保留3位小数
				$inputs['longtime'] = round($inputs['longtime'], 3);

				// 处理上传图片
				$detailvideo = Upload::uploadVideo($video);
				$cover = Upload::uploadOne($file, 'voice', 'detail');
				$inputs['video'] = $detailvideo;
				$inputs['size'] = $size;
				$inputs['cover'] = $cover;
				$save = $this->isUpdate(true)->save($inputs, ['detail_id'=>$inputs['detail_id']]);
				if ($save) {
					return array('status' => 1, 'msg' => '操作成功');
				}
				return array('status' => 0, 'msg' => '操作失败');

			}
			return array('status' => 0, 'msg' => '操作失败');
			
		}

	}


	
}
