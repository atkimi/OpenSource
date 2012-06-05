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
    %s -f<Security Credentials File> -x<asg|asi|images|instances|ips|keypairs|loadbalancers|rds|rdsparams|rdssecgroups|s3|secgroups|tags|volumes|vpcs|vpnconnections|vpngateways>\n\
    %s -u<AWS Account> -p<AWS Security key> -x<asg|asi|images|ips|instances|keypairs|loadbalancers|rds|rdsparams|rdssecgroups|s3|secgroups|tags|volumes|vpcs|vpnconnections|vpngateways>\n\
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
##
## Read and return the contents of a file or None
##
def readfiledata(fname):
  if not os.path.exists(fname):
    print  "%s: File %s does not exist or is not readable\n" % (scriptname,fname)
    exit(1)
  fhandle=open(fname,"r")
  filedata=fhandle.read()
  fhandle.close
  return(filedata)
#
# Main
# ____
#
def main():
  print """Orf we go"""
## _____________________
##
## E N T R Y   P O I N T
## _____________________
##
import imp, sys, os, re, time, math, optparse,boto
from optparse import OptionParser
clibits=sys.argv[0].split('/')
scriptname=clibits[len(clibits)-1]
myparser = OptionParser()
myparser.add_option("-f", "--file", help="AWS Credential filename",dest="securityfile")
myparser.add_option("-u",help="AWS ID",dest="username")
myparser.add_option("-p",help="AWS Key",dest="password")
myparser.add_option("-x",help="AWS Object",dest="awsobject")
myparser.add_option("-d", action='store_true',help="Debug",dest="debug")
(options, args) = myparser.parse_args()
#print "Got %d args" % len(args)
if options.awsobject is None:
  myhelp()
if options.securityfile is not None:
  for line in readfiledata(options.securityfile).splitlines():
    (Key,Value)=line.rsplit("=") 
#    if member.AWSxxx is not None:
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
 
print "Username is " + username + " Password is " + password
Credentials = ""
from boto.ec2.connection import EC2Connection
ec2handle = EC2Connection(Credentials)
if __name__ == "__main__":
    main()
