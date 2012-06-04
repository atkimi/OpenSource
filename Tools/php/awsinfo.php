#!/usr/bin/env php
<?php
//
// Documentation for PHP SDK for Amazon Web Services is at 
// 
//http://docs.amazonwebservices.com/AWSSDKforPHP/latest/
//
require_once 'sdk/sdk.class.php';
//
function Help()
{
	global $ScriptName,$Revision;
	print <<<Az
 
  $ScriptName extracts data from AWS objects

  Usage:
  
    $ScriptName -f<Security Credentials File> -x<asg|asi|images|instances|ips|keypairs|loadbalancers|rds|rdsparams|rdssecgroups|s3|secgroups|tags|volumes|vpcs|vpnconnections|vpngateways>
    $ScriptName -u<AWS Account> -k<AWS Security key> -x<asg|asi|images|ips|instances|keypairs|loadbalancers|rds|rdsparams|rdssecgroups|s3|secgroups|tags|volumes|vpcs|vpnconnections|vpngateways>

    Pre-requistes:
    ______________


    The PHP sdk installed in /usr/share/lib/php 
    A symbolic link from /usr/share/lib/php/sdk to the version installed above, e.g.

    lrwxrwxrwx. 1 root root 9 Mar 20 19:26 /usr/share/php/sdk -> sdk-1.5.3

    An AWS Credential file with the format ...
    
AWSAccessKeyId=<AWS Account>
AWSSecretKey=<AWS Security key value>
  
    Or
    values passed as command line arguments 


Az;
	exit(1);
}
//
// Print concatonated key and its value 
//
function PrintData($DepthArray,$Value)
{
	global $LastItem,$Item;
	if ((count($DepthArray) > 1 )) { $LastItem;}
	$DepthString=implode(":",$DepthArray);
	$DepthStringLength=strlen($DepthString);
	if( $DepthStringLength <80 ) { printf("%-80.80s%s\n",$DepthString,$Value); }
	else if( $DepthStringLength <120 ) { printf("%-120.120s%s\n",$DepthString,$Value); }
	else { printf("%s %s\n",$DepthString,$Value); }
	return(0);
}
//
//
function ParseArray($AwsArray,$FilterArray)
{
	global $DepthArray; 
	ksort($AwsArray);
	if ( ! is_array($AwsArray))
	{
		print("No Data Found\n\n");
		return(1);
	}
	foreach($AwsArray as $Akey=>$AValue)
	{
		if ( is_array($AValue))
		{
			
			array_push($DepthArray,$Akey);
			ParseArray($AValue,$FilterArray);
			array_pop($DepthArray);
		}
		else
		{
			array_push($DepthArray,$Akey);
			PrintData($DepthArray,$AValue);
			array_pop($DepthArray);
		}
	}
}
//
// _____________________
//
// E N T R Y   P O I N T
// _____________________
//
//
$ScriptName=basename($argv[0]);
$RevisionArray=explode(' ',"\$Revision$");
$Revision="V1.1";
$CommandFlags = getopt("f:u:x:k:,-help");
$AccessKey="";
$Password="";
$LastItem=0;
$Item=0;
$DepthArray=array();
if (( ! is_array($CommandFlags)) || (empty($CommandFlags))) { Help(); }
if ( (! isset($CommandFlags['x'])) || ( empty($CommandFlags['x']))) { Help(); }
if ( (empty($CommandFlags['u']) ||  empty($CommandFlags['k'])) && empty($CommandFlags['f'])) { Help(); }
if ( (isset($CommandFlags['f'])) && (! empty($CommandFlags['f']))) 
{
	if(!file_exists($CommandFlags['f'])) { Help();}
	$Contents=explode("\n",file_get_contents($CommandFlags['f']));
	foreach($Contents as $Line)
	{
		list($Lkey, $Lvalue) = explode("=","$Line=");
		if ( $Lkey == "AWSAccessKeyId") {$AccessKey=$Lvalue; }
		if ( $Lkey == "AWSSecretKey")   {$Password=$Lvalue; }
	}
}
else
{
	$AccessKey=$CommandFlags['u'];
	$Password=$CommandFlags['k'];
}
if ((strlen($AccessKey) === 0 ) || (strlen($Password) === 0))
{
	printf("\nFailed to determine credentials for AWS access\n\n");
	exit(1); 
} 
$Credentials=array("key"=>$AccessKey,"secret"=>$Password);
switch($CommandFlags['x'])
{
	case "asg" :
		$ASInstance=new AmazonAS($Credentials);
		$ASInstance->set_region(AmazonAS::REGION_EU_W1);
		printf("\n  AUTO SCALING GROUPS\n  ______________________\n\n");
		$ASIArray=(json_decode(json_encode($ASInstance->describe_auto_scaling_groups()),TRUE));
		$FilterOut=array("DescribeAutoScalingGroupsResult","AutoScalingGroups","member","@attributes","ns","RequestId");
		ParseArray($ASIArray['body']['DescribeAutoScalingGroupsResult']['AutoScalingGroups'],$FilterOut);
		break;
	case "asi" :
		$ASInstance=new AmazonAS($Credentials);
		$ASInstance->set_region(AmazonAS::REGION_EU_W1);
		printf("\n  AUTO SCALING INSTANCES\n  ______________________\n\n");
		$ASIArray=(json_decode(json_encode($ASInstance->describe_auto_scaling_instances()),TRUE));
		$FilterOut=array("AutoScalingInstances","member","@attributes","ns","RequestId");
		ParseArray($ASIArray['body']['DescribeAutoScalingInstancesResult']['AutoScalingInstances'],$FilterOut);
		break;
//
// Need to sort out default cache malarkey stuff here
//	
	case "dyn" :
		$DynInstance=new AmazonDynamoDB($Credentials);
		print("\n________\n So far so good\n");
		$DynInstance->set_region(AmazonDynamoDB::REGION_EU_W1);
		printf("\n  DYNAMO DBS\n  __________\n\n");
		$DynArray=(json_decode(json_encode($DynInstance->list_tables()),TRUE));
		$FilterOut=array("AutoScalingInstances","member","@attributes","ns","RequestId");
		ParseArray($DynArray['body'],$FilterOut);
		break;
	case "iam" :
		$IamInstance=new AmazonIAM($Credentials);
//		$IamInstance->set_region(AmazonIAM::REGION_EU_W1);
		printf("\n  Identity AM DETAILS\n  ___________________\n\n");
		$IAMArray=(json_decode(json_encode($IamInstance->list_users()),TRUE));
		$FilterOut=array("IsTruncated","Users","member","ns","@attributes","RequestId");
		ParseArray($IAMArray['body']['ListUsersResult']['Users'],$FilterOut);
		break;
	case "images" :
		$Ec2Instance=new AmazonEC2($Credentials);
		$Ec2Instance->set_region(AmazonEc2::REGION_EU_W1);
		printf("\n  AMI DETAILS\n  ___________\n\n");
		$AMIArray=(json_decode(json_encode($Ec2Instance->describe_images()),TRUE));
		$FilterOut=array("imageSet","item","@attributes","RequestId");
		ParseArray($AMIArray['body']['imagesSet'],$FilterOut);
		break;
	case "ips" :
		$Ec2Instance=new AmazonEC2($Credentials);
		$Ec2Instance->set_region(AmazonEc2::REGION_EU_W1);
		printf("\n  FIXED (Elastic) IP's\n  ____________________\n\n");
		$ElasticIPArray=(json_decode(json_encode($Ec2Instance->describe_addresses()),TRUE));
		$FilterOut=array("addressesSet","item","@attributes","RequestId","ns");
		ParseArray($ElasticIPArray['body']['addressesSet'],$FilterOut);
		break;
	case "instances" :
		$Ec2Instance=new AmazonEC2($Credentials);
		$Ec2Instance->set_region(AmazonEc2::REGION_EU_W1);
		printf("\n  INSTANCE DETAILS\n  ________________\n\n");
		$InstanceArray=(json_decode(json_encode($Ec2Instance->describe_instances()),TRUE));
		$FilterOut=array("requestId","reservationSet","item","@attributes","RequestId","ns");
//		print_r($InstanceArray['body']);exit(1);
		ParseArray($InstanceArray['body']['reservationSet'],$FilterOut);
		break;
	case "keypairs" :
		$Ec2Instance=new AmazonEC2($Credentials);
		$Ec2Instance->set_region(AmazonEc2::REGION_EU_W1);
		printf("\n  KEY PAIRS\n  __________\n\n");
		$KeyArray=(json_decode(json_encode($Ec2Instance->describe_key_pairs()),TRUE));
		$FilterOut=array("keySet","item","@attributes","RequestId");
		ParseArray($KeyArray['body']['keySet'],$FilterOut);
		break;
	case "loadbalancers" :
		$Elb=new AmazonELB($Credentials);
		$Elb->set_region(AmazonELB::REGION_EU_W1);
		printf("\n  LOAD BALANCERS\n  ______________\n\n");
		$LBArray=(json_decode(json_encode($Elb->describe_load_balancers()),TRUE));
		$FilterOut=array("DescribeLoadBalancersResult","LoadBalancerDescriptions","member","@attributes","RequestId");
		ParseArray($LBArray['body']['DescribeLoadBalancersResult']['LoadBalancerDescriptions'],$FilterOut);
		break;
	case "rds" :
		$Rds=new AmazonRDS($Credentials);
		$Rds->set_region(AmazonRDS::REGION_EU_W1);
		printf("\n  RDS INSTANCES\n  _____________\n\n");
		$RdsArray=(json_decode(json_encode($Rds->describe_db_instances()),TRUE));
		$FilterOut=array("DescribeDBInstancesResult","DBInstances","DBInstance","@attributes","requestId","ns");
		ParseArray($RdsArray['body']['DescribeDBInstancesResult']['DBInstances'],$FilterOut);
		break;
	case "rdsparams" :
		$Rds=new AmazonRDS($Credentials);
		$Rds->set_region(AmazonRDS::REGION_EU_W1);
		printf("\n  RDS PARAMETER GROUPS\n  ____________________\n\n");
		$RdsArray=(json_decode(json_encode($Rds->describe_db_parameter_groups()),TRUE));
		$FilterOut=array("DBParameterGroups","DBParameterGroup","@attributes","RequestId","ns");
		ParseArray($RdsArray['body']['DescribeDBParameterGroupsResult']['DBParameterGroups'],$FilterOut);
		break;
	case "rdssecgroups" :
		$Rds=new AmazonRDS($Credentials);
		$Rds->set_region(AmazonRDS::REGION_EU_W1);
		printf("\n  RDS SECURITY GROUPS\n  ___________________\n\n");
		$RdsSecArray=(json_decode(json_encode($Rds->describe_db_security_groups()),TRUE));
		$FilterOut=array("DBSecurityGroups","DBSecurityGroup","@attributes","RequestId","ns");
		ParseArray($RdsSecArray['body']['DescribeDBSecurityGroupsResult']['DBSecurityGroups'],$FilterOut);
		break;
	case "s3" :
		$S3Instance=new AmazonS3($Credentials);
//		$S3Instance->set_region(AmazonS3::REGION_EU_W1);
		printf("\n  S3 BUCKETS\n  __________\n\n");
		$S3Array=(json_decode(json_encode($S3Instance->get_bucket_list()),TRUE));
//		var_dump($S3Instance);
//		$FilterOut=array("@attributes","ns");
		$FilterOut=array("","");
		ParseArray($S3Array,$FilterOut);
		break;
	case "secgroups" :
		$Ec2Instance=new AmazonEC2($Credentials);
		$Ec2Instance->set_region(AmazonEc2::REGION_EU_W1);
		printf("\n  SECURITY GROUPS\n  _______________\n\n");
		$SecArray=(json_decode(json_encode($Ec2Instance->describe_security_groups()),TRUE));
		$FilterOut=array("item","@attributes","RequestId","ns");
		ParseArray($SecArray['body']['securityGroupInfo'],$FilterOut);
		break;
	case "tags" :
		$Ec2Instance=new AmazonEC2($Credentials);
		$Ec2Instance->set_region(AmazonEc2::REGION_EU_W1);
		printf("\n  INSTANCE TAG DETAILS\n  ____________________\n\n");
		$TagArray=(json_decode(json_encode($Ec2Instance->describe_tags()),TRUE));
		$FilterOut=array("item","@attributes","RequestId","ns");
		ParseArray($TagArray['body']['tagSet'],$FilterOut);
		break;
	case "volumes" :
		$Ec2Instance=new AmazonEC2($Credentials);
		$Ec2Instance->set_region(AmazonEc2::REGION_EU_W1);
		printf("\n  VOLUME DETAILS\n  ______________\n\n");
		$VolArray=(json_decode(json_encode($Ec2Instance->describe_volumes()),TRUE));
		$FilterOut=array("item","@attributes","RequestId","ns");
		ParseArray($VolArray['body']['volumeSet'],$FilterOut);
		break;
	case "vpcs" :
		$Ec2Instance=new AmazonEC2($Credentials);
		$Ec2Instance->set_region(AmazonEc2::REGION_EU_W1);
		printf("\n  VPCS DETAILS\n  ____________\n\n");
		$VpcsArray=(json_decode(json_encode($Ec2Instance->describe_vpcs()),TRUE));
		$FilterOut=array("@attributes","item");
		ParseArray($VpcsArray['body'],$FilterOut);
		break;
	case "vpnconnections" :
		$Ec2Instance=new AmazonEC2($Credentials);
		$Ec2Instance->set_region(AmazonEc2::REGION_EU_W1);
		printf("\n  VPN CONNECTION DETAILS\n  ______________________\n\n");
		$VpnArray=(json_decode(json_encode($Ec2Instance->describe_vpn_connections()),TRUE));
		$FilterOut=array("@attributes","item");
		ParseArray($VpnArray['body'],$FilterOut);
		break;
	case "vpngateways" :
		$Ec2Instance=new AmazonEC2($Credentials);
		$Ec2Instance->set_region(AmazonEc2::REGION_EU_W1);
		printf("\n  VPN GATEWAY DETAILS\n  ____________________\n\n");
		$VpnConnsArray=(json_decode(json_encode($Ec2Instance->describe_vpn_gateways()),TRUE));
		$FilterOut=array("@attributes","item");
		ParseArray($VpnConnsArray['body'],$FilterOut);
		break;
	default :
		Help();
		break;
}
exit(0);
?>
