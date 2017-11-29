# 需求
1. 用户一次性可购买1-5张票
2. 系统随机为用户分配座位


# 补充假设信息
1. 系统随机为用户分配相邻的五个座位
2. 假设b区、c区的1-9行座位视野更好实行和其他座位不一样的定价

b区1-9行 c区1-9行          299一个座位                       优先级5
d区1-9行 a区1-9行          199一个座位                       优先级4
b区10-18行 c区10-18行      159一个座位                       优先级3
d区10-18行 a区10-18行      99一个座位                         优先级2
a/b/c/d区19-26行          49一个座位                         优先级1

推荐座位时按照优先级从大到小开始推荐



## 抢购问题：
使用redis队列进行购买操作，解决抢购时的高并发和超售的问题

后台监听订单时间，超过五分钟未支付订单会自动取消

使用wss取代http接口，可以承受更高的并发


## 做了一些反爬虫的处理


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