<?php

namespace app\index\controller;

use app\common\model\Orders;
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
	
	protected $recommendList;
	
	public function __construct (Request $request = NULL)
	{
		parent::__construct ($request);
		try {
			$this->redis = new \Redis();
			$this->redis->connect ('127.0.0.1', 6379);
		} catch (Exception $e) {
			return ResponseReturn (1, '系统异常');
		}
		
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
		//生成座位数据
		//		$row = 1;
		//		$area = [
		//			'A',
		//			'B',
		//			'C',
		//			'D'
		//		];
		//		$seat_list = [];
		//		$count = 0;
		//		$sqlarr = [];//批量插入数据库
		//		for ( $i = 50; $i <= 100; $i = $i + 2 ) {
		//			$item = [];
		//			for ( $j = 1; $j <= $i; $j++ ) {
		//
		//				foreach ( $area as $v ) {
		//					$sqlarr[] = $item[ $v ][ $row ][ 'item' ][] = [
		//						'seat_id' => $v . sprintf ("%'.02d", $row) . sprintf ("%'.02d", $j),
		//						'row' => $row,
		//						'col' => $j,
		//						'area' => $v
		//					];
		//					$count++;
		//				}
		//			}
		//			$seat_list[ 'A' ][ $row ] = $item[ 'A' ][ $row ][ 'item' ];
		//			$seat_list[ 'B' ][ $row ] = $item[ 'B' ][ $row ][ 'item' ];
		//			$seat_list[ 'C' ][ $row ] = $item[ 'C' ][ $row ][ 'item' ];
		//			$seat_list[ 'D' ][ $row ] = $item[ 'D' ][ $row ][ 'item' ];
		//			$row++;
		//		}
		//		print_r($seat_list);
		//				$seat=new seat();
		//				$seat->saveAll($sqlarr);
		//==============生成座位=================
		
		
		$seats = Seat::all (NULL, [], TRUE);
		$seat_array = [];
		foreach ( $seats as $seat ) {
			$seat_array[ $seat->area ][ $seat->row ] [] = [
				'seat_id' => $seat->seat_id,
				'row' => $seat->row,
				'col' => $seat->col,
				'area' => $seat->area,
				'id' => $seat->id,
				'price' => $seat->price
			];
			
		}
		$orders = Orders::all (function ($order) {
			$order->where ('userid', $this->user->id)->order ('create_time desc');
		}, 'item');
		$this->find_seats (1);
		$token = md5 (rand (10000, 99999));
		Cache::set (Session::get ('user')[ 'id' ], $token);
		$this->assign ('token', $token);
		$this->assign ('recommend', json_encode ($this->recommendList));
		$this->assign ('seat', array_reverse ($seat_array));
		$this->assign ('time', time ());
		$this->assign ('paytime', 300);
		$this->assign ('orders', $orders);
		$this->assign ('row', 27);
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
	
	
	/**
	 * 获得第一个座位
	 *
	 * @param $limit
	 *
	 * @return \___PHPSTORM_HELPERS\static|bool
	 */
	private function get_head_item ($limit)
	{
		$seat = Seat::all (function ($seat) use ($limit) {
			$seat->where ('used', 1)->order ('sort desc')->limit ($limit - 1, $limit);
		});
		
		return $seat ? $seat[ 0 ] : FALSE;
	}
	
	private function find_seats ($limit)
	{
		#得到可用的座位
		$seat = $this->get_head_item ($limit);
		$this->recommendList[] = $seat;//推荐座位
		$recommendNum = 5;//推荐个数
		for ( $i = 2; $i < $recommendNum; $i++ ) {
			$temp_w = Seat::get (function ($temp) use ($seat) {
				$temp->where ('used', 1)->where ('area', $seat->area)->where ('row', $seat->row)->where ('col', $seat->col - 1);
			});//左边元素
			if ( $temp_w ) {
				$this->recommendList[] = $temp_w;
				if ( count ($this->recommendList) >= 5 ) {
					break;
				}
			}
			$temp_e = Seat::get (function ($temp) use ($seat) {
				$temp->where ('used', 1)->where ('area', $seat->area)->where ('row', $seat->row)->where ('col', $seat->col + 1);
			});//右元素
			if ( $temp_e ) {
				$this->recommendList[] = $temp_e;
				if ( count ($this->recommendList) >= 5 ) {
					break;
				}
			}
			
			$temp_s = Seat::get (function ($temp) use ($seat) {
				$temp->where ('used', 1)->where ('area', $seat->area)->where ('row', $seat->row + 1)->where ('col', $seat->col + 1);
			});//下方元素
			if ( $temp_s ) {
				$this->recommendList[] = $temp_s;
				if ( count ($this->recommendList) >= 5 ) {
					break;
				}
			}
			$temp_n = Seat::get (function ($temp) use ($seat) {
				$temp->where ('used', 1)->where ('area', $seat->area)->where ('row', $seat->row - 1)->where ('col', $seat->col - 1);
			});//上方元素
			if ( $temp_n ) {
				$this->recommendList[] = $temp_n;
				if ( count ($this->recommendList) >= 5 ) {
					break;
				}
			}
			
			if ( count ($this->recommendList) >= 5 ) {
				break;
			} else {
				if ( isset($this->recommendList[ $i ]) ) {
					$seat = $this->recommendList[ $i ];
					continue;
				} else {//找不够座位
					//换掉第一个座位重新开始
					if ( $limit >= 5000 ) {
						die;
					}
					$this->recommendList = [];
					$this->find_seats ($limit + 1);
					break;
				}
				
			}
			
		}
		
		//		foreach ( $this->recommendList as $value ) {
		//			echo $value->seat_id . "<br>";
		//		}
	}
}