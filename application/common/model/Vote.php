<?php

namespace app\common\model;

use think\Model;

class Vote extends Model {
	protected $name = "vote";
	
	protected function initialize ()
	{
		parent::initialize ();
	}
	
	/**
	 * 关联vote_item表
	 * @return \think\model\relation\BelongsTo
	 */
	public function item ()
	{
		return $this->hasMany ('vote_item', 'vid', 'id');
	}
	
	/**
	 * 根据投票id获取完整投票项目
	 *
	 * @param $id
	 *
	 * @return bool|null|static
	 */
	public function vote_with_item ($id)
	{
		$data = $this::get (function ($query) use ($id) {
			$query->where ('id', $id)->where ('switch', 1);
		}, 'item');
		
		return $data ? $data : FALSE;
	}
}
