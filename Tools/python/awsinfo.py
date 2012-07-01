#!/usr/bin/env python
#
# Documentation for PHP SDK for Amazon Web Services is at 
# 
#http:#docs.amazonwebservices.com/AWSSDKforPHP/latest/
#
import boto
#
def Help()

#  global $ScriptName,$Revision;
  print """ 
 
  $ScriptName extracts data from AWS objects

  Usage:
  
    $ScriptName -f<Security Credentials File> -x<asg|asi|images|instances|ips|keypairs|loadbalancers|rds|rdsparams|rdssecgroups|s3|secgroups|tags|volumes|vpcs|vpnconnections|vpngateways>
    $ScriptName -u<AWS Account> -k<AWS Security key> -x<asg|asi|images|ips|instances|keypairs|loadbalancers|rds|rdsparams|rdssecgroups|s3|secgroups|tags|volumes|vpcs|vpnconnections|vpngateways>

    Pre-requistes:
    ______________


    The python sdk "boto" 
    An AWS Credential file with the format ...
    
AWSAccessKeyId=<AWS Account>
AWSSecretKey=<AWS Security key value>
  
    or ...

    values passed as command line arguments 


"""
  exit(1);

#
# Print concatonated key and its value 
#
def PrintData(DepthArray,Value):

#  global $LastItem,$Item;
  if ((count($DepthArray) > 1 )) { $LastItem;}
  $DepthString=implode(":",$DepthArray);
  $DepthStringLength=strlen($DepthString);
  if $DepthStringLength <80 :
    print "%-80.80s%s\n" % (DepthString,$Value) 
  elif $DepthStringLength <120 :
    print "%-120.120s%s\n" % (DepthString,Value)
  else  print "%s %s\n" % (DepthString,Value) 

#
#
def ParseArray(AwsArray,FilterArray):

#  global $DepthArray; 
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
#
# _____________________
#
# E N T R Y   P O I N T
# _____________________
#
#
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
  print """\nFailed to determine credentials for AWS access\n\"""
  exit(1); 
} 
$Credentials=array("key"=>$AccessKey,"secret"=>$Password);
switch($CommandFlags['x'])
{
if  object == "asg":
    $ASInstance=new AmazonAS($Credentials);
    $ASInstance->set_region(AmazonAS::REGION_EU_W1);
    print """\n  AUTO SCALING GROUPS\n  ______________________\n\n"""
    $ASIArray=(json_decode(json_encode($ASInstance->describe_auto_scaling_groups()),TRUE));
    $FilterOut=array("DescribeAutoScalingGroupsResult","AutoScalingGroups","member","@attributes","ns","RequestId");
    ParseArray($ASIArray['body']['DescribeAutoScalingGroupsResult']['AutoScalingGroups'],$FilterOut);
elif object == "asi" :
    $ASInstance=new AmazonAS($Credentials);
    $ASInstance->set_region(AmazonAS::REGION_EU_W1);
    print """\n  AUTO SCALING INSTANCES\n  ______________________\n\n"""
    $ASIArray=(json_decode(json_encode($ASInstance->describe_auto_scaling_instances()),TRUE));
    $FilterOut=array("AutoScalingInstances","member","@attributes","ns","RequestId");
    ParseArray($ASIArray['body']['DescribeAutoScalingInstancesResult']['AutoScalingInstances'],$FilterOut);
#
# Need to sort out default cache malarkey stuff here
#  
elif object == "dyn" :
    $DynInstance=new AmazonDynamoDB($Credentials);
    print """\n________\n So far so good\"""
    $DynInstance->set_region(AmazonDynamoDB::REGION_EU_W1);
    print """\n  DYNAMO DBS\n  __________\n\"""
    $DynArray=(json_decode(json_encode($DynInstance->list_tables()),TRUE));
    $FilterOut=array("AutoScalingInstances","member","@attributes","ns","RequestId");
    ParseArray($DynArray['body'],$FilterOut);
elif object == "iam" :
    $IamInstance=new AmazonIAM($Credentials);
#    $IamInstance->set_region(AmazonIAM::REGION_EU_W1);
    print """\n  Identity AM DETAILS\n  ___________________\n\"""
    $IAMArray=(json_decode(json_encode($IamInstance->list_users()),TRUE));
    $FilterOut=array("IsTruncated","Users","member","ns","@attributes","RequestId");
    ParseArray($IAMArray['body']['ListUsersResult']['Users'],$FilterOut);
elif object == "images" :
    $Ec2Instance=new AmazonEC2($Credentials);
    $Ec2Instance->set_region(AmazonEc2::REGION_EU_W1);
    print """\n  AMI DETAILS\n  ___________\n\"""
    $AMIArray=(json_decode(json_encode($Ec2Instance->describe_images()),TRUE));
    $FilterOut=array("imageSet","item","@attributes","RequestId");
    ParseArray($AMIArray['body']['imagesSet'],$FilterOut);
elif object == "ips" :
    $Ec2Instance=new AmazonEC2($Credentials);
    $Ec2Instance->set_region(AmazonEc2::REGION_EU_W1);
    print """\n  FIXED (Elastic) IP's\n  ____________________\n\"""
    $ElasticIPArray=(json_decode(json_encode($Ec2Instance->describe_addresses()),TRUE));
    $FilterOut=array("addressesSet","item","@attributes","RequestId","ns");
    ParseArray($ElasticIPArray['body']['addressesSet'],$FilterOut);
elif object == "instances" :
    from boto.ec2.connection import EC2Connection
    ec2handle = EC2Connection(Credentials)
    $Ec2Instance=new AmazonEC2($Credentials);
    $Ec2Instance->set_region(AmazonEc2::REGION_EU_W1);
    print """\n  INSTANCE DETAILS\n  ________________\n\"""
    $InstanceArray=(json_decode(json_encode($Ec2Instance->describe_instances()),TRUE));
    $FilterOut=array("requestId","reservationSet","item","@attributes","RequestId","ns");
    ParseArray($InstanceArray['body']['reservationSet'],$FilterOut);
elif object == "keypairs" :
    $Ec2Instance=new AmazonEC2($Credentials);
    $Ec2Instance->set_region(AmazonEc2::REGION_EU_W1);
    print """\n  KEY PAIRS\n  __________\n\"""
    $KeyArray=(json_decode(json_encode($Ec2Instance->describe_key_pairs()),TRUE));
    $FilterOut=array("keySet","item","@attributes","RequestId");
    ParseArray($KeyArray['body']['keySet'],$FilterOut);
elif object == "loadbalancers" :
    $Elb=new AmazonELB($Credentials);
    $Elb->set_region(AmazonELB::REGION_EU_W1);
    print """\n  LOAD BALANCERS\n  ______________\n\"""
    $LBArray=(json_decode(json_encode($Elb->describe_load_balancers()),TRUE));
    $FilterOut=array("DescribeLoadBalancersResult","LoadBalancerDescriptions","member","@attributes","RequestId");
    ParseArray($LBArray['body']['DescribeLoadBalancersResult']['LoadBalancerDescriptions'],$FilterOut);
elif object == "rds" :
    $Rds=new AmazonRDS($Credentials);
    $Rds->set_region(AmazonRDS::REGION_EU_W1);
    print """\n  RDS INSTANCES\n  _____________\n\"""
    $RdsArray=(json_decode(json_encode($Rds->describe_db_instances()),TRUE));
    $FilterOut=array("DescribeDBInstancesResult","DBInstances","DBInstance","@attributes","requestId","ns");
    ParseArray($RdsArray['body']['DescribeDBInstancesResult']['DBInstances'],$FilterOut);
elif object == "rdsparams" :
    $Rds=new AmardssecgroupszonRDS($Credentials);
    $Rds->set_region(AmazonRDS::REGION_EU_W1);
    print """\n  RDS PARAMETER GROUPS\n  ____________________\n\"""
    $RdsArray=(json_decode(json_encode($Rds->describe_db_parameter_groups()),TRUE));
    $FilterOut=array("DBParameterGroups","DBParameterGroup","@attributes","RequestId","ns");
    ParseArray($RdsArray['body']['DescribeDBParameterGroupsResult']['DBParameterGroups'],$FilterOut);
elif object == "rdssecgroups" :
    $Rds=new AmazonRDS($Credentials);
    $Rds->set_region(AmazonRDS::REGION_EU_W1);
    print """\n  RDS SECURITY GROUPS\n  ___________________\n\"""
    $RdsSecArray=(json_decode(json_encode($Rds->describe_db_security_groups()),TRUE));
    $FilterOut=array("DBSecurityGroups","DBSecurityGroup","@attributes","RequestId","ns");
    ParseArray($RdsSecArray['body']['DescribeDBSecurityGroupsResult']['DBSecurityGroups'],$FilterOut);
elif object == "s3" :
    $S3Instance=new AmazonS3($Credentials);
    print """\n  S3 BUCKETS\n  __________\n\"""
    $S3Array=(json_decode(json_encode($S3Instance->get_bucket_list()),TRUE));
    $FilterOut=array("","");
    ParseArray($S3Array,$FilterOut);
elif object == "secgroups" :
    $Ec2Instance=new AmazonEC2($Credentials);
    $Ec2Instance->set_region(AmazonEc2::REGION_EU_W1);
    print """\n  SECURITY GROUPS\n  _______________\n\"""
    $SecArray=(json_decode(json_encode($Ec2Instance->describe_security_groups()),TRUE));
    $FilterOut=array("item","@attributes","RequestId","ns");
    ParseArray($SecArray['body']['securityGroupInfo'],$FilterOut);
elif object == "tags" :
    $Ec2Instance=new AmazonEC2($Credentials);
    $Ec2Instance->set_region(AmazonEc2::REGION_EU_W1);
    print """\n  INSTANCE TAG DETAILS\n  ____________________\n\"""
    $TagArray=(json_decode(json_encode($Ec2Instance->describe_tags()),TRUE));
    $FilterOut=array("item","@attributes","RequestId","ns");
    ParseArray($TagArray['body']['tagSet'],$FilterOut);
elif object == "volumes" :
    $Ec2Instance=new AmazonEC2($Credentials);
    $Ec2Instance->set_region(AmazonEc2::REGION_EU_W1);
    print """\n  VOLUME DETAILS\n  ______________\n\"""
    $VolArray=(json_decode(json_encode($Ec2Instance->describe_volumes()),TRUE));
    $FilterOut=array("item","@attributes","RequestId","ns");
    ParseArray($VolArray['body']['volumeSet'],$FilterOut);
elif object == "vpcs" :
    $Ec2Instance=new AmazonEC2($Credentials);
    $Ec2Instance->set_region(AmazonEc2::REGION_EU_W1);
    print """\n  VPCS DETAILS\n  ____________\n\"""
    $VpcsArray=(json_decode(json_encode($Ec2Instance->describe_vpcs()),TRUE));
    $FilterOut=array("@attributes","item");
    ParseArray($VpcsArray['body'],$FilterOut);
elif object == "vpnconnections" :
    $Ec2Instance=new AmazonEC2($Credentials);
    $Ec2Instance->set_region(AmazonEc2::REGION_EU_W1);
    print """\n  VPN CONNECTION DETAILS\n  ______________________\n\"""
    $VpnArray=(json_decode(json_encode($Ec2Instance->describe_vpn_connections()),TRUE));
    $FilterOut=array("@attributes","item");
    ParseArray($VpnArray['body'],$FilterOut);
elif object == "vpngateways" :
    $Ec2Instance=new AmazonEC2($Credentials);
    $Ec2Instance->set_region(AmazonEc2::REGION_EU_W1);
    print """\n  VPN GATEWAY DETAILS\n  ____________________\n\"""
    $VpnConnsArray=(json_decode(json_encode($Ec2Instance->describe_vpn_gateways()),TRUE));
    $FilterOut=array("@attributes","item");
    ParseArray($VpnConnsArray['body'],$FilterOut);
else :
    Help();
exit(0);
