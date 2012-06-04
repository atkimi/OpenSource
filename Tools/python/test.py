#!/usr/bin/env python
# -*- coding: utf8 -*-
#
# Well you know how it is with a new language ...
# How do I concatonate strings? how do I cast integers to strings?
# How do create and manage strings and arrays(lists)?
# How do I test and extract command line arguments?
#
# This is my test  script to find out
#
#
#
def squareroot(value):
    print "Iterating to obtain squareroot of  " + str(value)
    loop=0
    a=value/2
    while loop < 10:
        print "Trying Value " + str(a)
        b=float(value/a)
        a=float((b+a)/2)
	if str(a) == str(b) : break
#        loop+=1
#
# Primes, not that the else statement is associated with second for loop, NOT the if
# Also should implement sieve of eratosthenes and skip even integers
#
def prime(upto):
  print "Finding prime numbers up to " + str(upto)
#  for a in range(2,upto):
  for a in range(2,upto): 
    for b in range(2,int(math.sqrt(a))):# sieve
      if  a%b == 0 : break  
    else:
     print "Found Prime number %4.d" % a # formatted printing in python - add a printf placeholder like printf then a second arg "%" then the value
#
#
# Fibonacci
#
#
def fib(flist):
    print "fibonacci numbers below " + str(flist)  
    a, b = 0, 1
    while b <= flist:
        print b
        a, b = b, a+b
#
# Main
#
def main():
    rawstring = r"This is a rather long string containing\n\
several lines of text much as you would do in C."
    print rawstring
    triplecodedstring = """
Here's a multiline string
embedded in triple quotes "`'@!£%^&*()_~#| 

"""
    print triplecodedstring
    fib(100)
    squareroot(100000)
    prime(1000)

## _____________________
##
## E N T R Y   P O I N T
## _____________________
##
import imp, sys, os, re, time, math
identifier = "python-%s-%s" % (sys.version[:3], sys.platform)
timestamp = time.strftime("%Y%m%dT%H%M%SZ", time.gmtime(time.time()))
print "\nTime stamp is " + timestamp + "\nPython Version is " + identifier + "\n\n"
if __name__ == "__main__":
    main()
