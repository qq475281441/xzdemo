<?php

namespace app\index\controller;

use app\common\model\User;
use app\common\model\Userhis;
use app\common\model\Vote;
use think\Cache;
use think\Exception;
use think\Request;
use think\Session;
use app\common\model\Seat;
use app\common\model\Users;

class Index extends IndexBaseController {
	protected $redis;
	protected $user;
	
	public function __construct (Request $request = NULL)
	{
		parent::__construct ($request);
		try {
			$this->redis = new \Redis();
			$this->redis->connect ('127.0.0.1', 6379);
		} catch (Exception $e) {
			return ResponseReturn (1, '系统异常');
		}
		
		//测试中用户
		if ( !Session::has ('user') ) {
			$this->user = Users::create ([
				'username' => "观众" . rand (1000, 9999),
				'login_ip' => $request->ip (0, TRUE),
				'create_time' => time (),
				'login_time' => time ()
			]);
			Session::set ('user', $this->user);
		} else {
			$this->user = Session::get ('user');
		}
	}
	
	protected function _initialize ()
	{
		parent::_initialize ();
	}
	
	public function index ()
	{
		
		//生成作为数据
		$row = 1;
		$area = [
			'A',
			'B',
			'C',
			'D'
		];
		$seat_list = [];
		$count = 0;
		$sqlarr = [];//批量插入数据库
		for ( $i = 50; $i <= 100; $i = $i + 2 ) {
			$item = [];
			for ( $j = 1; $j <= $i; $j++ ) {
				
				foreach ( $area as $v ) {
					$sqlarr[] = $item[ $v ][ $row ][ 'item' ][] = [
						'seat_id' => $v . sprintf ("%'.02d", $row) . sprintf ("%'.02d", $j),
						'row' => $row,
						'col' => $j,
						'area' => $v
					];
					$count++;
				}
			}
			$seat_list[ 'A' ][ $row ] = $item[ 'A' ][ $row ][ 'item' ];
			$seat_list[ 'B' ][ $row ] = $item[ 'B' ][ $row ][ 'item' ];
			$seat_list[ 'C' ][ $row ] = $item[ 'C' ][ $row ][ 'item' ];
			$seat_list[ 'D' ][ $row ] = $item[ 'D' ][ $row ][ 'item' ];
			$row++;
		}
		//				$seat=new seat();
		//				$seat->saveAll($sqlarr);
		
		
		$this->assign ('row', $row);
		$this->assign ('seat', array_reverse ($seat_list));
		$this->assign ('host', config ('gatewayHost'));
		$this->assign ('user', $this->user);
		
		return $this->fetch ();
	}
	
	/**
	 * 买票接口
	 */
	public function buy ()
	{
		config ('app_trace', FALSE);
		//redis测试
		
		$userid = input ('get.uid/d');
		$seat_id = input ('get.seat_id');
		if ( trim ($userid) == "" || trim ($seat_id) == "" ) {
			return ResponseReturn (1, '缺少参数');
		}
		
		for ( $i = 1; $i <= 2000; $i++ ) {
			$p = [
				'userid' => $this->user->id,
				'seat' => [
					rand (1000, 7800),
					rand (1000, 7800),
					rand (1000, 7800),
					rand (1000, 7800)
				]
			];
			$r = $this->redis->lPush ('task', json_encode ($p));
		}
		if ( $r != FALSE ) {
			return ResponseReturn (0, '请稍候，已加入购买队列');
		}
	}
	
}