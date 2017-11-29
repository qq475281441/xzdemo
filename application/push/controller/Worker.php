<?php

namespace app\push\controller;

use app\common\model\Orders;
use app\common\model\Seat;
use \GatewayWorker\Lib\Gateway;
use think\Exception;
use think\Log;
use Workerman\Lib\Timer;

class Worker extends BaseServer {
	protected $config;
	protected $time_interval;
	protected $redis;
	
	public function __construct ()
	{
		//Gateway 进程配置，外部客户端连接进程
		$this->config[ 'gateway' ] = Config ('worker.gateway') == '' ? '' : Config ('worker.gateway');
		
		//实例化Register服务==============内部注册进程=====================
		$this->config[ 'register' ] = Config ('worker.register') == '' ? '' : Config ('worker.register');
		
		//实例化BusinessWorker进程,业务处理进程================
		$this->config[ 'business' ] = Config ('worker.business') == '' ? '' : Config ('worker.business');
		
		//实例化一个后台定时任务
		$task = new \Workerman\Worker();
		$task->name = 'RadisTaskServers';
		// 开启多少个进程运行定时任务，注意业务是否在多进程有并发问题
		$task->count = 1;
		Gateway::$registerAddress = config ('registerAddress');//注册Gateway推送消息
		$task->onWorkerStart = function ($task) {
			$this->time_interval = 0.5;//每0.5秒处理一次
			$this->redis = new \Redis();
			$this->redis->connect ('127.0.0.1', 6379);
			$this->timmer ($this->time_interval, $this->redis);
		};
		
		//实例化一个后台定时任务
		$task = new \Workerman\Worker();
		$task->name = 'ClearTaskServers';
		// 开启多少个进程运行定时任务，注意业务是否在多进程有并发问题
		$task->count = 1;
		$task->onWorkerStart = function ($task) {
			$this->time_interval = 5;//每0.5秒处理一次
			$this->clear_timmer ($this->time_interval);
		};
		parent::__construct ();
	}
	
	/**
	 * 添加定时器
	 *
	 * @param $time_interval
	 * @param $redis
	 */
	private function timmer ($time_interval, $redis)
	{
		Timer::add ($time_interval, function () use ($redis) {
			while ( 1 ) {//判断队列
				$task_return[ 'type' ] = "task_return";
				$task_return[ 'msg' ] = "";
				$task_return[ 'data' ] = [];
				$redis_task = $redis->rPop ('task');
				if ( $redis_task ) {
					#执行抢购
					#判断是否可以购买
					$data = json_decode ($redis_task);
					$seat = Seat::all ($data->seat);
					Log::info ("当前处理用户id:" . $data->userid);
					$all_price = 0;
					$useful = 1;//座位是否全部可用
					if ( $seat ) {
						foreach ( $seat as $v ) {
							if ( $v->used != 1 ) {//订票中某个座位已售出
								echo "订单失败,座位{$v->seat_id}已售出，id:{$v->id}\n";
								$task_return[ 'msg' ] = "订单失败,座位{$v->seat_id}已售出";
								$useful = 0;
								#订单失败
								Gateway::sendToUid ($data->userid, json_encode ($task_return));
								continue;
							} else {
								$all_price += $v->price;
								echo "用户id:{$data->userid}定座成功:{$v->seat_id}\n";
							}
						}
						if ( $useful ) {
							#写订单
							//TODO:写订单
							db ()->startTrans ();
							try {
								$seat_result = db ('seat')->where ('id', 'in', $data->seat)->update (['used' => 0]);
								#生成订单
								$order_number = date ('YmdHis') . mt_rand (10000, 99999);
								$orders_result = db ('orders')->insertGetId ([
									'order_number' => $order_number,
									'create_time' => time (),
									'num' => count ($data->seat),
									'price' => $all_price,
									'userid' => $data->userid
								]);
								
								
								foreach ( $data->seat as $value ) {
									$item[ 'seat_id' ] = $value;
									$item[ 'oid' ] = $orders_result;
									$item[ 'create_time' ] = time ();
									$orders_item[] = $item;
								}
								$item_result = db ('orders_item')->insertAll ($orders_item);
								
								if ( !$seat_result || !$orders_result || !$orders_result ) {
									echo "生成订单失败\n";
									$task_return[ 'msg' ] = "抢购失败";
									db ()->rollback ();
								} else {
									$task_return[ 'msg' ] = "恭喜，抢购成功，您有五分钟的支付时间";
									$task_return[ 'data' ] = Orders::get ($orders_result);
									echo "生成订单成功\n";
									db ()->commit ();
								}
								
							} catch (Exception $e) {
								db ()->rollback ();
								$task_return[ 'msg' ] = "抢购失败";
								echo "生成订单失败{$e->getMessage ()}\n";
							}
							
						} else {
							continue;
						}
						
					} else {
						$task_return[ 'msg' ] = "您提交的信息有误，座位未找到";
						echo "Seat not found \n";
					}
					
				} else {
					//队列空
					echo "Listening/0.5sec \n";
					break;
				}
				Gateway::sendToUid ($data->userid, json_encode ($task_return));
			}
		});
	}
	
	/**
	 * 定时清理过期订单
	 *
	 * @param $time_interval
	 */
	private function clear_timmer ($time_interval)
	{
		Timer::add ($time_interval, function () {
			$orders = Orders::all (function ($order) {
				$order->where ("create_time", '<=', time () - 300)->where ('is_clean', 0);//超过300s不支付并且未被清理的订单
			}, 'item');
			$itemArr = [];
			$cleanArr = [];
			if ( $orders ) {
				foreach ( $orders as $order ) {
					foreach ( $order->item as $o ) {
						$itemArr[] = $o->seat_id;
					}
					$cleanArr[] = $order->id;
				}
				
				Seat::update (['used' => 1], [
					'id' => [
						'in',
						$itemArr
					]
				
				]);
				
				Orders::update (['is_clean' => 1], [
					'id' => [
						'in',
						$cleanArr
					]
				]);
				echo "清理过期订单成功\n";
				
				#重新输出已售座位表
				$seats = Seat::all (function ($seat) {
					$seat->where ('used', 0);
				});
				
				$returnMsg[ 'type' ] = 'unuseful';
				$returnMsg[ 'data' ] = $seats;
				$returnMsg[ 'msg' ] = "已被选择的座位";
				Gateway::sendToAll (json_encode ($returnMsg));
			}
			
		});
	}
}
