# 需求
1. 用户一次性可购买1-5张票
2. 系统随机为用户分配座位


# 补充假设信息
1. 系统随机为用户分配相邻的五个座位
2. 假设b区、c区的1-9行座位视野更好实行和其他座位不一样的定价

> b区1-9行 c区1-9行          299一个座位                       优先级5
> d区1-9行 a区1-9行          199一个座位                       优先级4
> b区10-18行 c区10-18行      159一个座位                       优先级3
> d区10-18行 a区10-18行      99一个座位                         优先级2
> a/b/c/d区19-26行          49一个座位                         优先级1

推荐座位时按照优先级从大到小开始推荐

# 系统架构
框架：thinkphp5+workerman-gatewayworker

php/7.1.7
mysql/5.5.56
nginx/1.10.2

服务器：google/1核1G
系统：centos7.2

演示地址:
[演示地址](https://xz.zhouwenping.top)  

## 抢购问题：
使用redis队列进行购买操作，解决抢购时的高并发和超售的问题

后台监听订单时间，超过五分钟未支付订单会自动取消

使用wss取代http接口，可以承受更高的并发


## 做了一些反爬虫的处理


## 部分代码
##### redis队列处理
/application/push/controller/Worker.php
<pre>
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

</pre>
##### 座位推荐算法
/application/index/controller/Index.php
<pre>
# 得到可用的座位
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
</pre>
# 数据库
<pre>
-- MySQL dump 10.13  Distrib 5.5.56, for Linux (x86_64)
--
-- Host: localhost    Database: xz
-- ------------------------------------------------------
-- Server version	5.5.56-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `xz_orders`
--

DROP TABLE IF EXISTS `xz_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `xz_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_number` char(50) NOT NULL,
  `create_time` int(11) NOT NULL,
  `price` float NOT NULL DEFAULT '0',
  `num` int(11) NOT NULL DEFAULT '1',
  `userid` int(11) DEFAULT NULL,
  `is_clean` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `xz_orders_id_uindex` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4998 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `xz_orders_item`
--

DROP TABLE IF EXISTS `xz_orders_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `xz_orders_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `oid` int(11) DEFAULT NULL,
  `seat_id` int(11) NOT NULL,
  `create_time` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `xz_orders_item_id_uindex` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3276642 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `xz_seat`
--

DROP TABLE IF EXISTS `xz_seat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `xz_seat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seat_id` char(50) NOT NULL,
  `row` int(11) DEFAULT NULL,
  `col` int(11) DEFAULT NULL,
  `area` char(50) DEFAULT NULL,
  `used` int(11) NOT NULL DEFAULT '1' COMMENT '是否可购买',
  `price` float DEFAULT '99' COMMENT '单价',
  `sort` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `xz_seat_id_uindex` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7801 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `xz_users`
--

DROP TABLE IF EXISTS `xz_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `xz_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` char(50) NOT NULL,
  `create_time` int(11) DEFAULT NULL,
  `login_time` int(11) DEFAULT NULL,
  `login_ip` char(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `xz_users_id_uindex` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-11-29 16:17:30

</pre>


# 谢谢