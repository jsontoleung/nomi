<?php
namespace app\common\model;
use think\Model;
use app\common\config\Upload;
use think\facade\Env;
use think\File;
/*
** 文章模型
*/

class Article extends Model {

	public function articleInfo ($id=null) {

		if (empty($id)) {
			
			$list = $this->field('art.article_id, art.cid, art.check_type, art.check_num, art.title, art.cover, art.content, art.click, art.like, art.post_num, art.recommend, art.status, art.check, art.keyword, art.source, art.intro, art.create_time, ad.name as adname, c.name as cname')
				->alias('art')
				->leftJoin('admin ad', 'art.adminid = ad.id')
				->leftJoin('category c', 'art.cid = c.id')
				->order('art.create_time')
				->select();

		} else {

			$list = $this->field('art.article_id, art.cid, art.check_type, art.check_num, art.title, art.cover, art.content, art.click, art.like, art.post_num, art.recommend, art.status, art.check, art.keyword, art.source, art.intro, art.create_time, ad.name as adname, c.name as cname')
				->alias('art')
				->leftJoin('admin ad', 'art.adminid = ad.id')
				->leftJoin('category c', 'art.cid = c.id')
				->where('art.article_id=:id', ['id' => $id])
				->order('art.create_time')
				->select();

		}

		foreach ($list as $k => $v) {
			
			$category = model('category')->where('id=:id', ['id'=>$v['cid']])->find();
			if ($category['pid'] > 0) {
				$top = model('category')->where('id=:pid', ['pid'=>$category['pid']])->find();
				$list[$k]['cname'] = $top['name'].'--'. $category['name'];
			} else {
				$list[$k]['cname'] = $category['name'];
			}

		}
		return $list;
	}


	/**
	 * 保存添加、修改
	 */
	public function articleSave ($inputs, $file) {

		if (empty($inputs['article_id'])) {
			
			if (empty($file)) {
				return array('status' => 0, 'msg' => '请选择上传图片');
			} else {

				// 处理上传图片
				$cover = Upload::uploadOne($file, 'article', session('username'));
				$inputs['adminid'] = session('uid');
				$inputs['cover'] = $cover;
				$inputs['create_time'] = time();
				if ($this->data($inputs)->save()) {
					return array('status' => 1, 'msg' => '操作成功');
				}
				return array('status' => 0, 'msg' => '操作失败');

			}

		} else {

			if (empty($file)) {
				unset($inputs['cover']);
				$inputs['update_time'] = time();
				$save = $this->isUpdate(true)->save($inputs, ['article_id'=>$inputs['article_id']]);
				if ($save) {
					return array('status' => 1, 'msg' => '操作成功');
				}
				return array('status' => 0, 'msg' => '操作失败');
			} else {

				// 删除原有的图片
				$photo = $this->where(['article_id'=>$inputs['article_id']])->value('cover');
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
				$cover = Upload::uploadOne($file, 'article', session('username'));
				$inputs['cover'] = $cover;
				$save = $this->isUpdate(true)->save($inputs, ['article_id'=>$inputs['article_id']]);
				if ($save) {
					return array('status' => 1, 'msg' => '操作成功');
				}
				return array('status' => 0, 'msg' => '操作失败');

			}

		}

	}

	
}
