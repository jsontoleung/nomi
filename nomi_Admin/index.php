<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]
namespace think;

define('ADMIN_PATH', 'https://www.nomiyy.com/index.php/admin/');
define('APP_PATH', __DIR__ . '/application/');
define('URL_PATH', "https://".$_SERVER['HTTP_HOST']);
defined('ROOT_PATH') or define('ROOT_PATH', dirname(realpath(APP_PATH)) . '/');
defined('RUNTIME_PATH') or define('RUNTIME_PATH', ROOT_PATH . 'runtime' . '/');
defined('CACHE_PATH') or define('CACHE_PATH', RUNTIME_PATH . 'cache' . '/');
defined('TEMP_PATH') or define('TEMP_PATH', RUNTIME_PATH . 'temp' . '/');
defined('FILE_PATH') or define('FILE_PATH', RUNTIME_PATH . 'file' . '/');

// -------------WeCha-----------------
define(APPID, 'wx1f5fdbdc76854173');
define(MCHID, '1536292911');//商户号
define(KEY, 'Nomi663866613710724516YmdHisNomi');//API-key
define(APPSECRET, 'c99eda3334fadc96cf117bbcba3625d3');

// 加载基础文件
require __DIR__ . '/thinkphp/base.php';


// 支持事先使用静态方法设置Request对象和Config对象

// 执行应用并响应
Container::get('app')->run()->send();
