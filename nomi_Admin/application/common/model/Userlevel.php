<?php
namespace app\common\model;
use think\Model;

/*
** 积分等级设置模型
*/

class Userlevel extends Model {
	protected $table = 'nomi_user_level';


	public function levelInfo () {

		$list = $this->order('level_id asc')->select();
		foreach ($list as $k => $v) {
			
			$list[$k]['c_pond'] = $v['c_pond'] == 0 ? '' : $v['c_pond'];
			$list[$k]['one_level'] = $v['one_level'] == 0 ? '' : $v['one_level'];
			$list[$k]['second_level'] = $v['second_level'] == 0 ? '' : $v['second_level'];
			$list[$k]['b_pond'] = $v['b_pond'] == 0 ? '' : $v['b_pond'];
			$list[$k]['a_pond'] = $v['a_pond'] == 0 ? '' : $v['a_pond'];
			$list[$k]['p_pond'] = $v['p_pond'] == 0 ? '' : $v['p_pond'];
			$list[$k]['h_pond'] = $v['h_pond'] == 0 ? '' : $v['h_pond'];
			$list[$k]['t_pond'] = $v['t_pond'] == 0 ? '' : $v['t_pond'];
			$list[$k]['k_pond'] = $v['k_pond'] == 0 ? '' : $v['k_pond'];
			$list[$k]['s_pond'] = $v['s_pond'] == 0 ? '' : $v['s_pond'];
			$list[$k]['z_pond'] = $v['z_pond'] == 0 ? '' : $v['z_pond'];
			$list[$k]['g_pond'] = $v['g_pond'] == 0 ? '' : $v['g_pond'];
			$list[$k]['r_pond'] = $v['r_pond'] == 0 ? '' : $v['r_pond'];
			$list[$k]['n_pond'] = $v['n_pond'] == 0 ? '' : $v['n_pond'];
			$list[$k]['total'] = number_format((float)$v['c_pond'] + (float)$v['one_level'] + (float)$v['second_level'] + (float)$v['b_pond'] + (float)$v['a_pond'] + (float)$v['p_pond'] + (float)$v['h_pond'] + (float)$v['t_pond'] + (float)$v['k_pond'] + (float)$v['s_pond'] + (float)$v['z_pond'] + (float)$v['g_pond'] + (float)$v['r_pond'] + (float)$v['n_pond']);

		}
		return $list;

	}




	/**
	 * 保存添加、修改
	 */
	public function levelSave ($inputs) {

		if (empty($inputs['level_id'])) {

			if ($this->data($inputs)->save()) {
				return array('status' => 1, 'msg' => '操作成功');
			}
			return array('status' => 0, 'msg' => '操作失败');

		} else {

			if ($this->save($inputs, ['level_id'=>$inputs['level_id']])) {
				return array('status' => 1, 'msg' => '操作成功');
			}
			return array('status' => 0, 'msg' => '操作失败');

		}

	}



}
