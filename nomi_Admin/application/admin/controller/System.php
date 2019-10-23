<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\facade\Request;
use think\facade\Config;
use think\facade\Cache;
/*
** 系统设置 控制器
*/

class System extends Adminbase {
	private static $_setting = null; // 数据表对象

	// 优先加载
	public function  initialize() {
		parent::initialize();
		// 实例化数据表模型
		self::$_setting = model('Setting');


	}

	/**
	 * 清除缓存
	 */
	public function clearCache () {

		if (delete_dir_file(CACHE_PATH) && delete_dir_file(TEMP_PATH)) {
	        return json(['status' => 1, 'msg' => '清除缓存成功']);
	    } else {
	        return json(['status' => 0, 'msg' => '清除缓存失败']);
	    }

	}



	/**
	 * 系统设置
	 */
	public function setting () {

		if (!$this->isAccess()) return view('common/common');

		if (Cache::get('settingInfo')) {
			$lists = Cache::get('settingInfo');
		} else {
			$lists = self::$_setting->order('id asc')->select();
			Cache::set('settingInfo', $lists);
		}
		foreach ($lists as $k => $v) {
			if ($v['type'] > 2) {
				$lists[$k]['extert'] = parse_config_attr($v['extert']);
			}
		}
		// 保存设置
		if (Request::isPost()) {
			
			$inputs = Request::post();
			$adminIcn = Request::file('adminIcn');
			$adverImg = Request::file('adverImg');
			$loginImg = Request::file('loginImg');

			$save = self::$_setting->settingSave($inputs, $adminIcn, $adverImg, $loginImg);
			if ($save['status'] == 1) {
				Cache::set('settingInfo', null);
				return json(['status'=>1, 'msg'=>'操作成功']);
			}
			return json(['status'=>0, 'msg'=>'操作失败']);

		}

		return view('setting',[
			'lists' => $lists,
		]);
	}



	/**
	 * 配置管理
	 */
	public function deploy () {

		if (!$this->isAccess()) return view('common/common');
		
		if (Request::isPost()) {
			$inputs = Request::post();
			if ($inputs['type'] > 2) {
				$inputs['extert'] = $inputs['values'];
				unset($inputs['values']);
			}

			if (self::$_setting->save($inputs)) {
				Cache::set('settingInfo', null);
				return json(['status'=>1, 'msg'=>'操作成功']);
			}
			return json(['status'=>0, 'msg'=>'操作失败']);
		}

		return view('deploy');
	}



	/**
	 * 左侧菜单排序
	 */
	public function sortmenu() {

		if (!$this->isAccess()) return view('common/common');

		if (Cache::get('sortmenu')) {
            $menu = Cache::get('sortmenu');
        } else {
            $menu = model('Menu')->field(['id, name, sort'])->where(['pid' => 0])->order('sort')->select();
            Cache::set('sortmenu', $menu);
        }

		$data = Request::post();
		if (!empty($data)) {
			
			// 合并两个数组。其中的一个数组元素为键名，另一个数组元素为键值
			$tmp = array_combine($data['id'], $data['sort']);
			$result = '0';
			foreach ($tmp as $k => $v) {
				$res = model('Menu')->where(['id' => $k])->update(['sort' => $v]);
				if ($res == 1) {
					$result = 1;
				}
			}
			if($result == 1){
                cache('sortmenu', null);
                return json(['status'=>1, 'msg' => '更改成功']);
            }else{
                return json(['status'=>0, 'msg' => '更改失败']);
            }

		}

		return view('sortmenu', [
			'menu' => $menu,
		]);

	}



	/**
	 * 数据库备份
	 */
    public function baksql () {   

    	if (!$this->isAccess()) return view('common/common');

        //获取操作内容：（备份/下载/还原/删除）数据库
        $type=input('type');

        //获取需要操作的数据库名字
        $name=input('name');
        $backup = new \org\Baksql(Config::get("database."));

        $list = array_reverse($backup->get_filelist());
        
        switch ($type) {
            //备份
            case "system":
                $info = $backup->backup();
                $this->success("$info", 'system/baksql');
                break;
            //下载
            case "dowonload":
                $info = $backup->downloadFile($name);
                $this->success("$info", 'system/baksql');
                break;
            //还原
            case "restore":
                $info = $backup->restore($name);
                $this->success("$info", 'system/baksql');
                break;
            //删除
            case "del":
                $info = $backup->delfilename($name);
                $this->success("$info", 'system/baksql');
                break;
    
        }

        // 日期搜索
        $startime = Request::param('startime');
        $endtime = Request::param('endtime');

        if (isset($_GET['startime']) && $_GET['startime'] && isset($_GET['endtime']) && $_GET['endtime']) {
            
            // 检查时间日期的合法性
            $startime = input('param.startime');
            $endtime = input('param.endtime');
            $starTimes = date('Y-m-d', strtotime($startime));
            $endTimes = date('Y-m-d', strtotime($endtime));
            if($startime === $starTimes){ }else{
                echo '<script>alert("起始时间不合法！");</script>';
                $startime = '';
            }
            if($endtime === $endTimes){ }else{
                echo '<script>alert("结束时间不合法！");</script>';
                $endtime = '';
            }
            $start = strtotime($startime);
            $end = strtotime($endtime);
            
            if($start > $end){
                echo '<script>alert("开始时间不能大于结束时间！");</script>';
                $startime = '';
                $endtime = '';
            }

            foreach ($list as $k => $v) {
                if ( (strtotime($v['time']) > $start) && (strtotime($v['time']) < $end) ) {
                    $list[$k] = $v;
                } else {
                    unset($list[$k]);
                    // $list[$k] = array_values($v);
                }
            }
            
        } else {
            $startime = '';
            $endtime = '';
        }
        
        return view("baksql", [
            "lists" => $list,
        ]);//将信息由新到老排序

    }



}