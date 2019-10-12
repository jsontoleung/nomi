<?php 
namespace app\common\config;
use think\Db;

/*
** 分类 控制器
*/

class Categorys {

	/**
	 * @author 无限极递归评论
	 */
	public static function getCommlist($pid = 0,&$result = array()){       
		$arr = model('comment')->where("pid = '".$pid."'")->order("create_time desc")->select();

		if(empty($arr)){
			return array();
		}
		foreach ($arr as $cm) {  
			$thisArr=&$result[];
			$cm["children"] = Categorys::getCommlist($cm["comment_id"],$thisArr);    
			$thisArr = $cm;                                    
		}
		return $result;
	}





	/**
	 * 所属产品列表
	 */
	public static function proLists ($pickId = 0) {

		$lists = model('product')->field('pro_id, name')->order('pro_id desc')->select();

		$html = '<select name="proid" class="form-control m-b">';
		$html .= '<option value="0">--无所属产品--</option>';

		foreach($lists as $k => $v){
			// 被选中
			$selected = '';
			if($pickId == $v['pro_id']){
				$selected = 'selected';
			}
			$html .= '<option value="' . $v['pro_id'] . '" ' . $selected . '>' . $v['name'] . '</option>';
		}
		$html .= '</select>';
		
		return $html;


	}






	// 分类列表
	public static function categoryLists ($pickId = 0) {

		$lists = model('Category')
		    ->where(['status' => 1])
		    ->order('path', 'asc')
		    ->select();
		
		$html = '<select name="cid" class="form-control m-b">';
		$html .= '<option value="0">--选择分类--</option>';

		foreach($lists as $key => $val){
			// 被选中
			$selected = '';
			if($pickId == $val['id']){
				$selected = 'selected';
			}
			$html .= '<option value="' . $val['id'] . '" ' . $selected . '>' . get_tree_icons($val['path']) . $val['name'] . '</option>';
		}
		$html .= '</select>';
		
		return $html;
		
	}





	/**
	 * @author 音频类型
	 * @param $id['type']
	 */
	public static function voiceType ($vid = 0) {

		$type = model('voice')->where('voice_id=:id', ['id'=>$vid])->value('type');
		$type = empty($type) ? 0 : $type;

		$spec = model('Setting')->where(["name"=>"COMMONT_TYPE"])->value('values');
		$typeName = parse_config_attr($spec);
		
		$html = '<select name="type" class="form-control">';
		foreach ($typeName as $k => $v) {
			
			$selected = '';
			if ($type == $k) {
				$selected = 'selected';
			}

			$html .= '<option value="' . $k . '" ' . $selected . '>' . $v . '</option>';
		}
		$html .= '</select>';
		
		return $html;

	}




	/**
	 * @author 获取用户名
	 * @param $pid[用户id]
	 */
	public static function getUser ($pid = 0) {

		$lists = model('User')->field(['nickname, user_id'])->order('nickname asc')->select();
		$html = '<select name="uid" class="form-control">';

		$html .= '<option value="0">--请选择用户--</option>';

		foreach ($lists as $k => $v) {
			
			$selected = '';
			if ($pid == $v['user_id']) {
				$selected = 'selected';
			}

			$html .= '<option value="' . $v['user_id'] . '" ' . $selected . '>' . $v['nickname'] . '</option>';
		}
		$html .= '</select>';

		return $html;

	}





	/**
	 * @author 省份列表
	 * @param $province[省份id]
	 */
	public static function categoryProvince ($province = 0) {

		$lists = db('provinces')->order('province asc')->select();
		$html = '<select name="provinceid" class="form-control" style="width:100%">';

		$html .= '<option value="0">--选择省份--</option>';

		foreach ($lists as $k => $v) {

			if (empty($province)) {
				
				$html .= '<option value="' . $v['provinceid'] . '">' . $v['province'] . '</option>';

			} else {

				$selected = '';
				if ($province == $v['provinceid']) {
					$selected = 'selected';
				}

				$html .= '<option value="' . $v['provinceid'] . '" ' . $selected . '>' . $v['province'] . '</option>';

			}
			
		}
		$html .= '</select>';

		return $html;


	}




	/**
	 * @author 城市列表
	 * @param $city['城市id']
	 * @param $fatherID['省份id']
	 */
	public static function categoryCity ($fatherID=0, $city=null) {

		$lists = db('city')->where('provinceid=:father', ['father'=>$fatherID])->order('city asc')->select();
		
		$html = '<select name="cityid" class="form-control" style="width:100%;">';

		$html .= '<option value="0">--选择城市--</option>';

		foreach ($lists as $k => $v) {
			
			if (empty($city)) {

				$html .= '<option value="' . $v['cityid'] . '">' . $v['city'] . '</option>';
				
			} else {

				$selected = '';
				if ($city == $v['cityid']) {
					$selected = 'selected';
				}

				$html .= '<option value="' . $v['cityid'] . '" ' . $selected . '>' . $v['city'] . '</option>';

			}
			
		}
		$html .= '</select>';

		return $html;

	}




	/**
	 * @author 地区列表
	 * @param $area['地区id']
	 * @param $fatherID['城市id']
	 */
	public static function categoryArea ($fatherID=0, $area=null) {

		$lists = db('areas')->where('cityid=:father', ['father'=>$fatherID])->order('area asc')->select();
		$html = '<select name="areaid" class="form-control" style="width:100%;">';

		$html .= '<option value="0">--选择地区--</option>';

		foreach ($lists as $k => $v) {
			
			if (empty($area)) {
				
				$html .= '<option value="' . $v['areaid'] . '">' . $v['area'] . '</option>';

			} else {

				$selected = '';
				if ($area == $v['areaid']) {
					$selected = 'selected';
				}

				$html .= '<option value="' . $v['areaid'] . '" ' . $selected . '>' . $v['area'] . '</option>';

			}

		}
		$html .= '</select>';

		return $html;

	}





	/**
	 * @author 会员等级
	 * @param $levelId['level_id']
	 */
	public static function categoryLevel ($levelId = 0) {

		$lists = model('Userlevel')->field('level_id, level_type')->order('level_id asc')->select();

		$html = '<select name="level" class="form-control">';
		$html .= '<option value="0">--请选择会员等级--</option>';
		foreach ($lists as $k => $v) {
			
			$selected = '';
			if ($levelId == $v['level_id']) {
				$selected = 'selected';
			}

			$html .= '<option value="' . $v['level_id'] . '" ' . $selected . '>' . $v['level_type'] . '</option>';
		}
		$html .= '</select>';

		return $html;

	}




	/**
	 * 快递公司
	 */
	public static function categoryShipping () {

		$lists = model('Shipping')->select();

		$html = '<select name="logistic_name" class="form-control">';
		$html .= '<option value="0">--请选择快递公司--</option>';
		foreach ($lists as $k => $v) {
			
			$selected = '';

			$html .= '<option value="' . $v['code'] . '" ' . $selected . '>' . $v['ship_name'] . '</option>';
		}
		$html .= '</select>';

		return $html;

	}






	/**
	 * @author 渠道来源
	 * @param $cid['cid的值']
	 */
	public static function categoryChannel ($cid = 0) {

		$lists = model('Channel')->field('channel_id, channel_name')->order('channel_id desc')->select();

    	$html = '<select name="channel_id" class="form-control">';
		$html .= '<option value="0">--请设置渠道来源--</option>';
		foreach ($lists as $k => $v) {
			
			$selected = '';
			if ($cid == $v['channel_id']) {
				$selected = 'selected';
			}

			$html .= '<option value="' . $v['channel_id'] . '" ' . $selected . '>' . $v['channel_name'] . '</option>';
		}
		$html .= '</select>';

		return $html;

	}




	/**
	 * @author 店铺后台
	 * @param $id['后台的id']
	 */
	public static function categoryAdmins ($id = 0) {

		$lists = model('Admin')->field('id, nickname')->where(['type' => 1])->order('id desc')->select();

		$html = '<select name="admin_id" class="form-control">';
		$html .= '<option value="0">--请选择店铺后台--</option>';
		foreach ($lists as $k => $v) {
			
			$selected = '';
			if ($id == $v['id']) {
				$selected = 'selected';
			}

			$html .= '<option value="' . $v['id'] . '" ' . $selected . '>' . $v['nickname'] . '</option>';
		}
		$html .= '</select>';

		return $html;

	}






}