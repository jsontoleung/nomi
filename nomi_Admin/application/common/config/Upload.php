<?php 
namespace app\common\config;
use think\Validate;
use think\facade\Env;
use think\File;

/*
** 上传 控制器
*/

class Upload {

	/**
	 * @author 多上传图片
	 * @param $file 要上传的图片
	 * @param $dir 存放的文件夹
	 */
	public static function uploadMore($files, $dir='uploadMore') {

		$info = "";
		$data1 = "";
		$path = ROOT_PATH . 'uploads' . '/' . $dir . '/' . date('Ymd') . '/';

		if (!empty($files)) {
			
			foreach ($files as $file) {
				
				$config = array(
					'size' 	=> 55678000,
					'ext' 	=> 'jpg,gif,png,jpeg,bmp'
				);
				$info = $file->validate($config)->rule('uniqid')->move($path);

				if ($info) {

					$data1 .= '/uploads' . '/' . $dir . '/' .date('Ymd') . '/' . $info->getSaveName() . ",";

				} else {

					return $this->error($file->getError());

				}

			}

			$result = substr($data1, 0, strlen($data1)-1);

			return $result;

		} else {

			return '';

		}

	}// --end



	/**
	 * @author 单图片上传
	 * @param $file(上传的图片)
	 * @param $dir(存放图片的文件夹)
	 * @param $naem 为了更好的分类（自定义文件夹名）
	 */
	public static function uploadOne ($file, $dir, $name='') {

		$info = "";
		$data = "";
		$path = $name == '' ? ROOT_PATH . 'uploads/' . $dir . '/' . date('Ymd') . '/' : ROOT_PATH . 'uploads/' . $dir . '/' . date('Ymd') .  '/' . $name . '/';
		$config = array(
			'size' 	=> 55678000,
			'ext' 	=> 'jpg,gif,png,jpeg,bmp'
		);

		$info = $file->validate($config)->rule('uniqid')->move($path);
		if ($info) {

			if ($name == '') {
				$data .= '/uploads/' . $dir . '/' . date('Ymd') . '/' . $info->getSaveName();
			} else {
				$data .= '/uploads/' . $dir . '/' . date('Ymd') . '/' . $name . '/' . $info->getSaveName();
			}
			return $data;

		} else {

			return $file->getError();

		}

	}// --end


	/**
	 * @author 视频上传
	 * @param $file(上传的图片)
	 * @param $dir(存放图片的文件夹)
	 */
	public static function uploadVideo ($file, $dir='voice') {

		$info = "";
		$data = "";
		$path = ROOT_PATH . 'video/' . $dir . '/' . date('Ymd') . '/';
		$config = array(
			'size' 	=> 55678000,
			'ext' 	=> 'mpg,m4v,mp4,flv,3gp,mov,avi,rmvb,mkv,wmv,wav'
		);
		
		$info = $file->validate($config)->rule('uniqid')->move($path);
		if ($info) {

			$data .= '/video/' . $dir . '/' . date('Ymd') . '/' . $info->getSaveName();
			return $data;

		} else {

			return $file->getError();

		}

	}// --end



}