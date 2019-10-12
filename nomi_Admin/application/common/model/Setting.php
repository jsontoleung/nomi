<?php
namespace app\common\model;
use think\facade\Env;
use think\File;
use app\common\config\Upload;
use think\Model;

/*
** 设置模型
*/

class Setting extends Model {

	/**
	 * $adverImg 广告图
	 * $loginImg 登录封面
	 */
	public function settingSave ($inputs, $file, $adverImg, $loginImg) {

		if (empty($file) && empty($adverImg) && empty($loginImg)) {
			unset($inputs['adminIcn']);
			unset($inputs['adverImg']);
			unset($inputs['loginImg']);
			foreach ($inputs as $key => $value) {
				$data['values'] = $value;
				$save = $this->isUpdate(true)->save($data, ['name'=>$key]);
			}
			if ($save) {
				return array('status' => 1, 'msg' => '操作成功');
			}
			return array('status' => 0, 'msg' => '操作失败');

		} else {

			if (!empty($file)) {
				$cover = Upload::uploadOne($file, 'setting', 'icon');
				$inputs['ADMIN_ICN'] = $cover;
				unset($inputs['adverImg']);
				unset($inputs['loginImg']);
			} elseif (!empty($adverImg)) {
				$adverImg = Upload::uploadOne($adverImg, 'setting', 'adverImg');
				$inputs['ADVER_PHOTO'] = $adverImg;
				unset($inputs['adminIcn']);
				unset($inputs['loginImg']);
			} elseif (!empty($loginImg)) {
				$loginImg = Upload::uploadOne($loginImg, 'setting', 'loginImg');
				$inputs['LOGIN_PHOTO'] = $loginImg;
				unset($inputs['adminIcn']);
				unset($inputs['adverImg']);
			}

			
			
			foreach ($inputs as $key => $value) {
				$data['values'] = $value;
				$save = $this->isUpdate(true)->save($data, ['name'=>$key]);
			}
			if ($save) {
				return array('status' => 1, 'msg' => '操作成功');
			}
			return array('status' => 0, 'msg' => '操作失败');
		}

	}



	
}
