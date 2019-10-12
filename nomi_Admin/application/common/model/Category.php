<?php
namespace app\common\model;
use app\common\model\Common;


/*
** 文章分类表 模型
*/

class Category extends Common {
	
	// 注册模型事件
	protected static function init() {
		// 新增后事件
		Category::afterInsert(function($_category) {
			// 获取传入的数据
			$data = $_category;
			
			// 处理 path、topid
			$id = $data['id'];
			$pid = $data['pid'];
			if($pid > 0){
				// 获取上级菜单的 path
				$prentPath = $_category -> where(array('id' => $pid)) -> value('path');
				
				$newData['path'] = $prentPath . '-' . $id;
			}else{
				$newData['path']  = '0-' . $id;
			}
			
			// 保存
			$_category->save($newData, array('id' => $id));
			$_category->reCache();
		});

		// 更新后事件
		Menu::afterUpdate(function($_category) {
			// 更新缓存
			$_category->categoryLists();
		});

	}


	// 更新缓存
	protected function reCache() {
		cache('category', null);
		$data = $this->categoryLists();
		cache('category', $data);
	}
	

	// 获取分类列表
	public function categoryLists() {
		// 按 path 升序
		$lists = $this->order('path', 'asc')->select()->toArray();
		return $lists;
	}


	// 获取分类列表[只显示status==1]
	public function categoryStatus() {
		// 按 path 升序
		$lists = $this -> where(['status' => 1]) -> order('path', 'asc') -> select();
		
		return $lists;
	}
	

	// 检测是否有子分类
	public function hasChild($id) {
		
		$has = $this -> where(array('pid' => $id)) -> value('id');
		if($has){
			return true;
		}
		return false;
	}
	

	// 删除分类
	public function deletes($id) {
		// 验证 id
		$find = $this -> where(array('id' => $id)) -> value('id');
		if(!$find){
			return array('status' => 0, 'msg' => '参数错误');
		}
		
		// 检测是否有子分类
		if($this -> hasChild($id)){
			return array('status' => 0, 'msg' => '有子分类，不能删除');
		}
		
		// 检测是否有文章
		// if(model('Article') -> where(array('cid' => $id)) -> find()){
		// 	return array('status' => 0, 'msg' => '本分类下已有文章，不能删除');
		// }
		
		// 执行删除
		if($this -> where(array('id' => $id)) -> delete()){
			return array('status' => 1, 'msg' => '删除成功');
		}
		return array('status' => 0, 'msg' => '删除失败');
	}
	
}
