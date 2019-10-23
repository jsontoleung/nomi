<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use app\common\config\Categorys;
use app\common\config\Upload;
use think\facade\Request;
use think\facade\Cache;
use think\facade\Env;
use think\File;

/*
** 分类 控制器
*/

class Category extends Adminbase {
	private static $_category = null; // 数据表对象

	// 优先加载
	public function  initialize() {
		parent::initialize();
		// 实例化数据表模型
		self::$_category = model('Category');
	}


	// 文章分类列表
	public function articlelist () {

		if (!$this->isAccess()) return view('common/common');

		if (Cache::get('artCategory')) {
			$lists = Cache::get('artCategory');
		} else {
			$lists = self::$_category->categoryLists();
			Cache::set('artCategory', $lists);
		}

		return view('articlelist', [
			'lists' => $lists,
		]);

	}


	// 添加文章列表
	public function addarticlelist () {

		if (!$this->isAccess()) return view('common/common');

		$lists = self::$_category->categoryLists();

		return view('addarticlelist', [
			'lists' => $lists,
		]);

	}


	// 修改文章列表
	public function editarticlelist () {

		if (!$this->isAccess()) return view('common/common');

		$id = Request::param('id');

		// 上级分类列表
		$categorylists = Categorys::categoryLists($id);
		$lists = self::$_category->find($id);
		
		return view('editarticlelist', [
			'categorylists' => $categorylists,
			'lists' => $lists,
		]);

	}


	// 添加、修改文章接口
	public function savearticlelist () {

		if (!$this->isAccess()) return view('common/common');

		if (Request::isPost()) {

			$inputs = Request::post();
			$file = Request::file('');

			if (empty($inputs['id'])) {

				if ($inputs['pid'] == 0) {
					$type = self::$_category->order('type desc')->value('type');
					$inputs['type'] = $type+1;
				}

				// 使用模型验证器进行验证
				$result = $this -> validate($inputs, 'Category.add');
				if(true !== $result){
					// 验证失败 输出错误信息
					return json(['status'=>0, 'msg'=>$result]);
				}

				// 验证新增数据 name 是否重复
				$this -> checkNameAdd($inputs);

				// 处理上传图片
				if (!empty($file)) {
					
					$files = $file['cover'];
					$cover = Upload::uploadOne($files, 'category');
					$inputs['cover'] = $cover;

				} else {
					$inputs['cover'] = '';
				}
				
				// 数据插入
				if(self::$_category->data($inputs)->save()){
					Cache::set('artCategory', null);
					return json(['status'=>1, 'msg'=>'操作成功']);
				}

			} else {

				// 使用模型验证器进行验证
				$result = $this -> validate($inputs, 'Category.edit');
				if(true !== $result){
					// 验证失败 输出错误信息
					return json(['status'=>0, 'msg'=>$result]);
				}
				
				// 验证更新数据 name 是否重复
				$this -> checkNameUpdate($inputs);

				// 处理上传图片
				if (!empty($file)) {

					// 删除原有的图片
					$photo = self::$_category->where(['id'=>$inputs['id']])->value('cover');
					$len = substr($photo,0, 5);

					if (($len != 'https') || ($len != 'http')) {
						
						if (!empty($photo)) {
							$filePath = Env::get('root_path').'public'.$photo;
							if (is_file($filePath)) {
								unlink($filePath);
							}
						}

					}
					
					$files = $file['cover'];
					
					$cover = Upload::uploadOne($files, 'category');
					$inputs['cover'] = $cover;

				} else {
					unset($inputs['cover']);
				}

				$cate = self::$_category->where('id=:id', ['id' => $inputs['id']])->find();
				
				// 更新path
				if($cate['pid'] > 0){
					// 获取上级菜单的 path
					$prentPath = self::$_category->where(array('id' => $cate['pid']))->value('path');
					
					$inputs['path'] = $prentPath . '-' . $inputs['id'];
				}else{
					$inputs['path']  = '0-' . $inputs['id'];
				}
				
				// 保存数据
				if(self::$_category->save($inputs, array('id' => $inputs['id']))){
					if (Cache::get('artCategory')) {
						Cache::set('artCategory', null);
					}
					return json(['status'=>1, 'msg'=>'操作成功']);
				}

			}

		}


	}


	// 删除
	public function deletes() {
		
		if (!$this->isAccess()) return view('common/common');
		
		if (Request::isPost()) {
			
			$id = Request::post('id');

			// 删除原有的图片
			$photo = self::$_category->field('cover')->where(['id'=>$id])->find();
			$len = substr($photo,0, 5);
			if (($len != 'https') || ($len != 'http')) {
				
				if (!empty($photo)) {
					$filePath = Env::get('root_path').$photo['cover'];
					if (is_file($filePath)) {
						unlink($filePath);
					}
				}
			}

			$result = self::$_category->deletes($id);
			if($result['status'] == 1){
				if (Cache::get('artCategory')) {
					Cache::set('artCategory', null);
				}
				return json(['status'=>1, 'msg'=>$result['msg']]);
			}
			return json(['status'=>0, 'msg'=>$result['msg']]);
		}
		
	} 


	// 验证新增数据 name 是否重复添加
	private function checkNameAdd($data) {
		$checkData = array('name' => $data['name']);
		$find = self::$_category->where($checkData) -> find();
		if($find){
			return json(['status'=>0, 'msg'=>'同样的记录已经存在']);
		}
		return true;
	}
	
	// 验证更新数据 name 是否重复添加
	private function checkNameUpdate($data) {
		
		$checkData = array('name' => $data['name']);
		$find = self::$_category->where($checkData) -> value('id');
		
		if($find && $find != $data['id']){
			return json(['status'=>0, 'msg'=>'同样的记录已经存在']);
		}
		return true;
	}




} // ---end