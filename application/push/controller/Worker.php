<?php

namespace app\push\controller;

use app\push\controller\BaseServer;
use think\Cache;
use think\Exception;
use think\Log;
use Workerman\Lib\Timer;
use app\common\model\Userhis;

class Worker extends BaseServer {
	protected $config;
	
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
		$task->name = 'BackUpServices';
		// 开启多少个进程运行定时任务，注意业务是否在多进程有并发问题
		$task->count = 1;
		
		$task->onWorkerStart = function ($task) {
			$time_interval = 3;
			Timer::add ($time_interval, function () {
				$this->update_userhis ();
			});
		};
		parent::__construct ();
	}
	
	/**
	 * 后台定时更新userhis表
	 */
	protected function update_userhis ()
	{
		if ( Cache::has ('login_cache') ) {
			$userhis = new Userhis();
			$tmp = Cache::get ('login_cache');
			$i = count ($tmp);
			if ( $i > 0 ) {
				Cache::rm ('login_cache');
				try {
					$userhis->saveAll ($tmp);
					
				} catch (Exception $e) {
					echo $e->getMessage ();
					
					return Log::error ("后台定时插入userhis表报错,错误信息:" . $e->getMessage ());
				}
				
				echo "userhis表更新成功";
			}
		}
		
	}
}
