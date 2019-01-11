#2017-06-20

CREATE TABLE `s_admin` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(220) NOT NULL DEFAULT '',
  `pwd` varchar(220) NOT NULL DEFAULT '',
  `groupid` tinyint(2) NOT NULL DEFAULT '0',
  `ctime` int(11) NOT NULL DEFAULT '0',
  `alias` varchar(220) NOT NULL DEFAULT '',
  `lock_limit` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否锁定',
  `is_lock` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否改密',
  `is_first` varchar(1) NOT NULL DEFAULT '0',
  `utime` int(11) NOT NULL DEFAULT '0',
  `mobile` varchar(220) DEFAULT NULL COMMENT '手机',
  `id_card` varchar(220) DEFAULT NULL COMMENT '身份证号',
  `email` varchar(220) DEFAULT NULL COMMENT '邮箱',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8

CREATE TABLE `s_admin_log` (
  `id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `admin_name` varchar(100) NOT NULL DEFAULT '',
  `query_sql` text,
  `time` datetime NOT NULL ,
  PRIMARY KEY (`id`),
  KEY `badmin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8

CREATE TABLE `s_adminlogin` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `adminId` int(11) NOT NULL DEFAULT '0',
  `loginIP` varchar(200) NOT NULL,
  `ctime` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8

CREATE TABLE `s_group` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '',
  `flag` text,
  `ctime` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC

CREATE TABLE `s_admin_group` (
  `admin_id` int(11) NOT NULL DEFAULT '0',
  `group_id` int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY `admin_group` (`admin_id`,`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

insert  into `s_admin`(`id`,`name`,`pwd`,`groupid`,`ctime`,`alias`,`lock_limit`,`is_lock`,`is_first`,`utime`) values (1,'admin','0ec2ab8034bd8c1d3ee9445631646b3d',7,0,'管理员',0,0,0,1489384400);

CREATE TABLE `p_commercial` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL DEFAULT '' COMMENT '商户名称',
  `contacts` varchar(20) DEFAULT NULL COMMENT '联系人',
  `phone` varchar(20) NOT NULL DEFAULT '' COMMENT '联系电话',
  `address` varchar(200) DEFAULT NULL COMMENT '联系地址',
  `admin_name` varchar(200) DEFAULT NULL COMMENT '商户后台账号名称',
  `need_deliver` tinyint(1) DEFAULT '2' COMMENT '1.是 2 否',
  `need_product` tinyint(1) DEFAULT '2' COMMENT '1.是 2 否',
  `ali_appid` varchar(200) DEFAULT NULL COMMENT '支付宝id',
  `ali_secret` varchar(1024) DEFAULT '' COMMENT '支付宝密钥',
  `status` tinyint(1) DEFAULT '1' COMMENT '账号状态 默认1，否则为冻结',
  `msg1` varchar(64) DEFAULT NULL,
  `msg2` varchar(64) DEFAULT NULL,
  `msg3` varchar(64) DEFAULT NULL,
  `msg4` varchar(64) DEFAULT NULL,
  `msg5` varchar(64) DEFAULT NULL,
  `pay_user_id` varchar(64) DEFAULT NULL COMMENT '收款帐号',
  `pay_cent` float(5,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

#2017-06-30
DROP TABLE IF EXISTS `p_equipment`;

CREATE TABLE `p_equipment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equipment_id` varchar(255) NOT NULL COMMENT '设备id',
  `code` varchar(255) DEFAULT NULL COMMENT '编码',
  `status` tinyint(1) NOT NULL,
  `created_time` int(11) DEFAULT '0' COMMENT '创建时间',
  `platform_id` int(11) DEFAULT '1' COMMENT '商户id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `p_product` */

DROP TABLE IF EXISTS `p_product`;

CREATE TABLE `p_product` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL DEFAULT '0' COMMENT '果园商品id',
  `inner_code` varchar(50) DEFAULT NULL,
  `product_no` varchar(50) DEFAULT NULL,
  `class_id` int(11) NOT NULL DEFAULT '0' COMMENT '类目id',
  `product_name` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT '0.00',
  `old_price` decimal(10,2) DEFAULT '0.00',
  `volume` varchar(120) DEFAULT NULL,
  `unit` varchar(40) DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `created_time` int(11) DEFAULT '0',
  `status` int(11) DEFAULT '0',
  `preservation_time` int(11) DEFAULT NULL COMMENT '单位 （天）',
  PRIMARY KEY (`id`),
  KEY `price` (`price`),
  KEY `tags` (`tags`),
  KEY `product_name` (`product_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `p_product_class` */

DROP TABLE IF EXISTS `p_product_class`;

CREATE TABLE `p_product_class` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL COMMENT '类别名称',
  `parent_id` int(11) DEFAULT '0' COMMENT '父id',
  `ctime` int(11) DEFAULT '0',
  `order` int(11) DEFAULT '0' COMMENT '排序',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

alter TABLE  p_commercial add COLUMN `pay_succ_tpl_id` varchar(200) DEFAULT NULL COMMENT '支付成功模板ID';
alter TABLE  p_commercial add COLUMN `pay_fail_tpl_id` varchar(200) DEFAULT NULL COMMENT '支付失败模板ID';
alter TABLE  p_commercial add COLUMN `refund_tpl_id` varchar(200) DEFAULT NULL COMMENT '退款模板ID';
alter TABLE  p_commercial add COLUMN `notify_tpl_id` varchar(200) DEFAULT NULL COMMENT '通知模板ID';


ALTER TABLE `p_product` ADD COLUMN `img_url` VARCHAR(255) NULL , ADD COLUMN `img_url2` VARCHAR(255) NULL AFTER `img_url`, ADD COLUMN `img_url3` VARCHAR(255) NULL AFTER `img_url2`;


alter TABLE  p_commercial add COLUMN `wechat_appid` varchar(200) DEFAULT NULL COMMENT '微信appid';
alter TABLE  p_commercial add COLUMN `wechat_secret` varchar(1024) DEFAULT NULL COMMENT '微信密钥';
alter TABLE  p_commercial add COLUMN `wechat_mchid` varchar(200) DEFAULT NULL COMMENT '微信appid';
alter TABLE  p_commercial add COLUMN `wechat_key` varchar(200) DEFAULT NULL COMMENT '微信商户key';
alter TABLE  p_commercial add COLUMN `wechat_planid` varchar(200) DEFAULT NULL COMMENT '微信免密协议的模板ID';
alter TABLE  p_commercial add COLUMN `wechat_pay_succ_tpl_id` varchar(200) DEFAULT NULL COMMENT '微信消息模板';
alter TABLE  p_commercial add COLUMN `wechat_pay_fail_tpl_id` varchar(200) DEFAULT NULL COMMENT '微信消息模板';
alter TABLE  p_commercial add COLUMN `wechat_refund_tpl_id` varchar(200) DEFAULT NULL COMMENT '微信消息模板';
alter TABLE  p_commercial add COLUMN `wechat_notify_tpl_id` varchar(200) DEFAULT NULL COMMENT '微信消息模板';


alter TABLE  p_config_device add COLUMN `use_modou` TINYINT(2) DEFAULT 0 NOT NULL COMMENT '是否使用魔豆，0：不使用  1。使用';



ALTER TABLE `p_commercial` ADD COLUMN `kf_tel` VARCHAR(20) DEFAULT ''  COMMENT '客服电话';
ALTER TABLE `p_commercial` ADD COLUMN `msg_title` VARCHAR(20) DEFAULT ''  COMMENT '消息title';
ALTER TABLE `p_commercial` ADD COLUMN `img_banner` VARCHAR(255) DEFAULT ''  COMMENT '默认banner图';
ALTER TABLE `p_commercial` ADD COLUMN `qr_logo` VARCHAR(255) DEFAULT ''  COMMENT '二维码logo';
