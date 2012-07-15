-- 
-- 
--  Caches AWS Artifacts in a local mysql database
-- 
-- 
CREATE DATABASE /*!32312 IF NOT EXISTS*/ `AWSCache` /*!40100 DEFAULT CHARACTER SET utf8 */;
--
--
USE `AWSCache`;
-- 
-- ___________________________________________
-- 
-- TABLES for AWS (Amazon Web Services) Estate 
-- ___________________________________________
--
--
-- Table structure for table `EC2 Instances`
--

DROP TABLE IF EXISTS `EC2 Instances`;
CREATE TABLE `EC2 Instances` (
  `Region` varchar(40) NOT NULL default 'Not Set',
  `Instance ID` varchar(20) NOT NULL DEFAULT '',
  `Instance Type` varchar(20) NOT NULL DEFAULT '',
  `AMI ID` varchar(20) NOT NULL DEFAULT '',
  `Kernel ID` varchar(20)  NULL ,
  `Architecture` varchar(40) NOT NULL DEFAULT '',
  `State` varchar(60) NOT NULL DEFAULT '',
  `Monitoring` varchar(60) NOT NULL DEFAULT '',
  `Owner ID` varchar(20) NOT NULL DEFAULT '',
  `Requester ID` varchar(20) NOT NULL DEFAULT '',
  `Launch Time` DateTime  NULL ,
  `Availability Zone` varchar(20) NOT NULL DEFAULT '',
  `Key Name` varchar(30) NOT NULL DEFAULT '',
--
--
  `Public DNS Name` varchar(60) NOT NULL DEFAULT '',
  `Public IPAddress` varchar(15) NOT NULL DEFAULT '',
  `Private DNS Name` varchar(60) NOT NULL DEFAULT '',
  `Private IPAddress` varchar(15) NOT NULL DEFAULT '',
--
--
-- Data Enrichment
--
  `Creator Name` varchar(80) NOT NULL DEFAULT '',
  `Business Owner` varchar(60) NOT NULL DEFAULT '',
  `Cost Centre` varchar(40) NOT NULL DEFAULT '',
  `Support Group` enum('DigiOps','SysEng') DEFAULT 'SysEng',
  `Review Date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `Jira Ref` varchar(20) NOT NULL Default '',
  `Notes` text,
  PRIMARY KEY (`Region`,`Instance ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `EC2 Instance Tags`
--

DROP TABLE IF EXISTS `EC2 Instance Tags`;
CREATE TABLE `EC2 Instance Tags` (
  `Region` varchar(40) NOT NULL default 'Not Set',
  `Instance ID` varchar(20) NOT NULL DEFAULT '',
  `Key` varchar(80) NOT NULL DEFAULT '',
  `Value` varchar(160) DEFAULT NULL,
  PRIMARY KEY (`Region`,`Instance ID`,`Key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
-- 
--
-- Table structure for table `EBS Volumes`
--
DROP TABLE IF EXISTS `EC2 Instance Volumes`;
DROP TABLE IF EXISTS `EBS Volumes`;
CREATE TABLE `EBS Volumes` (
  `Region` varchar(40) NOT NULL default 'Not Set',
  `Instance ID` varchar(20) NOT NULL DEFAULT '',
  `Volume ID` varchar(20) NOT NULL DEFAULT '',
  `Device Name` varchar(30) NOT NULL DEFAULT '',
  `Attach Date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `Status` varchar(20) NOT NULL DEFAULT '',
  PRIMARY KEY (`Region`,`Instance ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
--
-- Table structure for table `ECS Security Groups - add table for connections
--
DROP TABLE IF EXISTS `EC2 Instance Security Groups`;
CREATE TABLE `EC2 Instance Security Groups` (
  `Region` varchar(40) NOT NULL default 'Not Set',
  `Group ID` varchar(20) NOT NULL DEFAULT '',
  `Owner ID` varchar(20) NOT NULL DEFAULT '',
  `Group Name` varchar(80) NOT NULL DEFAULT '',
  `Description` varchar(80) DEFAULT NULL,
  PRIMARY KEY (`Region`,`Group ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
--
-- Table structure for table `ECS Security Group Connections
--
DROP TABLE IF EXISTS `EC2 Instance Security Group Connections`;
CREATE TABLE `EC2 Instance Security Group Connections` (
  `Region` varchar(40) NOT NULL default 'Not Set',
  `Group ID` varchar(20) NOT NULL DEFAULT '',
  `Owner ID` varchar(20) NOT NULL DEFAULT '',
  `IP Protocol` varchar(20) NOT NULL Default '', 
  `Start Port`  int(3),
  `End Port`  int(3),
  `IP CIDR` varchar(20)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
-- PRIMARY KEY (`Region`,`Group ID`,`IP Protocol`,`Start Port`)
--
--  Groups details in `Ref Group ID` can lookup other details for that group referred to in `EC2 Instance Security Groups` 
--  cyclic check?
-- 
DROP TABLE IF EXISTS `EC2 Instance Security Group Groups`;
CREATE TABLE `EC2 Instance Security Group Groups` (
  `Region` varchar(40) NOT NULL default 'Not Set',
  `Local Group ID` varchar(50) NOT NULL DEFAULT '',
  `Local Owner ID` varchar(20) NOT NULL DEFAULT '',
  `Local Group Name`  varchar(50),
  `Foreign Group ID` varchar(50) NOT NULL DEFAULT '',
  `Foreign Owner ID` varchar(20) NOT NULL DEFAULT '',
  `Foreign Group Name` varchar(50),
  PRIMARY KEY (`Region`,`Local Group ID`,`Local Group Name`,`Foreign Group ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
--
-- Ranges (obsolete) 
-- 
DROP TABLE IF EXISTS `EC2 Instance Security Group Range`;
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
  `Region` varchar(40) NOT NULL default 'Not Set',
  `End Point` varchar(80) NOT NULL DEFAULT 'Not Set',
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
-- Data Enrichment
--
  `Creator Name` varchar(80) NOT NULL DEFAULT '',
  `Business Owner` varchar(60) NOT NULL DEFAULT '',
  `Cost Centre` varchar(40) NOT NULL DEFAULT '',
  `Support Group` enum('DigiOps','SysEng') DEFAULT 'SysEng',
  `Review Date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `Jira Ref` varchar(20) NOT NULL Default '',
  `Notes` text,
  PRIMARY KEY (`Region`,`End Point`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
-- 
--
DROP TABLE IF EXISTS `RDS Security Groups`;
CREATE TABLE `RDS Security Groups` (
  `Region` varchar(40) NOT NULL default 'Not Set',
  `Name` varchar(40) NOT NULL DEFAULT 'Not Set',
  `Description` varchar(40) NOT NULL DEFAULT '',
  `Owner ID` varchar(20) NOT NULL DEFAULT '',
  PRIMARY KEY (`Region`,`Name`,`Owner ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
--
-- Glue RDS and EC2 Security Groups in this table ... 
--
DROP TABLE IF EXISTS `RDS Associated EC2 Security Groups`;
CREATE TABLE `RDS Associated EC2 Security Groups` (
  `Region` varchar(40) NOT NULL default 'Not Set',
  `RDS Name` varchar(40) NOT NULL DEFAULT 'Not Set',
  `EC2 Name` varchar(40) NOT NULL DEFAULT '',
  `EC2 Owner ID` varchar(40) NOT NULL DEFAULT '',
  `Status` varchar(40) NOT NULL DEFAULT '',
  PRIMARY KEY (`Region`,`RDS Name`,`EC2 Name`,`EC2 Owner ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
--
--   PRIMARY KEY (`Region`,`RDS Name`,`EC2 Name`)
--
-- Modelling is a bit tricky here, an SSL Cert can be assigned to a LB Member if a port 443 and SSL off loading is required
-- however the cert belongs to the Load Balancer 
--
DROP TABLE IF EXISTS `Load Balancers`;
CREATE TABLE `Load Balancers` (
  `Region` varchar(40) NOT NULL default 'Not Set',
  `Name` varchar(40) NOT NULL DEFAULT 'Not Set',
--   `Public End Point URL` varchar(100) NOT NULL DEFAULT '',
  `Canonical Hosted Zone Name` varchar(100) NOT NULL DEFAULT '',
  `DNS Name` varchar(100) NOT NULL DEFAULT '',
  `Creation Date` DateTime  default NULL,
  `Health Check Interval` int(3) default NULL,
  `Health Check Target` char(120) default NULL,
  `Health Check Threshold` int(3) default NULL,
  `Health Check Unhealthy Threshold` int(3) default NULL,
  `Health Check Timeout` int(3) default NULL,
  `Availability Zones` varchar(40),
  `SSL Certificate` varchar(120) NOT NULL DEFAULT '',
-- 
-- Data enrichment
-- 
  `Creator Name` varchar(80) NOT NULL DEFAULT '',
  `Jira Ref` varchar(20) NOT NULL Default '',
  `Cost Centre Code` varchar(40) not null default 'Not Set',
  `Notes` text,
  PRIMARY KEY (`Region`,`Name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
-- 
-- Machines which are attached 
-- 
DROP TABLE IF EXISTS `Load Balancer Members`;
CREATE TABLE `Load Balancer Members` (
  `Region` varchar(40) NOT NULL default 'Not Set',
  `Name` varchar(40) NOT NULL DEFAULT '',
  `EC2 Instance ID` char(40) not null default '',
  PRIMARY KEY (`Region`,`Name`,`EC2 Instance ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
-- 
-- Load Balance Listeners 
-- 
DROP TABLE IF EXISTS `Load Balancer Listeners`;
CREATE TABLE `Load Balancer Listeners` (
  `Region` varchar(40) NOT NULL default 'Not Set',
  `Name` varchar(40) NOT NULL DEFAULT '',
  `Inbound Protocol` varchar(20),
  `Member Number` int(3),
  `Policy Names` varchar(80),
  `Protocol` varchar(20),
  `Inbound Port` int(3),
  `Outbound Protocol` varchar(20),
  `Outbound Port` int(3),
  `SSL Certificate` varchar(120),
  PRIMARY KEY (`Region`,`Name`,`Inbound Protocol`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 
-- Load Balance Policies App Cookie 
-- 
DROP TABLE IF EXISTS `Load Balancer App Policies`;
CREATE TABLE `Load Balancer App Policies` (
  `Region` varchar(40) NOT NULL default 'Not Set',
  `Name` varchar(40) NOT NULL DEFAULT '',
  `Member Number` int(3),
  `Value` varchar(80),
  PRIMARY KEY (`Region`,`Name`,`Member Number`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 
-- Load Balance Policies Other 
-- 
DROP TABLE IF EXISTS `Load Balancer Other Policies`;
CREATE TABLE `Load Balancer Other Policies` (
  `Region` varchar(40) NOT NULL default 'Not Set',
  `Name` varchar(40) NOT NULL DEFAULT '',
  `Member Number` int(3),
  `Value` varchar(80),
  PRIMARY KEY (`Region`,`Name`,`Member Number`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 
-- Load Balance Policies LB 
-- 
DROP TABLE IF EXISTS `Load Balancer LB Policies`;
CREATE TABLE `Load Balancer LB Policies` (
  `Region` varchar(40) NOT NULL default 'Not Set',
  `Name` varchar(40) NOT NULL DEFAULT '',
  `Member Number` int(3),
  `Value` varchar(80),
  PRIMARY KEY (`Region`,`Name`,`Member Number`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


-- __________________________________________________________________________
--
