<?php
namespace app\common\model;
use think\Model;

/*
** 店铺表模型
*/

class Shop extends Model {

	public function shopList () {

		$list = $this->field('s.*, a.nickname, c.channel_name, pic.province, cy.city, ar.area')
			->alias('s')
			->leftJoin('admin a', ['s.admin_id = a.id'])
			->leftJoin('channel c', ['s.channel_id = c.channel_id'])
			->leftJoin('provinces pic', ['s.province = pic.provinceid'])
			->leftJoin('city cy', ['s.city = cy.cityid'])
			->leftJoin('areas ar', ['s.area = ar.areaid'])
			->order('s.shop_id desc')
			->select();
		foreach ($list as $k => $v) {
			if (empty($v['nickname'])) {
				$v['nickname'] = '暂无后台账号';
			}
		}
		return $list;
	}



	// 添加、修改保存
	public function shopSave ($data) {

		if (empty($data['shop_id'])) {
			
			$this->startTrans();
			try {

				$admin = model('Admin')->where('id=:id', ['id' => $data['admin_id']])->find();
				if (empty($admin)) {

					return array('status' => 0, 'msg' => '请添加店铺后台');

				} elseif ($admin['is_select'] == 1) {

					return array('status' => 0, 'msg' => '该后台已有人选了,请添加店铺后台');

				} else {

					$addShop = $this->data($data)->save();

					$inputs['is_select'] = 1;
					$saveAdmin = model('Admin')->save($inputs, ['id' => $data['admin_id']]);

					if ($addShop && $saveAdmin) {
						$this->commit();
						return array('status' => 1, 'msg' => '操作成功');
					} else {
						return array('status' => 0, 'msg' => '操作失败');
					}

				}
				return array('status' => 0, 'msg' => '操作失败');
				
			} catch (Exception $e) {
				$this->rollback();
				throw $e;
			}


		} else {

			$this->startTrans();
			try {

				$shop = $this->where('shop_id=:id', ['id' => $data['shop_id']])->find();

				$admin = model('Admin')->where('id=:id', ['id' => $data['admin_id']])->find();

				if (empty($data['admin_id'])) {
					return array('status' => 0, 'msg' => '请添加店铺后台');
				} else {

					if (empty($admin)) {
						$saveAdminOne = 1;
					} else {
						if ($admin['is_select'] == 1 && ($shop['admin_id'] != $data['admin_id'])) {
							return array('status' => 0, 'msg' => '该后台已有人选了,请添加店铺后台');
						}
						$data2['is_select'] = 0;
						$saveAdminOne = model('Admin')->save($data2, ['id' => $admin['id']]);
					}
					
				}

				$editShop = $this->save($data, ['shop_id' => $data['shop_id']]);

				$inputs['is_select'] = 1;
				$saveAdminTwo = model('Admin')->save($inputs, ['id' => $data['admin_id']]);

				if ($editShop && $saveAdminOne && $saveAdminTwo) {
					$this->commit();
					return array('status' => 1, 'msg' => '操作成功');
				} else {
					return array('status' => 0, 'msg' => '操作失败');
				}
				
			} catch (Exception $e) {
				$this->rollback();
				throw $e;
			}

		}

	}


}
