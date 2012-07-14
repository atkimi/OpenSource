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
  
    $ScriptName -f<Security Credentials File> 
    $ScriptName -u<AWS Account> -k<AWS Security key> >



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
function SecurityGroupAssoc($Association,$RDSSecurityGroup)
{
	global $MySqlInstance;
	if ( ! isset($Association['EC2SecurityGroupName'])) {$Association['EC2SecurityGroupName']=""; } // RDS Not in use?
	if ( ! isset($Association['EC2SecurityGroupOwnerId'])) {$Association['EC2SecurityGroupOwnerId']=""; }
	if ( ! isset($Association['Status'])) {$Association['Status']=""; }
		$QueryString = sprintf("Insert into `RDS Associated EC2 Security Groups` 
(`RDS Name`,`EC2 Name`,`EC2 Owner ID`,`Status`) values ('%s','%s','%s','%s')",
$RDSSecurityGroup,$Association['EC2SecurityGroupName'],$Association['EC2SecurityGroupOwnerId'],$Association['Status']);
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
// ___________________________________________________________
//
// Process EC2 Security Group Details for a Region and Account
// ___________________________________________________________
//
function ProcessEC2SecurityGroups($SecurityArtifact,$Region)
{
	global $MySqlInstance,$AccountNumber;
//	print_r($SecurityArtifact);
//	exit(0);
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
	return(0);

}
// _____________________________________________________
//
// Process EC2 Instance Details for a Region and Account
// _____________________________________________________
//
function ProcessEC2($Credentials,$Region)
{
	global $MySqlInstance,$AccountNumber;
	$Ec2Instance=new AmazonEC2($Credentials);
	$FullRegion="AmazonEC2::$Region";
	$RegionBits=explode(".",constant($FullRegion));
	$ShortRegion=$RegionBits[1];
	$Ec2Instance->set_region(constant($FullRegion));
	$InstanceArray=(json_decode(json_encode($Ec2Instance->describe_instances()),TRUE));
	if ( count($InstanceArray['body']['reservationSet']['item']) == 0 )
	{ printf("No EC2 Instances found for Account %s in Region %s\n", $AccountNumber,$Region);return(1);}
	if(@is_string($InstanceArray['body']['reservationSet']['item']['ownerId']))
	{
		print("Single Machine Set for Account $AccountNumber\n");
		InsertInstanceSet($InstanceArray['body']['reservationSet']['item'],$ShortRegion);
	}
	else if (@is_array($InstanceArray['body']['reservationSet']['item']['0']))
	{
		print("Multi Machine Set for Account $AccountNumber\n");
		foreach($InstanceArray['body']['reservationSet']['item'] as $InstanceObject)
		{
			InsertInstanceSet($InstanceObject,$ShortRegion);
		}
	}
	else // Code should never reach here
	{
		print_r($InstanceArray['body']['reservationSet']['item']);
	}
//
// EC2 Security Groups
//
	$SecArray=(json_decode(json_encode($Ec2Instance->describe_security_groups()),TRUE));
	foreach($SecArray['body']['securityGroupInfo']['item'] as $SecurityItem)
	{
		ProcessEC2SecurityGroups($SecurityItem,$ShortRegion);
//		$QueryString = sprintf("Insert into `EC2 Instance Security Groups`
//(`Region`,`Group ID`,`Owner ID`, `Group Name`, `Description`) values ( '%s','%s','%s','%s')",
//$ShortRegion,$SecurityItem['groupId'],$SecurityItem['ownerId'],$SecurityItem['groupName'],$SecurityItem['groupDescription']);
//		if (($QueryHandle=$MySqlInstance->query($QueryString)) === false ){printf("\nERROR: Failed to run query %s\n%s\n",
//			$QueryString,$MySqlInstance->error);continue;}
	}
}
//
//
//
function InsertRDSInstance($RDSObject)
{
	global $AccountNumber,$MySqlInstance;
	if ( ! @isset($RDSObject['DBName'])) {$RDSObject['DBName']="Unset";}
	if ( ! @isset($RDSObject['LatestRestorableTime'])) {$RDSObject['LatestRestorableTime']="Unset";}
// Need to implode Sec and Parameter groups
//	print_r($RDSObject);
	$QueryString = sprintf("Insert into `RDS Instances` 
(`End Point`,`Port`,`Class`,`Engine`,`Version`,`Admin Name`,`DB Name`,`Security Group Name`,`Parameter Group Name`,
`Storage Size`,`Status`,`Multizone`,`Allow Minor Upgrade`,`Creation Date`,`Last Backup Date`) values
('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
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
$RDSObject['LatestRestorableTime']);
	if (($QueryHandle=$MySqlInstance->query($QueryString)) === false ){printf("\nERROR: Failed to run query %s\n%s\n",
		$QueryString,$MySqlInstance->error);continue;}

}
// _____________________
//
// Process RDS Databases
// _____________________
//
function ProcessRDS($Credentials)
{
	global $AccountNumber,$MySqlInstance;
	$FullRegion="AmazonRDS::$Region";
	$Rds=new AmazonRDS($Credentials);
	$Rds->set_region(constant($FullRegion));
	$RdsInstances=(json_decode(json_encode($Rds->describe_db_instances()),TRUE));
	printf("Got %s Instances\n",count($RdsInstances['body']['DescribeDBInstancesResult']['DBInstances']) );
	if ( count($RdsInstances['body']['DescribeDBInstancesResult']['DBInstances']) == 0 )
	{ printf("No RDS Security Groups found for Account %s in Region %s\n", $AccountNumber,$Region);return(1);}
//	print_r($RdsInstances['body']['DescribeDBInstancesResult']['DBInstances']);
	if (@is_string($RdsInstances['body']['DescribeDBInstancesResult']['DBInstances']['DBInstance']['Engine']))
	{
//		print("Singleton\n");
		InsertRDSInstance($RdsInstances['body']['DescribeDBInstancesResult']['DBInstances']['DBInstance']);
		return(0);
	}
	else
	{
		foreach($RdsInstances['body']['DescribeDBInstancesResult']['DBInstances']['DBInstance'] as $RDSSet)
		{
//			print("Array\n");
			InsertRDSInstance($RDSSet);
		}
	}
	$RdsSecArray=(json_decode(json_encode($Rds->describe_db_security_groups()),TRUE));
	if ( count($RdsSecArray) == 0 ) {printf("No RDS Security Groups found for Account %s in Region %s\n", $AccountNumber,$Region);}
	foreach($RdsSecArray['body']['DescribeDBSecurityGroupsResult']['DBSecurityGroups'] as $SecurityGroupSet)
	{
		foreach( $SecurityGroupSet as  $SecurityGroupSetItem)
		{
			$QueryString = sprintf("Insert into `RDS Security Groups` 
(`Name`,`Description`,`Owner ID`) values ('%s','%s','%s')",
$SecurityGroupSetItem['DBSecurityGroupName'],$SecurityGroupSetItem['DBSecurityGroupDescription'],$SecurityGroupSetItem['OwnerId']);
			if (($QueryHandle=$MySqlInstance->query($QueryString)) === false ){printf("\nERROR: Failed to run query %s\n%s\n",
				$QueryString,$MySqlInstance->error);continue;}
			foreach($SecurityGroupSetItem as $PermissionedSet)
			{
				if ( ! @is_array($PermissionedSet['EC2SecurityGroup'])){ continue; }
				if ( @is_string($PermissionedSet['EC2SecurityGroup']['Status']))
				{
					SecurityGroupAssoc($PermissionedSet['EC2SecurityGroup'],$SecurityGroupSetItem['DBSecurityGroupName']);
					continue;
				}
				foreach($PermissionedSet['EC2SecurityGroup'] as $PermissionedItem)
				{
					SecurityGroupAssoc($PermissionedItem,$SecurityGroupSetItem['DBSecurityGroupName']);
				}
			}
		}
	}
}
//Array
//(
//    [LatestRestorableTime] => 2012-07-09T20:15:00Z
//    [ReadReplicaDBInstanceIdentifiers] => Array
//        (
//        )
//
//    [Engine] => mysql
//    [PendingModifiedValues] => Array
//        (
//        )
//
//    [BackupRetentionPeriod] => 2
//    [MultiAZ] => false
//    [LicenseModel] => general-public-license
//    [DBInstanceStatus] => available
//    [EngineVersion] => 5.1.57
//    [DBInstanceIdentifier] => analyticseuprod
//    [Endpoint] => Array
//        (
//            [Port] => 3306
//            [Address] => analyticseuprod.clxmkvcygirj.eu-west-1.rds.amazonaws.com
//        )
//
//    [DBParameterGroups] => Array
//        (
//            [DBParameterGroup] => Array
//                (
//                    [ParameterApplyStatus] => in-sync
//                    [DBParameterGroupName] => default.mysql5.1
//                )
//
//        )
//
//    [DBSecurityGroups] => Array
//        (
//            [DBSecurityGroup] => Array
//                (
//                    [Status] => active
//                    [DBSecurityGroupName] => analytics-prod-dbsg
//                )
//
//        )
//
//    [PreferredBackupWindow] => 05:00-05:30
//    [DBName] => analyticseuphive
//    [AutoMinorVersionUpgrade] => true
//    [PreferredMaintenanceWindow] => sun:02:00-sun:02:30
//    [AvailabilityZone] => eu-west-1a
//    [InstanceCreateTime] => 2012-01-26T11:45:37.002Z
//    [AllocatedStorage] => 10
//    [MasterUsername] => analytics
//    [DBInstanceClass] => db.m1.small
//)
function InsertLoadBalancer($LoadBalancerObject)     
{
	global $MySqlInstance,$SortRegion;
	$SSLCert="";
	if ( ! is_array($LoadBalancerObject)) { return(1); }
	foreach($LoadBalancerObject['ListenerDescriptions']['member'] as $Listener)
	{
		if (@is_string($Listener['Listener']['SSLCertificateId'])) { $SSLCert=$Listener['Listener']['SSLCertificateId'];}
	}
	if(@is_string($LoadBalancerObject['AvailabilityZones']['member'])) { $AvailabilityZone=$LoadBalancerObject['AvailabilityZones']['member'];}
	else { $AvailabilityZone=implode(',',$LoadBalancerObject['AvailabilityZones']['member']);}
	$QueryString = sprintf("Insert into `Load Balancers` 
(`Name`,`Canonical Hosted Zone Name`,`DNS Name`,`Creation Date`,`Health Check Interval`,`Health Check Target`,`Health Check Threshold`,
`Health Check Unhealthy Threshold`,`Health Check Timeout`,`Availability Zones`,`SSL Certificate`) 
values ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
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
		$QueryString,$MySqlInstance->error);continue;}

}
// ______________________
//
// Process Load Balancers
// ______________________
//
function ProcessLB($Credentials,$Region)
{
	global $AccountNumber,$MySqlInstance,$ShortRegion;
	$FullRegion="AmazonELB::$Region";
	$RegionBits=explode(".",constant($FullRegion));
	$ShortRegion=$RegionBits[1];
	$Elb=new AmazonELB($Credentials);
	$Elb->set_region(constant($FullRegion));
	$LBArray=(json_decode(json_encode($Elb->describe_load_balancers()),TRUE));
	if ( count($LBArray['body']['DescribeLoadBalancersResult']['LoadBalancerDescriptions']['member']) == 0 )
		{printf("No Load Balancers found for Account %s in Region %s\n", $AccountNumber,$Region);return(0);}
//	print_r($LBArray['body']['DescribeLoadBalancersResult']['LoadBalancerDescriptions']['member']);
//	print_r($LBArray['body']['DescribeLoadBalancersResult']['LoadBalancerDescriptions']['member']);exit(2);
	if(is_array($LBArray['body']['DescribeLoadBalancersResult']['LoadBalancerDescriptions']['member']['ListenerDescriptions']))
	{
		InsertLoadBalancer($LBArray['body']['DescribeLoadBalancersResult']['LoadBalancerDescriptions']['member']);
	}
	else
	{
		foreach($LBArray['body']['DescribeLoadBalancersResult']['LoadBalancerDescriptions']['member'] as $LBMember)
		{
			InsertLoadBalancer($LBMember);
		}
	}	
}
//
//
function InsertInstanceSet($InstanceObject,$Region)
{
// ['body']['reservationSet']['item']  or ['body']['reservationSet']['item'][0-999] 
	if ( @is_string($InstanceObject['instancesSet']['item']['instanceId']))
	{
		print(".");
		InsertInstance($InstanceObject,$InstanceObject['instancesSet']['item'],$Region);
		return(0);
	}
	foreach($InstanceObject['instancesSet']['item'] as $InstanceSet)
	{
		print("+");
		InsertInstance($InstanceObject,$InstanceSet,$Region);
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
	$ReviewDate=strftime("%Y-%m-%d 00:00:00",$EpochSeconds);
	if  ( @isset($InstanceDetails['keyName'])) {$KeyName=$InstanceDetails['keyName'];} else {$KeyName="Unset";}
	if  ( @isset($InstanceDetails['ipAddress'])) {$IPAddress=$InstanceDetails['ipAddress'];} else {$IPAddress="Unset";}
	if  ( @isset($InstanceDetails['privateIpAddress'])) {$PrivateIPAddress=$InstanceDetails['privateIpAddress'];} else {$PrivateIPAddress="Unset";}
	if  ( @isset($InstanceDetails['kernelId'])) {$KernelId=$InstanceDetails['kernelId'];} else {$KernelId="Unset";}
	if  ( @isset($InstanceDetails['requesterId'])) {$RequesterId=$InstanceDetails['requesterId'];} else {$RequesterId="Unset";}
	$QueryString = 
sprintf("select count(*) from `EC2 Instances` where `Instance ID`='%s' and `Region` = '%s'",$InstanceDetails['instanceId'],$Region);
	if (($QueryHandle=$MySqlInstance->query($QueryString)) === false ){printf("\nERROR: Failed to run query %s\n%s\n",
		$QueryString,$MySqlInstance->error);return(1);}
	$Count=0;
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
$CommandFlags = getopt("dr,-help");
$AccessKey="";
$Password="";
$LastItem=0;
$Debug=0;
$Item=0;
$CredArray=array();
$LogFile=sprintf("/var/log/AWSCapture/Log_%s",date("Ym"));
$FlockFile="/tmp/" . $ScriptName;
if ( ($FlockFilePointer=fopen($FlockFile, "w+")) == false)
{
	$Fatal=sprintf("\n\nFatal:  $ScriptName Cannot open/create file $FlockFile\n\n");
	RaiseAlert($Fatal);
	exit(1);
}
if ( $Debug == 0 )
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
// Instantiate a mysql connection
//
$MySqlInstance = new mysqli("localhost", "awsadmin", "awsadmin", "EnvironmentManagement");
if (mysqli_connect_errno()) {
    printf("ERROR: Failed to Connect to Database %s Details are: %s\n", 'EnvironmentManagement', mysqli_connect_error());
    exit(1);
}
//
// Delete existing table data 
//
print("Deleting Security Groups\n");
$QueryString = "delete from `EC2 Instance Security Groups`";
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
		printf("\n\nProcessing Artifacts for Account %s in Location %s\n",$AccountNumber,$ALocation);
		ProcessEC2($CredArray,$ALocation);
//		ProcessRDS($CredArray,$ALocation);
//		ProcessLB($CredArray,$ALocation);
	}
}
exit(0);
?>
