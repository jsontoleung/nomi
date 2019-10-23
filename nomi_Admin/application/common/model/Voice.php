<?php
namespace app\common\model;
use think\Model;
use app\common\config\Upload;
use think\facade\Env;
use think\File;

/*
** 音频主模型
*/

class Voice extends Model {


	public function voiceInfo () {

		$list = $this->field('v.voice_id, v.title, v.type, v.cid, v.proid, v.cover, v.cover_detail, v.content, v.play_num, v.share_num, v.info, v.status, v.create_time, c.id, c.name')
			->alias('v')
			->leftJoin('category c', 'c.id = v.cid')
			->order('v.voice_id desc')
			->select();

		foreach ($list as $k => $v) {

			// 产品列表
			$pro = model('product')->field('name')->where('pro_id=:id', ['id'=>$v['proid']])->find();
			
			// 分类列表
			$category = model('category')->where('id=:id', ['id'=>$v['cid']])->find();

			// 已发布数量（音频）
			// $detail = model('VoiceDetail')->where(['voiceid' => $v['voice_id']])->count();

			// $list[$k]['publish_num'] = $detail ? $detail : 0;

			// 点赞量
			$like_num = model('like')->where(['voiceid' => $v['voice_id']])->count();

			$list[$k]['like_num'] = $like_num;

			// 收藏量
			$collect_num = model('collect')->where(['voiceid' => $v['voice_id']])->count();

			$list[$k]['collect_num'] = $collect_num;

			// 所属产品
			if ($v['proid'] == 0) {
				$list[$k]['proName'] = '无';
			} else {

				$list[$k]['proName'] = $pro['name'];

			}

			// 文章分类
			if ($category['pid'] > 0) {
				$top = model('category')->where('id=:pid', ['pid'=>$category['pid']])->find();
				$list[$k]['name'] = $top['name'].'--'. $category['name'];
			} else {
				$list[$k]['name'] = $category['name'];
			}

		}
		return $list;

	}


	/**
	 * 保存添加、修改
	 */
	public function voiceSave ($inputs, $file, $cover_detail) {

		$inputs['update_time'] = time();
		
		if (empty($inputs['voice_id'])) {
			
			if (empty($file) || empty($cover_detail)) {
				return array('status' => 0, 'msg' => '请选择上传图片');
			} else {
				
				// 处理上传封面
				$cover = Upload::uploadOne($file, 'article');
				$inputs['cover'] = $cover;
				// 处理上传详情封面
				$cover_two = Upload::uploadOne($cover_detail, 'article');
				$inputs['cover_detail'] = $cover_two;
				$inputs['create_time'] = time();

				if ($this->data($inputs)->save()) {
					return array('status' => 1, 'msg' => '操作成功');
				}
				return array('status' => 0, 'msg' => '操作失败');
			}

		} else {

			if (empty($file) && empty($cover_detail)) {
				unset($inputs['cover']);
				unset($inputs['cover_detail']);
				$save = $this->isUpdate(true)->save($inputs, ['voice_id'=>$inputs['voice_id']]);
				if ($save) {
					return array('status' => 1, 'msg' => '操作成功');
				}
				return array('status' => 0, 'msg' => '操作失败');

			} elseif (!empty($file) && empty($cover_detail)) {
				
				unset($inputs['cover_detail']);
				// 删除原有的图片
				$photo = $this->where(['voice_id'=>$inputs['voice_id']])->value('cover');
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
				$cover = Upload::uploadOne($file, 'article');
				$baseCover = imgToBase64($cover);
				p($baseCover);die;
				$inputs['cover'] = $cover;

				$save = $this->isUpdate(true)->save($inputs, ['voice_id'=>$inputs['voice_id']]);
				if ($save) {
					return array('status' => 1, 'msg' => '操作成功');
				}
				return array('status' => 0, 'msg' => '操作失败');

			} elseif (empty($file) && !empty($cover_detail)) {
				
				unset($inputs['cover']);
				// 删除原有的图片
				$photo_two = $this->where(['voice_id'=>$inputs['voice_id']])->value('cover_detail');
				$len_two = substr($photo_two,0, 5);

				if (($len_two != 'https') || ($len_two != 'http')) {
					
					if (!empty($photo_two)) {
						$filePath_two = Env::get('root_path').$photo_two;
						if (is_file($filePath_two)) {
							unlink($filePath_two);
						}
					}

				}
				// 处理上传详情图片
				$cover_two = Upload::uploadOne($cover_detail, 'article');
				$inputs['cover_detail'] = $cover_two;
				
				$save = $this->isUpdate(true)->save($inputs, ['voice_id'=>$inputs['voice_id']]);
				if ($save) {
					return array('status' => 1, 'msg' => '操作成功');
				}
				return array('status' => 0, 'msg' => '操作失败');

			} else {

				// 删除原有的图片
				$photo = $this->where(['voice_id'=>$inputs['voice_id']])->value('cover');
				$len = substr($photo,0, 5);

				if (($len != 'https') || ($len != 'http')) {
					
					if (!empty($photo)) {
						$filePath = Env::get('root_path').$photo;
						if (is_file($filePath)) {
							unlink($filePath);
						}
					}

				}

				// 删除原有的图片
				$photo_two = $this->where(['voice_id'=>$inputs['voice_id']])->value('cover_detail');
				$len_two = substr($photo_two,0, 5);

				if (($len_two != 'https') || ($len_two != 'http')) {
					
					if (!empty($photo_two)) {
						$filePath_two = Env::get('root_path').$photo_two;
						if (is_file($filePath_two)) {
							unlink($filePath_two);
						}
					}

				}

				// 处理上传图片
				$cover = Upload::uploadOne($file, 'article');
				$inputs['cover'] = $cover;
				// 处理上传详情图片
				$cover_two = Upload::uploadOne($cover_detail, 'article');
				$inputs['cover_detail'] = $cover_two;

				$save = $this->isUpdate(true)->save($inputs, ['voice_id'=>$inputs['voice_id']]);
				if ($save) {
					return array('status' => 1, 'msg' => '操作成功');
				}
				return array('status' => 0, 'msg' => '操作失败');

			}
			
		}

	}



	
}
