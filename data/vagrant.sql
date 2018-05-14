/*
Navicat MySQL Data Transfer

Source Server         : 本地数据库
Source Server Version : 50553
Source Host           : localhost:3306
Source Database       : vagrant

Target Server Type    : MYSQL
Target Server Version : 50553
File Encoding         : 65001

Date: 2018-05-11 18:05:28
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for vt_access
-- ----------------------------
DROP TABLE IF EXISTS `vt_access`;
CREATE TABLE `vt_access` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(50) NOT NULL DEFAULT '' COMMENT '权限名称',
  `urls` varchar(1000) NOT NULL DEFAULT '' COMMENT 'json 数组',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1：有效 0：无效',
  `updated_time` int(11) NOT NULL DEFAULT '0' COMMENT '最后一次更新时间',
  `created_time` int(11) NOT NULL DEFAULT '0' COMMENT '插入时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='权限详情表';

-- ----------------------------
-- Records of vt_access
-- ----------------------------

-- ----------------------------
-- Table structure for vt_app_access_log
-- ----------------------------
DROP TABLE IF EXISTS `vt_app_access_log`;
CREATE TABLE `vt_app_access_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` bigint(20) NOT NULL DEFAULT '0' COMMENT '品牌UID',
  `target_url` varchar(255) NOT NULL DEFAULT '' COMMENT '访问的url',
  `query_params` longtext NOT NULL COMMENT 'get和post参数',
  `ua` varchar(255) NOT NULL DEFAULT '' COMMENT '访问ua',
  `ip` varchar(32) NOT NULL DEFAULT '' COMMENT '访问ip',
  `note` varchar(1000) NOT NULL DEFAULT '' COMMENT 'json格式备注字段',
  `created_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户操作记录表';

-- ----------------------------
-- Records of vt_app_access_log
-- ----------------------------

-- ----------------------------
-- Table structure for vt_blog
-- ----------------------------
DROP TABLE IF EXISTS `vt_blog`;
CREATE TABLE `vt_blog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `text` varchar(255) DEFAULT NULL,
  `add_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of vt_blog
-- ----------------------------
INSERT INTO `vt_blog` VALUES ('5', 'thinkphp', 'thinkphp@qq.com', '1526022082');
INSERT INTO `vt_blog` VALUES ('2', 'thinkphp', 'thinkphp@qq.com', '1526018127');
INSERT INTO `vt_blog` VALUES ('3', 'thinkphp', 'thinkphp@qq.com', '1526018128');
INSERT INTO `vt_blog` VALUES ('4', 'TOPThink', 'Topthink@gmail.com', '1526021516');
INSERT INTO `vt_blog` VALUES ('6', 'thinkphp', 'thinkphp@qq.com', '1526022201');
INSERT INTO `vt_blog` VALUES ('7', 'thinkphp', 'thinkphp@qq.com', '1526022392');

-- ----------------------------
-- Table structure for vt_role
-- ----------------------------
DROP TABLE IF EXISTS `vt_role`;
CREATE TABLE `vt_role` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '角色名称',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1：有效 0：无效',
  `updated_time` int(11) NOT NULL DEFAULT '0' COMMENT '最后一次更新时间',
  `created_time` int(11) NOT NULL DEFAULT '0' COMMENT '插入时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='角色表';

-- ----------------------------
-- Records of vt_role
-- ----------------------------

-- ----------------------------
-- Table structure for vt_role_access
-- ----------------------------
DROP TABLE IF EXISTS `vt_role_access`;
CREATE TABLE `vt_role_access` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL DEFAULT '0' COMMENT '角色id',
  `access_id` int(11) NOT NULL DEFAULT '0' COMMENT '权限id',
  `created_time` int(11) NOT NULL DEFAULT '0' COMMENT '插入时间',
  PRIMARY KEY (`id`),
  KEY `idx_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='角色权限表';

-- ----------------------------
-- Records of vt_role_access
-- ----------------------------

-- ----------------------------
-- Table structure for vt_user
-- ----------------------------
DROP TABLE IF EXISTS `vt_user`;
CREATE TABLE `vt_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL DEFAULT '' COMMENT '姓名',
  `email` varchar(30) NOT NULL DEFAULT '' COMMENT '邮箱',
  `is_admin` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否是超级管理员 1表示是 0 表示不是',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1：有效 0：无效',
  `updated_time` int(11) NOT NULL DEFAULT '0' COMMENT '最后一次更新时间',
  `created_time` int(11) NOT NULL DEFAULT '0' COMMENT '插入时间',
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='用户表';

-- ----------------------------
-- Records of vt_user
-- ----------------------------
INSERT INTO `vt_user` VALUES ('1', '超级管理员', 'apanly@163.com', '1', '1', '0', '0');

-- ----------------------------
-- Table structure for vt_user_role
-- ----------------------------
DROP TABLE IF EXISTS `vt_user_role`;
CREATE TABLE `vt_user_role` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `role_id` int(11) NOT NULL DEFAULT '0' COMMENT '角色ID',
  `created_time` int(11) NOT NULL DEFAULT '0' COMMENT '插入时间',
  PRIMARY KEY (`id`),
  KEY `idx_uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户角色表';

-- ----------------------------
-- Records of vt_user_role
-- ----------------------------
