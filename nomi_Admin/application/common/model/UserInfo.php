<?php
namespace app\common\model;
use think\Model;

/*
** 用户个人信息模型
*/

class UserInfo extends Model {
	protected $table = 'nomi_user_info';

	/**
	 * 保存个人信息
	 */
	public function personalSave ($data) {

		$data['birth'] = time($data['birth']);
		$data['province'] = $data['provinceid'];
		$data['city'] = $data['cityid'];
		$data['area'] = $data['areaid'];
		unset($data['provinceid']);
		unset($data['cityid']);
		unset($data['areaid']);
		if(!preg_match("/^1[345678]{1}\d{9}$/", $data['phone'])){
		    return (['status'=>0, 'msg'=>'请填写正确的手机号码']);
		}

		$userInfo = $this->where('uid=:id', ['id' => $data['uid']])->find();
		if (empty($userInfo)) {	// 添加
			
			if ($this->data($data)->save()) {
				return (['status'=>1, 'msg'=>'操作成功']);
			}
			return (['status'=>0, 'msg'=>'操作失败']);

		} else { // 修改

			if ($this->save($data, ['uid' => $data['uid']])) {
				return (['status'=>1, 'msg'=>'操作成功']);
			}
			return (['status'=>0, 'msg'=>'操作失败']);

		}

	}


}
