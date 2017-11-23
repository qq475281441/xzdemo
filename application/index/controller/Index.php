<?php

namespace app\index\controller;

use app\common\model\User;
use app\common\model\Userhis;
use app\common\model\Vote;
use think\Cache;
use think\Request;
use think\Session;

class Index extends IndexBaseController {
	public function __construct (Request $request = NULL)
	{
		parent::__construct ($request);
	}
	
	protected function _initialize ()
	{
		parent::_initialize ();
	}
	
	
	public function index ()
	{
		
		
		return $this->fetch ();
	}
	
	
}