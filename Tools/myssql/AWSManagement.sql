--
-- Filename			: AWSManagement.sql
-- Author			: Mike Atkinson (mike.atkinson@quitenear.me) 
-- Date created			: 1 July 2012
--
--
--	This Software is FREE and is 
--	subject to the GNU General Public License which can be found at ...
--
--	http://www.gnu.org/licenses/gpl.txt
--
--	Enjoy
-- 
--  I wrote this to achieve two aims ...
-- 
--  1, Track AWS entities that an AWS account has created
--  2. Provide a mechanism to query the relationship between E2 Instances, RDS Instances, Load Balancers and EBS Instances
-- 
-- This is VERY alpha code ! 
-- 
CREATE DATABASE /*!32312 IF NOT EXISTS*/ `AWSManagement` /*!40100 DEFAULT CHARACTER SET utf8 */;
--
--
USE `AWSManagement`;
-- 
--
-- Table structure for table `EC2 Instances`
--

DROP TABLE IF EXISTS `EC2 Instances`;
CREATE TABLE `EC2 Instances` (
  `Instance ID` varchar(20) NOT NULL DEFAULT '',
  `InstanceType` varchar(20) NOT NULL DEFAULT '',
  `AMI ID` varchar(20) NOT NULL DEFAULT '',
  `Kernel ID` varchar(20)  NULL ,
  `Architecture` varchar(40) NOT NULL DEFAULT '',
  `State` varchar(60) NOT NULL DEFAULT '',
  `Monitoring` varchar(60) NOT NULL DEFAULT '',
  `Owner ID` varchar(20) NOT NULL DEFAULT '',
  `Requester ID` varchar(20) NOT NULL DEFAULT '',
  `Launch Time` DateTime  NULL ,
  `Availability Zone` varchar(20) NOT NULL DEFAULT '',
--
--
  `PublicDNSName` varchar(60) NOT NULL DEFAULT '',
  `PublicIPAddress` varchar(15) NOT NULL DEFAULT '',
  `PrivateDNSName` varchar(60) NOT NULL DEFAULT '',
  `PrivateIPAddress` varchar(15) NOT NULL DEFAULT '',
--
--
-- Data Enrichment columns
--
  `Business Owner` varchar(60) NOT NULL DEFAULT '',
  `Support Group` enum('System-Admin','Development-Admin') DEFAULT 'System-Admin',
  `Review Date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `Tracking Ref` varchar(20) NOT NULL Default '',
  PRIMARY KEY (`Instance ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
--
-- Table structure for table `EC2 Instance Tags`
--
DROP TABLE IF EXISTS `EC2 Instance Tags`;
CREATE TABLE `EC2 Instance Tags` (
  `Instance ID` varchar(20) NOT NULL DEFAULT '',
  `Key` varchar(80) NOT NULL DEFAULT '',
  `Value` varchar(120) DEFAULT NULL,
  PRIMARY KEY (`Instance ID`,`Key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
--
--
-- Table structure for table `EBS Volumes`
--
DROP TABLE IF EXISTS `EC2 Instance Volumes`;
CREATE TABLE `EC2 Instance Volumes` (
  `Instance ID` varchar(20) NOT NULL DEFAULT '',
  `Volume ID` varchar(20) NOT NULL DEFAULT '',
  `Device Name` varchar(30) NOT NULL DEFAULT '',
  `Attach Date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `Status` varchar(20) NOT NULL DEFAULT '',
  PRIMARY KEY (`Instance ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
--
--
--
--
DROP TABLE IF EXISTS `EC2 Instance Security Groups`;
CREATE TABLE `EC2 Instance Security Groups` (
  `Instance ID` varchar(20) NOT NULL DEFAULT '',
  `Name` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`Instance ID`,`Name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
-- 
-- 
--    _____________
-- 
--    RDS Instances
--    _____________
-- 
-- 
DROP TABLE IF EXISTS `RDS Instances`;
CREATE TABLE `RDS Instances` (
  `End Point` varchar(80) NOT NULL DEFAULT '',
  `Port` int(3) DEFAULT NULL,
  `Class` varchar(120) NOT NULL DEFAULT '',
  `Engine` varchar(40) NOT NULL DEFAULT '',
  `Version` varchar(30) NOT NULL DEFAULT '',
  `Admin Name` varchar(30) DEFAULT NULL,
  `DB Name` varchar(50)  NOT NULL DEFAULT '',
  `Security Group Name` varchar(30) DEFAULT NULL,
  `Parameter Group Name` varchar(30) DEFAULT NULL,
  `Storage Size` int(3) DEFAULT NULL,
  `Status` varchar(30) NOT NULL DEFAULT '',
  `Multizone` enum('true','false'),
  `Allow Minor Upgrade` enum('true','false'),
  `Creation Date` DateTime Default NULL,
  `Last Backup Date` DateTime Default Null,
-- 
-- Data enrichment
-- 
  `Creator Name` varchar(80) NOT NULL DEFAULT '',
  `Jira Ref` varchar(20) NOT NULL Default '',
  PRIMARY KEY (`End Point`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
-- 
--
DROP TABLE IF EXISTS `RDS Security Groups`;
CREATE TABLE `RDS Security Groups` (
  `Name` varchar(40) NOT NULL DEFAULT '',
  `Description` varchar(40) NOT NULL DEFAULT '',
-- 
-- Data enrichment
-- 
  `Creator Name` varchar(80) NOT NULL DEFAULT '',
  `Jira Ref` varchar(20) NOT NULL Default '',
  PRIMARY KEY (`Name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
--
-- Glue RDS and EC2 Security Groups in this table ... 
--
DROP TABLE IF EXISTS `RDS Associated EC2 Security Groups`;
CREATE TABLE `RDS Associated EC2 Security Groups` (
  `RDS Name` varchar(40) NOT NULL DEFAULT '',
  `EC2 Name` varchar(40) NOT NULL DEFAULT '',
  `EC2 Owner ID` varchar(40) NOT NULL DEFAULT '',
  `Status` varchar(40) NOT NULL DEFAULT '',
  PRIMARY KEY (`RDS Name`,`EC2 Name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
--
--
-- Modelling is a bit tricky here, an SSL Cert can be assigned to a LB Member if a port 443 and SSL off loading is required
-- however the cert belongs to the Load Balancer 
--
DROP TABLE IF EXISTS `Load Balancers`;
CREATE TABLE `Load Balancers` (
  `Name` varchar(40) NOT NULL DEFAULT '',
  `Public End Point URL` varchar(100) NOT NULL DEFAULT '',
  `Canonical Hosted Zone Name` varchar(100) NOT NULL DEFAULT '',
  `Creation Date` DateTime  default NULL,
  `Health Check Threshold` int(3) default NULL,
  `Health Check Unhealthy Threshold` int(3) default NULL,
  `Health Check Target` char(120) default NULL,
  `Health Check Timeout` int(3) default NULL,
  `SSL Certificate` varchar(120) NOT NULL DEFAULT '',
-- 
-- Data enrichment
-- 
  `Creator Name` varchar(80) NOT NULL DEFAULT '',
  `Jira Ref` varchar(20) NOT NULL Default '',
  PRIMARY KEY (`Name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
-- 
-- 
DROP TABLE IF EXISTS `Load Balancer Members`;
CREATE TABLE `Load Balancer Members` (
  `Name` varchar(40) NOT NULL DEFAULT '',
  `EC2 Instance ID` char(40) not null default '',
  `Policy Name` varchar(40) NOT NULL DEFAULT '',
  `Cookie Expiry Period` int(3)  DEFAULT NULL,
  `Stickiness Policy` varchar(40) NOT NULL DEFAULT '',
  `Port` varchar(40) NOT NULL DEFAULT '',
  `Protocol` varchar(40) NOT NULL DEFAULT '',
  PRIMARY KEY (`Name`,`EC2 Instance ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
--
--
