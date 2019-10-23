<?php
namespace app\common\model;
use think\Model;
use app\common\config\Upload;
use think\facade\Env;
use think\File;

/*
** Banner模型
*/

class Banner extends Model {

	public function bannerInfo () {

		$list = model('Banner')->select();
		foreach ($list as $k => $v) {

			// 所属分类
			$cate = model('Category')->field('name')->where('id=:id', ['id' => $v['cid']])->find();
			$list[$k]['name'] = $cate['name'];
			
			// 产品列表
			$pro = model('product')->field('name')->where('pro_id=:id', ['id'=>$v['proid']])->find();

			// 所属产品
			if ($v['proid'] == 0) {
				$list[$k]['proName'] = '无';
			} else {

				$list[$k]['proName'] = $pro['name'];

			}

		}
		return $list;

	}



	/**
	 * 删除banner图
	 */
	public function delBanner ($id) {

		$list = $this->where('banner_id=:id', ['id' => $id])->find();
		// 删除原有的图片
		$photo = $this->where(['banner_id'=>$list['banner_id']])->value('banner_img');
		$len = substr($photo,0, 5);
		if (($len != 'https') || ($len != 'http')) {
			
			if (!empty($photo)) {
				$filePath = Env::get('root_path').$photo;
				if (is_file($filePath)) {
					unlink($filePath);
				}
			}
		}
		$del = $this->where('banner_id=:id', ['id' => $id])->delete();
		if ($del) {
			return array('status' => 1, 'msg' => '删除成功');
		}
			return array('status' => 0, 'msg' => '删除失败');

	}



	/**
	 * 更改、添加banner图
	 */
	public function saveBanner ($data, $banner_img) {

		if (empty($data['banner_id'])) {
			
			if (empty($data['proid'])) {
				return array(['status' => 0, 'msg' => '请选择分类']);
			} elseif (empty($banner_img)) {
				return array(['status' => 0, 'msg' => '请上传banner封面']);
			} else {

				// 处理上传图片
				$cover = Upload::uploadOne($banner_img, 'banner');
				$data['banner_img'] = $cover;
				
				if ($this->data($data)->save()) {
					return array('status' => 1, 'msg' => '操作成功');
				}
					return array('status' => 0, 'msg' => '操作失败');
			}

		} else {

			if (empty($data['proid'])) {
				return array(['status' => 0, 'msg' => '请选择分类']);
			} elseif (empty($banner_img)) {
				unset($data['banner_img']);
			} else {

				// 删除原有的图片
				$photo = $this->where(['banner_id'=>$data['banner_id']])->value('banner_img');
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
				$cover = Upload::uploadOne($banner_img, 'banner');
				$data['banner_img'] = $cover;

			}

			$save = $this->isUpdate(false)->save($data, ['banner_id'=>$data['banner_id']]);
			if ($save) {
				return array('status' => 1, 'msg' => '操作成功');
			}
				return array('status' => 0, 'msg' => '操作失败');

		}

	}

	
}
