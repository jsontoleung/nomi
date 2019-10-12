<?php
namespace app\common\model;
use think\Model;
use app\common\config\Upload;
use think\facade\Env;
use think\File;

/*
** 评论模型
*/

class Comment extends Model {

	public function commentInfo ($id) {

		$list = $this
			->field('com.comment_id, com.content, com.browse, com.audit, com.create_time, u.nickname, v.title')
			->alias('com')
			->leftJoin('user u', 'com.uid = u.user_id', 'left')
			->leftJoin('voice v', 'com.type_id = v.voice_id', 'left')
			->where(function ($query) use ($id) {
				$query->where(['com.pid' => 0]);
				$query->where(['com.type' => 2]);
				$query->where(['com.type_id' => $id]);
			})
			->order('create_time desc')
			->select();
		foreach ($list as $k => $v) {
			$like = model('like')->where(['commentid'=>$v['comment_id']])->count();
			$list[$k]['like'] = $like;
		}

		return $list;

	}


	/**
	 * 下级评论
	 */
	public function juniorInfo ($id) {

		$list = $this
			->field('com.comment_id, com.by_uid, com.content, com.browse, com.audit, com.create_time, u.nickname, v.title')
			->alias('com')
			->leftJoin('user u', 'com.uid = u.user_id', 'left')
			->leftJoin('voice v', 'com.type_id = v.voice_id', 'left')
			->where(function ($query) use ($id) {
				$query->where(['com.pid' => $id]);
			})
			->order('create_time desc')
			->select();
		foreach ($list as $k => $v) {
			$like = model('like')->where(['commentid'=>$v['comment_id']])->count();
			$topName = $this
				->alias('com')
				->leftJoin('user u', 'com.uid = u.user_id')
				->where('comment_id=:by_uid', ['by_uid'=>$v['by_uid']])
				->value('u.nickname');
			$list[$k]['by_name'] = $topName;
			$list[$k]['like'] = $like;
		}

		return $list;

	}


	
}
