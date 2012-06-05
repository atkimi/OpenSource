#!/usr/bin/env python
# -*- coding: utf8 -*-
#
# http://www.tutorialspoint.com/python/
#
# AWS test script 
#
def myhelp():
  print "\n\
\n\
  %s extracts data from AWS objects\n\
\n\
  Usage:\n\
\n\
    %s -f<Security Credentials File> -r <Region> -x<asg|asi|images|instances|ips|keypairs|loadbalancers|rds|rdsparams|rdssecgroups|s3|secgroups|tags|volumes|vpcs|vpnconnections|vpngateways>\n\
    %s -u<AWS Account> -p<AWS Security key> -r <Region> -x<asg|asi|images|ips|instances|keypairs|loadbalancers|rds|rdsparams|rdssecgroups|s3|secgroups|tags|volumes|vpcs|vpnconnections|vpngateways>\n\
\n\
" % (scriptname,scriptname,scriptname)
  print """
    Pre-requistes:
    ______________

    The python sdk "boto" 
    An AWS Credential file with the format ...
    
AWSAccessKeyId=<AWS Account>
AWSSecretKey=<AWS Security key value>
  
    or ...

    password and usernames values passed as command line arguments (option 2)


"""
  exit(1)
## ______________________________________________
##
## Read and return the contents of a file or None
## ______________________________________________
##
def readfiledata(fname):
  if not os.path.exists(fname):
    print  "%s: File %s does not exist or is not readable\n" % (scriptname,fname)
    exit(1)
  fhandle=open(fname,"r")
  filedata=fhandle.read()
  fhandle.close
  return(filedata)
# ____
#
# Main
# ____
#
def main(account,key,myregion,myobject):
  import boto.ec2
  regionNames= []
  try:
    availableRegions=boto.ec2.regions(aws_access_key_id=account, aws_secret_access_key=key)
  except:
    print "\nCouldn't acquire EC2 Regions\nAre your credentials correct?\n"
    exit(1)
  for Region in availableRegions:
    regionNames.append(str(Region.name))
    if  Region.name == myregion:
      try:
        ec2handle = Region.connect(aws_access_key_id=account, aws_secret_access_key=key)
      except:
        print "\nCouldn't instantiate an EC2 Instance\n"
        exit(1)
  if not 'ec2handle' in locals():
    print "\nCouldn't connect to region %s\nAvailable regions are %s\n" % (myregion,str(regionNames))
    exit(1)
  if  myobject == "instances": 
    try:
      instanceList=ec2handle.get_all_instances()
    except:
      print "\nUnable to get Instances for your account, Are your access credentials correct?\n"
      exit(1)
    chain = itertools.chain.from_iterable  
    for Reservation in instanceList:
      print list(chain([Reservation.instances]))
  elif myobject == "secgroup":
    try:
      securityList=ec2handle.get_all_security_groups()
    except:
      print "\nUnable to get Security Groups for your account, Are your access credentials correct?\n"
      exit(1)
    for securitygroup in securityList:
      print securitygroup
  else:
    print "\nObject %s not implemented yet\n" % myobject
  ec2handle.close

##  _____________________
##
##  E N T R Y   P O I N T
##  _____________________
##
import imp, sys, os, re, time, math, optparse,itertools
from optparse import OptionParser
clibits=sys.argv[0].split('/')
scriptname=clibits[len(clibits)-1]
myparser = OptionParser()
myparser.add_option("-f", "--file", help="AWS Credential filename",dest="securityfile")
myparser.add_option("-u",help="AWS ID",dest="username")
myparser.add_option("-p",help="AWS Key",dest="password")
myparser.add_option("-r",help="AWS Region",dest="region")
myparser.add_option("-x",help="AWS Object",dest="awsobject")
myparser.add_option("-d", action='store_true',help="Debug",dest="debug")
(options, args) = myparser.parse_args()
if options.awsobject is None:
  myhelp()
if options.securityfile is not None:
  for line in readfiledata(options.securityfile).splitlines():
    (Key,Value)=line.rsplit("=") 
    if Key == "AWSAccessKeyId":
      username=Value
    if Key == "AWSSecretKey":
      password=Value
else:
  if options.username is not None and options.password is not None:
    username=options.username
    password=options.password
  else:
    myhelp()
if options.awsobject is None:
  myhelp() 
if options.region is None:
  myhelp()
if __name__ == "__main__":
    main(username,password,options.region,options.awsobject)
