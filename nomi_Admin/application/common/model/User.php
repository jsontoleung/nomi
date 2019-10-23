<?php
namespace app\common\model;
use app\common\config\Upload;
use think\facade\Env;
use think\File;
use think\Model;

/*
** 用户模型
*/

class User extends Model {

	/**
	 * 用户信息
	 */
	public function getUserInfo ($channel_id = '') {

		if(empty($channel_id)) {

			$lists = model('User')
				->alias('u')
				->field('u.*, ul.level_type, c.channel_name')
				->leftJoin('user_level ul', ['u.level = ul.level_id'])
				->leftJoin('channel c', ['u.channel_id = c.channel_id'])
				->order('user_id desc')
				->select();

		} else {

			$lists = model('User')
				->alias('u')
				->field('u.*, ul.level_type, c.channel_name')
				->leftJoin('user_level ul', ['u.level = ul.level_id'])
				->leftJoin('channel c', ['u.channel_id = c.channel_id'])
				->where('u.channel_id=:id', ['id' => $channel_id])
				->order('user_id desc')
				->select();

		}
		
		

		if (!empty($lists)) {
			
			foreach ($lists as $k => $v) {

				$topName = $this->where('user_id=:pid', ['pid'=>$v['pid']])->value('nickname');
				$lists[$k]['pid'] = $topName;
				if (empty($lists[$k]['pid'])) {
					$lists[$k]['pid'] = '顶级用户';
				}
				if (empty($v['channel_name'])) {
					$lists[$k]['channel_name'] = '请设置渠道';
				}
				
			}

		}
		return $lists;
	}



	/**
	 * @author 	保存修改
	 * @param 	$inputs--要修改的数据
	 * @param 	$files--上传图片
	 */
	public function userSave ($inputs, $file) {

		// 检测上级用户
		if (empty($inputs['topOnenid'])) {
			$inputs['pid'] = 0;
		} else {

			$userid = $this->where(['accont' => $inputs['topOnenid']])->value('user_id');
			if (empty($userid)) {
				return array('status' => 0, 'msg' => '输入的账户错误或不存在');
			} else {
				$inputs['pid'] = $userid;
			}
		
		}
		unset($inputs['topOnenid']);

		if (empty($inputs['user_id'])) {

			if (empty($file)) {
				return array('status' => 0, 'msg' => '请选择上传图片');
			} else {
				
				// 处理上传图片
				$cover = Upload::uploadOne($file, 'user', $inputs['nickname']);
				$inputs['headimg'] = $cover;
				$inputs['accont'] = 'nomiya'.date('His').random_string(4);
				$inputs['register_type'] = 1;
				$inputs['register_time'] = time();
				if ($this->data($inputs)->save()) {
					return array('status' => 1, 'msg' => '操作成功');
				}
				return array('status' => 0, 'msg' => '操作失败');
			}
			
		} else {

			if (empty($file)) {
				unset($inputs['headimg']);
				$save = $this->isUpdate(true)->save($inputs, ['user_id'=>$inputs['user_id']]);
				if ($save) {
					return array('status' => 1, 'msg' => '操作成功');
				}
				return array('status' => 0, 'msg' => '操作失败');

			} else {

				// 删除原有的图片
				$photo = $this->where(['user_id'=>$inputs['user_id']])->value('headimg');

				if (!empty($photo)) {

					if (substr($photo, 0, 4) != 'http') {

						$filePath = Env::get('root_path').$photo;
						if (is_file($filePath)) {

							unlink($filePath);

						} else {
							return array('status' => 0, 'msg' => '图片路径有误');
						}

					}

				} else {
					return array('status' => 0, 'msg' => '删除原有图片失败');
				}

				// 处理上传图片
				$cover = Upload::uploadOne($file, 'user', $inputs['nickname']);
				$inputs['headimg'] = $cover;
				$save = $this->isUpdate(true)->save($inputs, ['user_id'=>$inputs['user_id']]);
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

		// $photo = $this->where('user_id=:id', ['id'=>$id])->value('headimg');
		
		// if (!empty($photo)) {

		// 	if (substr($photo, 0, 4) != 'http') {

		// 		$filePath = Env::get('root_path').$photo;
		// 		if (is_file($filePath)) {

		// 			unlink($filePath);

		// 		} else {
		// 			return array('status' => 0, 'msg' => '图片路径有误');
		// 		}

		// 	}

		// }
		
		$del = $this->where(['user_id'=>$id])->delete();

		if ($del) {
			return (['status'=>1, 'msg'=>'删除成功']);
		}
		

	}



	/**
	 * 所属渠道来源信息
	 */
	public function getChannelInfo ($cid) {

		$lists = model('User')
				->alias('u')
				->field('u.*, ul.level_type, c.name as channel_name')
				->leftJoin('user_level ul', ['u.level = ul.level_id'])
				->leftJoin('category c', ['u.cid = c.id'])
				->where('cid=:id', ['id' => $cid])
				->order('user_id desc')
				->select();
		if (!empty($lists)) {
			
			foreach ($lists as $k => $v) {

				$topName = $this->where('user_id=:pid', ['pid'=>$v['pid']])->value('nickname');
				$lists[$k]['pid'] = $topName;
				if (empty($lists[$k]['pid'])) {
					$lists[$k]['pid'] = '顶级用户';
				}
				if (empty($v['channel_name'])) {
					$lists[$k]['channel_name'] = '请设置渠道';
				}
				
			}

		}
		return $lists;

	}


	
}
