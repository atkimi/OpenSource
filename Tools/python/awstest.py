#!/usr/bin/env python
# -*- coding: utf8 -*-
#
#
# Amazon Web Services Boto test Script
#
#    This program is free software: you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation, either version 3 of the License, or
#    (at your option) any later version.
#
#    This program is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with this program.  If not, see <http://www.gnu.org/licenses/>.
#
#    Mike.Atkinson@quitenear.me
#
#    Lasted updated 7th June 2012
#
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
    return(None)
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
  import boto.rds
  import locale
  locale.setlocale(locale.LC_ALL, 'en_US.UTF-8')
  regionNames= []
  try:
    availableRegions=boto.ec2.regions(aws_access_key_id=account, aws_secret_access_key=key)
  except:
    print "\nCouldn't acquire AWS Regions\nAre your credentials correct?\n"
    exit(1)
  for Region in availableRegions:
    regionNames.append(str(Region.name))
    if  Region.name == myregion:
      chosenRegion=Region
      try:
        awsConnection = Region.connect(aws_access_key_id=account, aws_secret_access_key=key)
      except:
        print "\nCouldn't instantiate an AWS Instance\n"
        exit(1)
  if not 'awsConnection' in locals():
    print "\nCouldn't connect to region %s\nAvailable regions are %s\n" % (myregion,str(regionNames))
    exit(1)
##
## AWS VM's (Instances) 
##
  if  myobject == "instances": 
    try:
      instanceList=awsConnection.get_all_instances()
    except:
      print "\nUnable to get Instances for your account, Are your access credentials correct?\n"
      exit(1)
#    sortedlist=sorted(instanceList,key=lambda k: k['name'])
    chain = itertools.chain.from_iterable  
    for Reservation in instanceList:
      print list(chain([Reservation.instances]))
##
## AWS Security Groups
##
  elif myobject == "secgroup":
    try:
      securityGroupList=sorted(awsConnection.get_all_security_groups())
    except:
      print "\nUnable to get Security Groups for your account, Are your access credentials correct?\n"
      exit(1)
    for securitygroup in securityGroupList:
      print securitygroup
##
## RDS Instances
##
  elif myobject == "rds":
    try:
       RDSconn = boto.rds.connect_to_region(chosenRegion, aws_access_key_id=account,     aws_secret_access_key=key)
#      rdsconn=boto.rds.RDSConnection(aws_access_key_id=account, aws_secret_access_key=key, is_secure=True, port=None, proxy=None, proxy_port=None, proxy_user=None, proxy_pass=None, debug=0, https_connection_factory=None, region=myregion, path='/', security_token=None)
#      rdsconn=boto.rds.RDSConnection(aws_access_key_id=account, aws_secret_access_key=key, is_secure=True, port=None, proxy=None, proxy_port=None, proxy_user=None, proxy_pass=None, debug=0, https_connection_factory=None, region=myregion, path='/')
    except Exception,RDSe:
      sys.stderr.write('RDS Connection ERROR: %s\n' % str(RDSe))
      print "\nCouldn't instantiate an AWS RDS Connection\nAre your credentials correct?\n"
      exit(1)
#    try:
#      awsConnection = Region.connect(aws_access_key_id=account, aws_secret_access_key=key)
#    except:
#      print "\nCouldn't instantiate an AWS RDS Instance\n"
#      exit(1)
    try:
      rdsList=sorted(RDSconn.rds_conn.get_all_dbinstances())
    except:
      print "\nUnable to get RDS Instances for your account, Are your access credentials correct?\n"
      exit(1)
    for rdsvalue in rdsList:
#      print rdsvalue
      pprint(rdsvalue)
  else:
    print "\nObject %s not implemented yet\n" % myobject
  awsConnection.close

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
