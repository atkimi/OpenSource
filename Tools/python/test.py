#!/usr/bin/env python
# -*- coding: utf8 -*-
#
# Fibonacci
#
#
def fib():
    a, b = 0, 1
    while b < 10:
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
    fib()

## _____________________
##
## E N T R Y   P O I N T
## _____________________
##
import imp, sys, os, re, time
identifier = "python-%s-%s" % (sys.version[:3], sys.platform)
timestamp = time.strftime("%Y%m%dT%H%M%SZ", time.gmtime(time.time()))
if __name__ == "__main__":
    main()
