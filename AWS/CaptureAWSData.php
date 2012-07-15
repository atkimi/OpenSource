#!/usr/bin/env php
<?php
//
// Documentation for PHP SDK for Amazon Web Services is at 
// 
//http://docs.amazonwebservices.com/AWSSDKforPHP/latest/
//
//
// Written by mike.atkinson@quitenear.me
//
// 14th July 2012
//
//
// This Script attempts to model an AWS account or accounts to Mysql tables
// so that queries can be made to find the associations between artifact types
//
//
//
require_once 'sdk/sdk.class.php';
//
function Help()
{
	global $ScriptName,$Revision;
	print <<<Az
 
  $ScriptName extracts artifacts from AWS and inserts then into a mysql database.

  Usage:
  
    $ScriptName 
    $ScriptName -d   # Debug on
    $ScriptName -h   # This page 



Az;
	exit(1);
}
//
// Please not that if a key has NO value set, the tha value hash will be an array
//
function InsertTagSet($TagSet,$InstanceId,$Region)
{
	global $MySqlInstance;
	if ( @is_array($TagSet['value'])) { $TValue="Unset"; } else {$TValue=$TagSet['value'];}
	if ( ! @is_string($TagSet['key'])) {return(1);}
	$QueryString = sprintf("Insert into `EC2 Instance Tags` (`Region`,`Instance ID`,`Key`,`Value`) values
 ('%s','%s','%s','%s')",$Region,$InstanceId,$TagSet['key'],$TValue);
	if (($QueryHandle=$MySqlInstance->query($QueryString)) === false ){printf("\nERROR: Failed to run query %s\n%s\n",
		$QueryString,$MySqlInstance->error);return(1);}
//		$QueryString,$MySqlInstance->error);print("\n\n");print_r($TagSet);print("\n\n");exit(1);}
	return(0);
}
//
//
function SecurityGroupAssoc($Association,$RDSSecurityGroup,$Region)
{
	global $MySqlInstance;
	if ( ! isset($Association['EC2SecurityGroupName'])) {$Association['EC2SecurityGroupName']=""; } // RDS Not in use?
	if ( ! isset($Association['EC2SecurityGroupOwnerId'])) {$Association['EC2SecurityGroupOwnerId']=""; }
	if ( ! isset($Association['Status'])) {$Association['Status']=""; }
		$QueryString = sprintf("Insert into `RDS Associated EC2 Security Groups` 
(`Region`,`RDS Name`,`EC2 Name`,`EC2 Owner ID`,`Status`) values ('%s','%s','%s','%s','%s')",
$Region,$RDSSecurityGroup,$Association['EC2SecurityGroupName'],$Association['EC2SecurityGroupOwnerId'],$Association['Status']);
	if (($QueryHandle=$MySqlInstance->query($QueryString)) === false ){printf("\nERROR: Failed to run query %s\n%s\n",
		$QueryString,$MySqlInstance->error);return(1);}
	return(0);
}

// ________________________________________________________________________
//
// Process EC2 Security Group Details which reference other security groups 
// ________________________________________________________________________
//
function ProcessEC2SecurityGroupMembers($SecurityMemberArtifact,$FullArtifact,$Region)
{
	global $MySqlInstance,$AccountNumber;
	if ( @is_array($SecurityMemberArtifact[0]))
	{
		foreach($SecurityMemberArtifact as $AMember)
		{
			if ( count($AMember['groups']) > 0 )
			{
				if( ! @is_string($AMember['groups']['item']['groupId'])) { continue;} 
				if( ! @is_string($AMember['groups']['item']['groupName'])) { continue;} 
				if( ! @is_string($AMember['groups']['item']['userId'])) { continue;} 
				$QueryString = sprintf("Insert into `EC2 Instance Security Group Groups`
(`Region`,`Local Group ID`,`Local Owner ID`,`Local Group Name`,
`Foreign Group ID`, `Foreign Owner ID`,`Foreign Group Name`) values ( '%s','%s','%s','%s','%s','%s','%s')",
$Region,
$FullArtifact['groupId'],
$FullArtifact['ownerId'],
$FullArtifact['groupName'],
$AMember['groups']['item']['groupId'],
$AMember['groups']['item']['userId'],
$AMember['groups']['item']['groupName']);
				$MySqlInstance->query($QueryString);
//				{ printf("\nERROR: Failed to run query %s\n%s\n", there are duplicates here (AWS modelling slightly flakey)
//		$QueryString,$MySqlInstance->error);return(1);}
			}
		}
	}
}
// ________________________________________________________________________
//
// Process EC2 Security Group Details Port and IP Range protocols 
// ________________________________________________________________________
//
function ProcessEC2SecurityGroupConnections($SecurityMemberArtifact,$FullArtifact,$Region)
{
	global $MySqlInstance,$AccountNumber,$Debug;
	if ( @is_array($SecurityMemberArtifact[0]))
	{
		foreach($SecurityMemberArtifact as $AMember)
		{
			if( ! @is_string($AMember['toPort'])) { $AMember['toPort']= -1;}
			if( ! @is_string($AMember['fromPort'])) { $AMember['fromPort']= -1;}
			if( $Debug ) {print("|");}
			if(  @is_array($AMember['ipRanges']['item'][0]))
			{
				if( $Debug ) {print("o");}
				foreach($AMember['ipRanges']['item'] as $Cidr)
				{
					$QueryString = sprintf("Insert into `EC2 Instance Security Group Connections`
(`Region`,`Group ID`,`Owner ID`,`IP Protocol`,`Start Port`,`End Port`,`IP CIDR` )
values ( '%s','%s','%s','%s','%s','%s','%s')",
$Region,
$FullArtifact['groupId'],
$FullArtifact['ownerId'],
$AMember['ipProtocol'],
$AMember['fromPort'],
$AMember['toPort'],
$Cidr['cidrIp']);
					if( $MySqlInstance->query($QueryString) == false )
						{ printf("\nERROR: Failed to run query %s\n%s\n", 
						$QueryString,$MySqlInstance->error);return(1);}
						
				}
				return(0);
			}
			if( ! @is_array($AMember['ipRanges']['item'])) {continue;}
			if( $Debug ) {print("x");}
			if( ! @is_string($AMember['ipRanges']['item']['cidrIp'])) {print("*");} 
			$QueryString = sprintf("Insert into `EC2 Instance Security Group Connections`
(`Region`,`Group ID`,`Owner ID`,`IP Protocol`,`Start Port`,`End Port`,`IP CIDR` )
values ( '%s','%s','%s','%s','%s','%s','%s')",
$Region,
$FullArtifact['groupId'],
$FullArtifact['ownerId'],
$AMember['ipProtocol'],
$AMember['fromPort'],
$AMember['toPort'],
$AMember['ipRanges']['item']['cidrIp']);
			if( $MySqlInstance->query($QueryString) == false )
			{ printf("\nERROR: Failed to run query %s\n%s\n", 
		$QueryString,$MySqlInstance->error);return(1);}
		}
	}
	return(0);
}
// ___________________________________________________________
//
// Process EC2 Security Group Details for a Region and Account
// ___________________________________________________________
//
function ProcessEC2SecurityGroups($SecurityArtifact,$Region)
{
	global $MySqlInstance,$AccountNumber;
//	print_r($SecurityArtifact);exit(1);
	$QueryString = sprintf("Insert into `EC2 Instance Security Groups`
(`Region`,`Group ID`,`Owner ID`, `Group Name`, `Description`) values ( '%s','%s','%s','%s','%s')",
$Region,
$SecurityArtifact['groupId'],
$SecurityArtifact['ownerId'],
$SecurityArtifact['groupName'],
$SecurityArtifact['groupDescription']);
	if (($QueryHandle=$MySqlInstance->query($QueryString)) === false ){printf("\nERROR: Failed to run query %s\n%s\n",
		$QueryString,$MySqlInstance->error);return(1);}
	if ( @is_array($SecurityArtifact['ipPermissions']['item'])) {
		ProcessEC2SecurityGroupMembers($SecurityArtifact['ipPermissions']['item'],$SecurityArtifact,$Region); }
	if ( @is_array($SecurityArtifact['ipPermissions']['item'])) {
		ProcessEC2SecurityGroupConnections($SecurityArtifact['ipPermissions']['item'],$SecurityArtifact,$Region); }
	return(0);

}
// _____________________________________________________
//
// Process EC2 Instance Details for a Region and Account
// _____________________________________________________
//
function ProcessEC2($Credentials,$Region)
{
	global $MySqlInstance,$AccountNumber,$TotalEC2;
	$Ec2Instance=new AmazonEC2($Credentials);
	$FullRegion="AmazonEC2::$Region";
	$RegionBits=explode(".",constant($FullRegion));
	$ShortRegion=$RegionBits[1];
	$Ec2Instance->set_region(constant($FullRegion));
	$StartEC2Count=$TotalEC2;
	$InstanceArray=(json_decode(json_encode($Ec2Instance->describe_instances()),TRUE));
	if ( count($InstanceArray['body']['reservationSet']['item']) == 0 )
	{ printf("Account %s in the %s Location has %4.d EC2 Instances",$AccountNumber,$ShortRegion,0);return(1);}
	if(@is_string($InstanceArray['body']['reservationSet']['item']['ownerId']))
	{
//		printf("Account %s in the %s Location has %4.d EC2 Instance",$AccountNumber,$ShortRegion,1 );
		InsertInstanceSet($InstanceArray['body']['reservationSet']['item'],$ShortRegion);
	}
	else if (@is_array($InstanceArray['body']['reservationSet']['item']['0']))
	{
//		printf("Account %s in the %s Location has %4.d EC2 Instances",
//$AccountNumber,$ShortRegion,count($InstanceArray['body']['reservationSet']['item']));
		foreach($InstanceArray['body']['reservationSet']['item'] as $InstanceObject)
		{
			InsertInstanceSet($InstanceObject,$ShortRegion);
		}
	}
	else // Code should never reach here
	{
		print_r($InstanceArray['body']['reservationSet']['item']);
	}
	if (($TotalEC2-$StartEC2Count) == 1 ) { $plural="";} else { $plural="s";}
	printf("Account %s in the %s Location has %4.d EC2 Instance%s",$AccountNumber,$ShortRegion,($TotalEC2-$StartEC2Count),$plural);
//
// EC2 Security Groups
//
	$SecArray=(json_decode(json_encode($Ec2Instance->describe_security_groups()),TRUE));
	foreach($SecArray['body']['securityGroupInfo']['item'] as $SecurityItem)
	{
		ProcessEC2SecurityGroups($SecurityItem,$ShortRegion);
	}
}
//
//
//
function InsertRDSInstance($RDSObject,$Region)
{
	global $AccountNumber,$MySqlInstance,$Debug;
	$EpochSeconds=time();
	$EpochSeconds+=(86420*91);
	$ReviewDate=strftime("%Y-%m-%d 00:00:00",$EpochSeconds);
	if ( ! @isset($RDSObject['DBName'])) {$RDSObject['DBName']="Unset";}
	if ( ! @isset($RDSObject['LatestRestorableTime'])) {$RDSObject['LatestRestorableTime']="Unset";}
// Need to implode Sec and Parameter groups
//	print_r($RDSObject);
	$QueryString = sprintf("Insert into `RDS Instances` 
(`Region`,`End Point`,`Port`,`Class`,`Engine`,`Version`,`Admin Name`,`DB Name`,`Security Group Name`,`Parameter Group Name`,
`Storage Size`,`Status`,`Multizone`,`Allow Minor Upgrade`,`Creation Date`,`Last Backup Date`,`Review Date`) values
('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
$Region,
$RDSObject['Endpoint']['Address'],
$RDSObject['Endpoint']['Port'],
$RDSObject['DBInstanceClass'],
$RDSObject['Engine'],
$RDSObject['EngineVersion'],
$RDSObject['MasterUsername'],
$RDSObject['DBName'],
$RDSObject['DBSecurityGroups']['DBSecurityGroup']['DBSecurityGroupName'],
$RDSObject['DBParameterGroups']['DBParameterGroup']['DBParameterGroupName'],
$RDSObject['AllocatedStorage'],
$RDSObject['DBInstanceStatus'],
$RDSObject['MultiAZ'],
$RDSObject['AutoMinorVersionUpgrade'],
$RDSObject['InstanceCreateTime'],
$RDSObject['LatestRestorableTime'],
$ReviewDate);
	if (($QueryHandle=$MySqlInstance->query($QueryString)) === false ){printf("\nERROR: Failed to run query %s\n%s\n",
		$QueryString,$MySqlInstance->error);continue;}

}
// _____________________
//
// Process RDS Databases
// _____________________
//
function ProcessRDS($Credentials,$Region)
{
	global $AccountNumber,$MySqlInstance,$TotalRDS;
	$FullRegion="AmazonRDS::$Region";
	$RegionBits=explode(".",constant($FullRegion));
	$ShortRegion=$RegionBits[1];
	$Rds=new AmazonRDS($Credentials);
	$Rds->set_region(constant($FullRegion));
	$RdsInstances=(json_decode(json_encode($Rds->describe_db_instances()),TRUE));
	if ( count($RdsInstances['body']['DescribeDBInstancesResult']['DBInstances']) == 0 )
	{ printf("Account %s in the %s Location has %4.d RDS Instances",$AccountNumber,$ShortRegion,0);return(1);}
	if (@is_string($RdsInstances['body']['DescribeDBInstancesResult']['DBInstances']['DBInstance']['Engine']))
	{
		printf("Account %s in the %s Location has %4.d RDS Instance",$AccountNumber,$ShortRegion,1 );
		InsertRDSInstance($RdsInstances['body']['DescribeDBInstancesResult']['DBInstances']['DBInstance'],$ShortRegion);
		$TotalRDS++;
		return(0);
	}
	else
	{
		printf("Account %s in the %s Location has %4.d RDS Instances",
$AccountNumber,$ShortRegion,count($RdsInstances['body']['DescribeDBInstancesResult']['DBInstances']['DBInstance']));
		$TotalRDS+=count($RdsInstances['body']['DescribeDBInstancesResult']['DBInstances']['DBInstance']);
		foreach($RdsInstances['body']['DescribeDBInstancesResult']['DBInstances']['DBInstance'] as $RDSSet)
		{
			InsertRDSInstance($RDSSet,$ShortRegion);
		}
	}
	$RdsSecArray=(json_decode(json_encode($Rds->describe_db_security_groups()),TRUE));
	if ( count($RdsSecArray) == 0 ) {printf("No RDS Security Groups found for Account %s in Region %s\n", $AccountNumber,$ShortRegion);}
	foreach($RdsSecArray['body']['DescribeDBSecurityGroupsResult']['DBSecurityGroups'] as $SecurityGroupSet)
	{
		foreach( $SecurityGroupSet as  $SecurityGroupSetItem)
		{
			$QueryString = sprintf("Insert into `RDS Security Groups` 
(`Region`,`Name`,`Description`,`Owner ID`) values ('%s','%s','%s','%s')",
$ShortRegion,
$SecurityGroupSetItem['DBSecurityGroupName'],
$SecurityGroupSetItem['DBSecurityGroupDescription'],
$SecurityGroupSetItem['OwnerId']);
			if (($QueryHandle=$MySqlInstance->query($QueryString)) === false ){printf("\nERROR: Failed to run query %s\n%s\n",
				$QueryString,$MySqlInstance->error);continue;}
			foreach($SecurityGroupSetItem as $PermissionedSet)
			{
				if ( ! @is_array($PermissionedSet['EC2SecurityGroup'])){ continue; }
				if ( @is_string($PermissionedSet['EC2SecurityGroup']['Status']))
				{
					SecurityGroupAssoc($PermissionedSet['EC2SecurityGroup'],$SecurityGroupSetItem['DBSecurityGroupName'],$ShortRegion);
					continue;
				}
				foreach($PermissionedSet['EC2SecurityGroup'] as $PermissionedItem)
				{
					SecurityGroupAssoc($PermissionedItem,$SecurityGroupSetItem['DBSecurityGroupName'],$ShortRegion);
				}
			}
		}
	}
}
//
// Insert Load Balancer Members and Listeners
// 
function InsertLoadBalancerExtras($LoadBalancerObject,$Region)     
{
	global $MySqlInstance,$Debug;
//
// Members
//
	if (! @is_array($LoadBalancerObject['Instances']['member'])) {return(1);} // unused Load Balancer
	if ( @is_string($LoadBalancerObject['Instances']['member']['InstanceId'])) //singleton
	{
//		printf("Member: %s\n",$LoadBalancerMembers['Instances']['member']['InstanceId']);
		$QueryString = sprintf("Insert into `Load Balancer Members` 
(`Region`,`Name`,`EC2 Instance ID`) values ('%s','%s','%s')",
$Region,
$LoadBalancerObject['LoadBalancerName'],
$LoadBalancerObject['Instances']['member']['InstanceId']);
		if( $MySqlInstance->query($QueryString) == false )
			{ printf("\nERROR: Failed to run query %s\n%s\n",$QueryString,$MySqlInstance->error);return(1);}
		return(0);
	}
	foreach($LoadBalancerObject['Instances']['member'] as $AttachedInstance)
	{
		$QueryString = sprintf("Insert into `Load Balancer Members` 
(`Region`,`Name`,`EC2 Instance ID`) values ('%s','%s','%s')",
$Region,
$LoadBalancerObject['LoadBalancerName'],
$AttachedInstance['InstanceId']);
		if( $MySqlInstance->query($QueryString) == false )
			{ printf("\nERROR: Failed to run query %s\n%s\n",$QueryString,$MySqlInstance->error);return(1);}
	}
	return(0);
//
// To do:
//
// Policies
//
// Load Balancer App Policies
// Load Balancer LB Policies 
// Load Balancer Other Policies
//
// Load Balancer Listeners  
//
// Balancer Object follows ...
//
//Array
//(
//    [SecurityGroups] => Array
//        (
//        )
//
//    [LoadBalancerName] => awseb-NIAnalyticsEMR
//    [CreatedTime] => 2011-09-16T12:02:13.650Z
//    [HealthCheck] => Array
//        (
//            [Interval] => 300
//            [Target] => HTTP:80/_hostmanager/healthcheck
//            [HealthyThreshold] => 10
//            [Timeout] => 60
//            [UnhealthyThreshold] => 10
//        )
//
//    [ListenerDescriptions] => Array
//        (
//            [member] => Array
//                (
//                    [PolicyNames] => Array
//                        (
//                        )
//
//                    [Listener] => Array
//                        (
//                            [Protocol] => HTTP
//                            [LoadBalancerPort] => 80
//                            [InstanceProtocol] => HTTP
//                            [InstancePort] => 80
//                        )
//
//                )
//
//        )
//
//    [Instances] => Array
//        (
//            [member] => Array
//                (
//                    [InstanceId] => i-eeda2896
//                )
//
//        )
//
//    [Policies] => Array
//        (
//            [AppCookieStickinessPolicies] => Array
//                (
//                )
//
//            [OtherPolicies] => Array
//                (
//                )
//
//            [LBCookieStickinessPolicies] => Array
//                (
//                )
//
//        )
//
//    [AvailabilityZones] => Array
//        (
//            [member] => us-east-1a
//        )
//
//    [CanonicalHostedZoneName] => awseb-NIAnalyticsEMR-1176644490.us-east-1.elb.amazonaws.com
//    [CanonicalHostedZoneNameID] => Z3DZXE0Q79N41H
//    [SourceSecurityGroup] => Array
//        (
//            [OwnerAlias] => amazon-elb
//            [GroupName] => amazon-elb-sg
//        )
//
//    [DNSName] => awseb-NIAnalyticsEMR-1176644490.us-east-1.elb.amazonaws.com
//    [BackendServerDescriptions] => Array
//        (
//        )
//
//    [Subnets] => Array
//        (
//        )
//
//)






	print_r($LoadBalancerObject['Instances']['member']);
	if ( ! is_array($LoadBalancerObject)) { return(1); }
	if ( ! is_array($LoadBalancerObject['ListenerDescriptions']['member'])) { print_r($LoadBalancerObject);exit(1); }
	foreach($LoadBalancerObject['ListenerDescriptions']['member'] as $Listener)
	{
		if (@is_string($Listener['Listener']['SSLCertificateId'])) { $SSLCert=$Listener['Listener']['SSLCertificateId'];}
	}
	if(@is_string($LoadBalancerObject['AvailabilityZones']['member'])) { $AvailabilityZone=$LoadBalancerObject['AvailabilityZones']['member'];}
	else { $AvailabilityZone=implode(',',$LoadBalancerObject['AvailabilityZones']['member']);}
	if( $Debug ) {print("%");}




}
function InsertLoadBalancer($LoadBalancerObject,$Region)     
{
	global $MySqlInstance,$Debug;
	$SSLCert="";
	if ( ! is_array($LoadBalancerObject)) { return(1); }
	if ( ! is_array($LoadBalancerObject['ListenerDescriptions']['member'])) { print_r($LoadBalancerObject);exit(1); }
	foreach($LoadBalancerObject['ListenerDescriptions']['member'] as $Listener)
	{
		if (@is_string($Listener['Listener']['SSLCertificateId'])) { $SSLCert=$Listener['Listener']['SSLCertificateId'];}
	}
	if(@is_string($LoadBalancerObject['AvailabilityZones']['member'])) { $AvailabilityZone=$LoadBalancerObject['AvailabilityZones']['member'];}
	else { $AvailabilityZone=implode(',',$LoadBalancerObject['AvailabilityZones']['member']);}
	if( $Debug ) {print("%");}
	$QueryString = sprintf("Insert into `Load Balancers` 
(`Region`,`Name`,`Canonical Hosted Zone Name`,`DNS Name`,`Creation Date`,`Health Check Interval`,`Health Check Target`,
`Health Check Threshold`, `Health Check Unhealthy Threshold`,`Health Check Timeout`,`Availability Zones`,`SSL Certificate`) 
values ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
$Region,
$LoadBalancerObject['LoadBalancerName'],
$LoadBalancerObject['CanonicalHostedZoneName'],
$LoadBalancerObject['DNSName'],
$LoadBalancerObject['CreatedTime'],
$LoadBalancerObject['HealthCheck']['Interval'],
$LoadBalancerObject['HealthCheck']['Target'],
$LoadBalancerObject['HealthCheck']['HealthyThreshold'],
$LoadBalancerObject['HealthCheck']['UnhealthyThreshold'],
$LoadBalancerObject['HealthCheck']['Timeout'],
$AvailabilityZone,
$SSLCert
);
	if (($QueryHandle=$MySqlInstance->query($QueryString)) === false ){printf("\nERROR: Failed to run query %s\n%s\n",
		$QueryString,$MySqlInstance->error);return(1);;}
	return(0);
}
// ______________________
//
// Process Load Balancers
// ______________________
//
function ProcessLB($Credentials,$Region)
{
	global $AccountNumber,$MySqlInstance,$ShortRegion,$TotalLB;
	$FullRegion="AmazonELB::$Region";
	$RegionBits=explode(".",constant($FullRegion));
	$ShortRegion=$RegionBits[1];
	$Elb=new AmazonELB($Credentials);
	$Elb->set_region(constant($FullRegion));
	$LBArray=(json_decode(json_encode($Elb->describe_load_balancers()),TRUE));
	if ( @count($LBArray['body']['DescribeLoadBalancersResult']['LoadBalancerDescriptions']['member']) == 0 )
		{ printf("Account %s in the %s Location has %4.d LB  Instances",$AccountNumber,$ShortRegion,0);return(1);}
	if( @is_array($LBArray['body']['DescribeLoadBalancersResult']['LoadBalancerDescriptions']['member']['ListenerDescriptions']))
	{
		printf("Account %s in the %s Location has %4.d LB  Instances",$AccountNumber,$ShortRegion,1);
		InsertLoadBalancer($LBArray['body']['DescribeLoadBalancersResult']['LoadBalancerDescriptions']['member'],$ShortRegion);
		InsertLoadBalancerExtras($LBArray['body']['DescribeLoadBalancersResult']['LoadBalancerDescriptions']['member'],$ShortRegion);
		$TotalLB++;
	}
	else
	{
		printf("Account %s in the %s Location has %4.d LB  Instances",$AccountNumber,$ShortRegion,
count($LBArray['body']['DescribeLoadBalancersResult']['LoadBalancerDescriptions']['member']));
		$TotalLB+=count($LBArray['body']['DescribeLoadBalancersResult']['LoadBalancerDescriptions']['member']);
		foreach($LBArray['body']['DescribeLoadBalancersResult']['LoadBalancerDescriptions']['member'] as $LBMember)
		{
			InsertLoadBalancer($LBMember,$ShortRegion);
			InsertLoadBalancerExtras($LBMember,$ShortRegion);
		}
	}	
}
// Load Balancer Members + Load Balancer Listeners
//                            [Listener] => Array
//                                        (
//                                            [Protocol] => HTTPS
//                                            [LoadBalancerPort] => 443
//                                            [InstanceProtocol] => HTTP
//                                            [SSLCertificateId] => arn:aws:iam::365866581348:server-certificate/fof.staging.newsoftheworld.co.uk
//                                            [InstancePort] => 80
//                                        )
//
//                                )
//            [ListenerDescriptions] => Array
//                (
//                    [member] => Array
//                        (
//                            [0] => Array
//                                (
//                                    [PolicyNames] => Array
 ////                                       (
//        (
//            [SecurityGroups] => Array
//                (
//                )
//            [LoadBalancerName] => ilovethesun-lb
//            [CreatedTime] => 2011-05-20T10:06:47.850Z
//            [HealthCheck] => Array
//                (
//                    [Interval] => 300
//                    [Target] => HTTP:80/registration/page/pid/sundc1/
//                    [HealthyThreshold] => 10
//                    [Timeout] => 8
//                    [UnhealthyThreshold] => 8
//                )
//            [ListenerDescriptions] => Array
//                (
//                    [member] => Array
//                        (
//                            [PolicyNames] => Array
//                                (
//                                )
//
//                            [Listener] => Array
//                                (
//                                    [Protocol] => HTTP
//                                    [LoadBalancerPort] => 80
//                                    [InstanceProtocol] => HTTP
//                                    [InstancePort] => 80
//                                )
//                )
//            [Instances] => Array
//                (
//                    [member] => Array
//                        (
//                            [InstanceId] => i-9e90a6e8
//                        )
//                )
//            [Policies] => Array
//                (
//                    [AppCookieStickinessPolicies] => Array
//                        (
//                        )
//                    [OtherPolicies] => Array
//                        (
//                        )
//                    [LBCookieStickinessPolicies] => Array
//                        (
//                        )
//                )
//
//            [AvailabilityZones] => Array
//                (
//                    [member] => eu-west-1b
//                )
//
//            [CanonicalHostedZoneName] => ilovethesun-lb-592344843.eu-west-1.elb.amazonaws.com
//            [CanonicalHostedZoneNameID] => Z3NF1Z3NOM5OY2
//            [SourceSecurityGroup] => Array
//                (
//                    [OwnerAlias] => amazon-elb
//                    [GroupName] => amazon-elb-sg
//                )
//
//            [DNSName] => ilovethesun-lb-592344843.eu-west-1.elb.amazonaws.com
//            [BackendServerDescriptions] => Array
//                (
//                )
//
//            [Subnets] => Array
//                (
//                )
//
//                (
function InsertInstanceSet($InstanceObject,$Region)
{
	global $Debug,$TotalEC2;
	if ( @is_string($InstanceObject['instancesSet']['item']['instanceId']))
	{
		if( $Debug) {print(".");}
		InsertInstance($InstanceObject,$InstanceObject['instancesSet']['item'],$Region);
		$TotalEC2++;
		return(0);
	}
	foreach($InstanceObject['instancesSet']['item'] as $InstanceSet)
	{
		if( $Debug) {print("+");}
		InsertInstance($InstanceObject,$InstanceSet,$Region);
		$TotalEC2++;
	}
	return(0);
}
//
//
function InsertInstance($InstanceObject,$InstanceDetails,$Region)
{
	global $MySqlInstance;
	$EpochSeconds=time();
	$EpochSeconds+=(86420*91);
	if( ! is_array($InstanceObject['instancesSet']['item']))
{	print_r($InstanceDetails);exit(0);}
	if( ! is_string($InstanceDetails['instanceId']))
{	print_r($InstanceDetails);exit(0);}
	$ReviewDate=strftime("%Y-%m-%d 00:00:00",$EpochSeconds);
	if  ( @isset($InstanceDetails['keyName'])) {$KeyName=$InstanceDetails['keyName'];} else {$KeyName="Unset";}
	if  ( @isset($InstanceDetails['ipAddress'])) {$IPAddress=$InstanceDetails['ipAddress'];} else {$IPAddress="Unset";}
	if  ( @isset($InstanceDetails['privateIpAddress'])) {$PrivateIPAddress=$InstanceDetails['privateIpAddress'];} else {$PrivateIPAddress="Unset";}
	if  ( @isset($InstanceDetails['kernelId'])) {$KernelId=$InstanceDetails['kernelId'];} else {$KernelId="Unset";}
	if  ( @isset($InstanceDetails['requesterId'])) {$RequesterId=$InstanceDetails['requesterId'];} else {$RequesterId="Unset";}
//	printf("Id is %s\n",$InstanceDetails['instanceId']);
	$QueryString = 
sprintf("select count(*) from `EC2 Instances` where `Instance ID`='%s' and `Region` = '%s'",$InstanceDetails['instanceId'],$Region);
	if (($QueryHandle=$MySqlInstance->query($QueryString)) === false ){printf("\nERROR: Failed to run query %s\n%s\n",
		$QueryString,$MySqlInstance->error);return(1);}
	$Count=0;
// MYSQL_ASSOC, MYSQL_NUM, and MYSQL_BOTH
	while ($QueryResult[$Count++]= $QueryHandle->fetch_array(MYSQLI_NUM)) {;}
	$QueryHandle->free();
	if ($QueryResult[0][0] == "1" ) 
	{
		$QueryString = sprintf("Update `EC2 Instances` set
`Instance Type`='%s',
`AMI ID`='%s',
`Kernel ID`='%s',
`Architecture`='%s',
`State`='%s',
`Monitoring`='%s',
`Owner ID`='%s',
`Requester ID`='%s',
`Launch Time`='%s',
`Availability Zone`='%s',
`Key Name`='%s',
`Public DNS Name`='%s',
`Public IPAddress`='%s',
`Private DNS Name`='%s',
`Private IPAddress`='%s',
`Review Date`='%s' where `Region`='%s' and `Instance ID` = '%s'",
$InstanceDetails['instanceType'],
$InstanceDetails['imageId'],
$KernelId,
$InstanceDetails['architecture'],
$InstanceDetails['instanceState']['name'],
$InstanceDetails['monitoring']['state'],
$InstanceObject['ownerId'],
$RequesterId,
$InstanceDetails['launchTime'],
$InstanceDetails['placement']['availabilityZone'],
$KeyName,
$InstanceDetails['dnsName'],
$IPAddress,
$InstanceDetails['privateDnsName'],
$PrivateIPAddress,
$ReviewDate,
$InstanceDetails['instanceId'],
$Region);

	} else {
		$QueryString = sprintf("Insert into `EC2 Instances` (
`Region`,
`Instance ID`,
`Instance Type`,
`AMI ID`,
`Kernel ID`,
`Architecture`,
`State`,
`Monitoring`,
`Owner ID`,
`Requester ID`,
`Launch Time`,
`Availability Zone`,
`Key Name`,
`Public DNS Name`,
`Public IPAddress`,
`Private DNS Name`,
`Private IPAddress`,
`Review Date`) values ( '%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
$Region,
$InstanceDetails['instanceId'],
$InstanceDetails['instanceType'],
$InstanceDetails['imageId'],
$KernelId,
$InstanceDetails['architecture'],
$InstanceDetails['instanceState']['name'],
$InstanceDetails['monitoring']['state'],
$InstanceObject['ownerId'],
$RequesterId,
$InstanceDetails['launchTime'],
$InstanceDetails['placement']['availabilityZone'],
$KeyName,
$InstanceDetails['dnsName'],
$IPAddress,
$InstanceDetails['privateDnsName'],
$PrivateIPAddress,
$ReviewDate
);
	}
	if (($QueryHandle=$MySqlInstance->query($QueryString)) === false ){printf("\nERROR: Failed to run query %s\n%s\n",
			$QueryString,$MySqlInstance->error);return(1);}
	if ( ! @is_array($InstanceDetails['tagSet'])) {return(1);}
	foreach($InstanceDetails['tagSet'] as $TagSet)
	{
		if ( @is_string($Tagset['key']))
		{
			InsertTagSet($Tagset,$InstanceDetails['instanceId'],$Region);
		}
		else
		{
			foreach($InstanceDetails['tagSet'] as $TagItem)
			{
				if ( @is_array($TagItem[0]))
				{
					foreach ( $TagItem as $SingleTagItem)
					{
						InsertTagSet($SingleTagItem,$InstanceDetails['instanceId'],$Region);
					}
				}
				else
				{
					InsertTagSet($TagItem,$InstanceDetails['instanceId'],$Region);
				}
			
			}
		}
	}
	return(0);
}
//
// _____________________
//
// E N T R Y   P O I N T
// _____________________
//
//
date_default_timezone_set ( "Europe/London" );
$ScriptName=basename($argv[0]);
$RevisionArray=explode(' ',"\$Revision$");
$Revision="V1.1";
$CommandFlags = getopt("dh");
$AccessKey="";
$TotalLB=0;
$TotalRDS=0;
$TotalEC2=0;
$Password="";
$LastItem=0;
$Debug=false;
$Item=0;
if ( isset($CommandFlags['h'])) { Help(); }
if ( isset($CommandFlags['d'])) { $Debug=true; }
$CredArray=array();
$LogFile=sprintf("/var/log/AWSCapture/Log_%s",date("Ym"));
$FlockFile="/tmp/" . $ScriptName;
if ( ($FlockFilePointer=fopen($FlockFile, "w+")) == false)
{
	$Fatal=sprintf("\n\nFatal:  $ScriptName Cannot open/create file $FlockFile\n\n");
	RaiseAlert($Fatal);
	exit(1);
}
if ( $Debug == false )
{
	if(($LogFilePointer=@fopen($LogFile,"a+")) == false)
	{
		printf("Warning: Cannot open file %s for Logging. Progress will be written to Standard Error\n",$LogFile);
		$LogFilePointer=fopen("php://stderr","w");
	}
	else
	{
		fprintf($LogFilePointer,"__________________________________________________________________\n\n
%s started on %s\n\n",$ScriptName,$Date);
	}
}
else
{
	$LogFilePointer=fopen("php://stderr","w");
}
if ( flock($FlockFilePointer, LOCK_EX | LOCK_NB ) == false)
{
	printf("\n\nError:  $ScriptName: Another Instance of $ScriptName is running, please try later\n\n");
	exit(1);
} 
// ______________________________________________________
//
// Get Credential Files, populating array $CredentialFile
// ______________________________________________________
//
$CredentialDirectory=sprintf("%s/.aws",getenv('HOME'));
$CredentialFile=array();
if (($awsDirHandle = opendir($CredentialDirectory)) == FALSE )
{
	$Fatal=sprintf("\nFatal: $ScriptName: Failed to open Directory %s\n\n",$CredentialDirectory);
	RaiseAlert($Fatal);
	exit(1);
}
while ( $CredFile =readdir($awsDirHandle))
{
	if (( $CredFile == ".") || ( $CredFile == ".."))  {continue;}
	array_push($CredentialFile,$CredentialDirectory . "/" .$CredFile);
}
closedir($awsDirHandle);
//
// Current Regions - may extend and may differ for each connection class
//
// REGION_US_E1, REGION_US_W1, REGION_US_W2, REGION_EU_W1, REGION_APAC_SE1, REGION_APAC_NE1, REGION_US_GOV1, REGION_SA_E1
//
//
//
// Current Regions - may extend and may differ for each connection class
//
// REGION_US_E1, REGION_US_W1, REGION_US_W2, REGION_EU_W1, REGION_APAC_SE1, REGION_APAC_NE1, REGION_US_GOV1, REGION_SA_E1
//
//
// This script will deal with AWS Customers who have multiple account in multiple regions
// You will need to create a .aws subdirectory in the Home Directory of the Invoker account
// And create files with the same name as the Account number
//
// The following Array then glues such  accounts (filename) to any Regions your accounts operate in
//
// The following are EXAMPLES ONLY !
//
$Location['123456789012'][0]= "REGION_EU_W1"; 
$Location['123456789012'][1]= "REGION_US_E1";
$Location['987654321098'][0]= "REGION_EU_W1";
$Location['987654321098'][1]= "REGION_US_E1";
//
//
//
// Instantiate a mysql connection
//
$MySqlInstance = new mysqli("localhost", "awsadmin", "awsadmin", "AWSCache");
if (mysqli_connect_errno()) {
    printf("ERROR: Failed to Connect to Database %s Details are: %s\n", 'AWSCache', mysqli_connect_error());
    exit(1);
}
//
// Delete existing table data 
//
print("Deleting Security Groups\n");
$QueryString = "delete from `EC2 Instance Security Groups`";
if (($QueryHandle=$MySqlInstance->query($QueryString)) === false ){printf("\nERROR: Failed to run query %s\n%s\n",
	$QueryString,$MySqlInstance->error);}
print("Deleting Security Group Range\n");
$QueryString = "delete from `EC2 Instance Security Group Range`";
if (($QueryHandle=$MySqlInstance->query($QueryString)) === false ){printf("\nERROR: Failed to run query %s\n%s\n",
	$QueryString,$MySqlInstance->error);}
print("Deleting Security Group Connections\n");
$QueryString = "delete from `EC2 Instance Security Group Connections`";
if (($QueryHandle=$MySqlInstance->query($QueryString)) === false ){printf("\nERROR: Failed to run query %s\n%s\n",
	$QueryString,$MySqlInstance->error);}
print("Deleting EC2 Instance Tags\n");
$QueryString = "delete from `EC2 Instance Tags`";
if (($QueryHandle=$MySqlInstance->query($QueryString)) === false ){printf("\nERROR: Failed to run query %s\n%s\n",
	$QueryString,$MySqlInstance->error);}
print("Deleting RDS Instances\n");
$QueryString = "delete from `RDS Instances`";
if (($QueryHandle=$MySqlInstance->query($QueryString)) === false ){printf("\nERROR: Failed to run query %s\n%s\n",
	$QueryString,$MySqlInstance->error);}
print("Deleting RDS Security Groups\n");
$QueryString ="delete from `RDS Security Groups`";
if (($QueryHandle=$MySqlInstance->query($QueryString)) === false ){printf("\nERROR: Failed to run query %s\n%s\n",
	$QueryString,$MySqlInstance->error);}
print("Deleting RDS Associated EC2 Security Groups\n");
$QueryString = "delete from `RDS Associated EC2 Security Groups`";
if (($QueryHandle=$MySqlInstance->query($QueryString)) === false ){printf("\nERROR: Failed to run query %s\n%s\n",
	$QueryString,$MySqlInstance->error);}
print("Deleting Load Balancers\n");
$QueryString = "delete from `Load Balancers`";
if (($QueryHandle=$MySqlInstance->query($QueryString)) === false ){printf("\nERROR: Failed to run query %s\n%s\n",
	$QueryString,$MySqlInstance->error);}
print("Deleting Load Balancer Members\n");
$QueryString = "delete from `Load Balancer Members`";
if (($QueryHandle=$MySqlInstance->query($QueryString)) === false ){printf("\nERROR: Failed to run query %s\n%s\n",
	$QueryString,$MySqlInstance->error);}
// ______________________________________________
//
// Main Loop for each account in each region ... 
// ______________________________________________
//
foreach($CredentialFile as $Credentials)
{
	$AccountNumber=basename($Credentials);
	if(!file_exists($Credentials))
	{
    		printf("ERROR: Failed to locate credential file %s\n",$Credentials );
   		 exit(1);
	}
//
// Extract the Credentials from the $Credentials file
//
	$Contents=explode("\n",file_get_contents($Credentials));
	foreach($Contents as $Line)
	{
		list($Lkey, $Lvalue) = explode("=","$Line=");
		if ( $Lkey == "AWSAccessKeyId") {$AccessKey=$Lvalue; }
		if ( $Lkey == "AWSSecretKey")   {$Password=$Lvalue; }
		$CredArray=array("key"=>$AccessKey,"secret"=>$Password);
	}
//
// Look for a match in the $Location Array
//
	if (! @is_array($Location[$AccountNumber])) {continue;}
	foreach($Location[$AccountNumber] as $ALocation)
	{
//		print_r($CredArray);
//		printf("Region %s\n",$ALocation);
		printf("\n\nProcessing Artifacts for Account %s in Location %s\n\n",$AccountNumber,$ALocation);
		print("   EC2 Artifacts ");
		ProcessEC2($CredArray,$ALocation);
		print("\n   RDS Artifacts ");
		ProcessRDS($CredArray,$ALocation);
		print("\n   LB  Artifacts ");
		ProcessLB($CredArray,$ALocation);
	}
}
printf("\n\n   Total EC2 Instances: %4.d\n   Total RDS Instances: %4.d\n   Total LB  Instances: %4.d\n\n",$TotalEC2,$TotalRDS,$TotalLB);
exit(0);
?>
