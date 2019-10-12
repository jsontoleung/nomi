<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;


/*
** 后台 ueditor 上传 图片 控制器
*/

class Ueditor extends Adminbase {
	
	// 优先加载
	public function _initialize() {
		parent::_initialize();
		
	}
	
	// 编辑器内上传图片
	private $stateMap = array(
		'SUCCESS', //上传成功标记，在UEditor中内不可改变，否则flash判断会出错
		'文件大小超出 upload_max_filesize 限制',
		'文件大小超出 MAX_FILE_SIZE 限制',
		'文件未被完整上传',
		'没有文件被上传',
		'上传文件为空',
		'ERROR_TMP_FILE' => '临时文件错误',
		'ERROR_TMP_FILE_NOT_FOUND' => '找不到临时文件',
		'ERROR_SIZE_EXCEED' => '文件大小超出网站限制',
		'ERROR_TYPE_NOT_ALLOWED' => '文件类型不允许',
		'ERROR_CREATE_DIR' => '目录创建失败',
		'ERROR_DIR_NOT_WRITEABLE' => '目录没有写权限',
		'ERROR_FILE_MOVE' => '文件保存时出错',
		'ERROR_FILE_NOT_FOUND' => '找不到上传文件',
		'ERROR_WRITE_CONTENT' => '写入文件内容错误',
		'ERROR_UNKNOWN' => '未知错误',
		'ERROR_DEAD_LINK' => '链接不可用',
		'ERROR_HTTP_LINK' => '链接不是http链接',
		'ERROR_HTTP_CONTENTTYPE' => '链接contentType不正确',
		'INVALID_URL' => '非法 URL',
		'INVALID_IP' => '非法 IP'
	);
	
	// Ueditor 方法 Controller.php，初始化
	public function doupload() {
		date_default_timezone_set('Asia/chongqing');
		error_reporting(E_ERROR);
		header('Content-Type: text/html; charset=utf-8');
		$CONFIG = json_decode(preg_replace('/\/\*[\s\S]+?\*\//', '', file_get_contents('./static/admin/function/ueditor/config.json')), true);
		$action = input('param.action');
		
		// // 存储目录
		// $mySavePath = 'partError';
		// if(isset($_GET['savepath']) && $_GET['savepath']){
			$mySavePath = input('param.savepath');
		// }

		switch ($action) {
			case 'config':
				$result =  json_encode($CONFIG);
				break;
			
			/* 上传图片 */
			case 'uploadimage':
				$result = $this -> upload_image($mySavePath);
				break;
			
			/* 上传涂鸦 */
			case 'uploadscrawl':
			
			/* 上传视频 */
			case 'uploadvideo':
			
			/* 上传文件 */
			
			case 'uploadfile':
				//$result = include("action_upload.php");
				break;
			
			/* 列出图片 */
			case 'listimage':
				//$result = include("action_list.php");
				break;
			
			/* 列出文件 */
			case 'listfile':
				//$result = include("action_list.php");
				break;
			
			/* 抓取远程文件 */
			case 'catchimage':
				//$result = $this -> get_remote_image($mySavePath);
				break;
			
			default:
				$result = json_encode(array(
					'state'=> '请求地址出错'
				));
				break;
		}
		
		/* 输出结果 */
		if(isset($_GET['callback'])){
			if (preg_match('/^[\w_]+$/', $_GET['callback'])) {
				echo htmlspecialchars($_GET['callback']) . '(' . $result . ')';
			}else{
				echo json_encode(array(
					'state'=> 'callback参数不合法'
				));
			}
		} else {
			echo $result;
		}
	}
	
	// 上传图片
	private function upload_image($mySavePath) {
		
		$file = request() -> file('upfile');
		
		$config = array(
				'size' 	=> 3145728,
				'ext' 	=> 'jpg,gif,png,jpeg,bmp'
			);
		$savePath = ROOT_PATH . 'uploads/' . $mySavePath;
		
		if($file){
			$info = $file -> validate($config) -> move($savePath);
			if($info){
				// 获取图片路径
				$url = URL_PATH . '/uploads/' . $mySavePath . '/' . $info -> getSaveName();
				$title = $oriName =  URL_PATH . '/uploads/' . $mySavePath . '/' . $info -> getFilename();
				
				// 上传成功
				$state = 'SUCCESS';
			}else{
				// 上传失败
				$state = $info -> getError();
			}
		}
		
		$response=array(
				'state' 	=> $state,
				'url' 		=> $url,
				'title' 	=> $title,
				'original' 	=> $oriName,
			);
		
		return json_encode($response);
	}
	
	// 拉取远程图片
	private function get_remote_image($mySavePath) {
		$source = array();
		if(isset($_POST['source'])){
			$source = $_POST['source'];
		}else{
			$source = $_GET['source'];
		}
		
		$item = array(
				'state' 	=> '',
				'url' 		=> '',
				'size' 		=> '',
				'title' 	=> '',
				'original' 	=> '',
				'source' 	=> ''
			);
		$date = date('Ym');
		//远程抓取图片配置
		$config = array(
				// 保存路径
				'savePath' => './uploads/' . $mySavePath . '/' . $date . '/',
				//文件允许格式
				'allowFiles' => array('.gif' , '.png' , '.jpg' , '.jpeg' , '.bmp'),
				//文件大小限制，单位KB
				'maxSize' => 3000,
		);
		
		$list = array();
		foreach($source as $imgUrl) {
			$return_img = $item;
			$return_img['source'] = $imgUrl;
			$imgUrl = htmlspecialchars($imgUrl);
			$imgUrl = str_replace('&amp;', '&', $imgUrl);
			// http开头验证
			if(strpos($imgUrl, 'http') !== 0){
				$return_img['state'] = $this->stateMap['ERROR_HTTP_LINK'];
				array_push($list, $return_img);
				continue;
			}
			
			// 格式验证(扩展名验证和Content-Type验证)
			$fileType = strtolower(strrchr($imgUrl, '.'));
			if(!in_array($fileType, $config['allowFiles']) || stristr($heads['Content-Type'], 'image')){
				$return_img['state'] = $this->stateMap['ERROR_HTTP_CONTENTTYPE'];
				array_push($list, $return_img);
				continue;
			}
			
			// 打开输出缓冲区并获取远程图片
			ob_start();
			$context = stream_context_create(
				array(
					'http' => array(
							'follow_location' => false // don't follow redirects
						)
				)
			);
			
			// 请确保php.ini中的fopen wrappers已经激活
			readfile($imgUrl, false, $context);
			$img = ob_get_contents();
			ob_end_clean();
			
			// 大小验证
			$uriSize = strlen($img); // 得到图片大小
			$allowSize = 1024 * $config['maxSize'];
			if($uriSize > $allowSize){
				$return_img['state'] = $this->stateMap['ERROR_SIZE_EXCEED'];
				array_push($list, $return_img);
				continue;
			}
			
			// 创建保存位置
			$savePath = $config['savePath'];
			if(!file_exists($savePath)){
				mkdir("$savePath", 0777);
			}
			$newName = round(microtime(true) * 1000);
			//$file = uniqid() . strrchr($imgUrl, '.');
			$file = $newName . strrchr($imgUrl, '.');
			
			// 写入文件
			$tmpName = $savePath . $file ;
			$fileShow = '/uploads/' . $mySavePath . '/' . $date . '/' . $file;  // 返回的图片地址，不要 '.'
			$file = './uploads/' . $mySavePath . '/' . $date . '/' . $file;
			if(strpos($file, 'https') === 0 || strpos($file, 'http') === 0){
				
			}else{ // local
				//$host=(is_ssl() ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
				//$file = $host.$file;
				$file = $fileShow;
			}
			
			if(file_write($tmpName, $img)){
				$return_img['state'] = 'SUCCESS';
				$return_img['url'] = $file;
				array_push($list, $return_img);
			}else{
				$return_img['state'] = $this->stateMap['ERROR_WRITE_CONTENT'];
				array_push($list, $return_img);
			}
		}
		
		return json_encode(array(
					'state' => count($list) ? 'SUCCESS' : 'ERROR',
					'list' 	=> $list
		));
	}
	
}
