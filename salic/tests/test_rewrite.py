#!/usr/bin/env python2

import requests

baseurl = "http://localhost"
tests = {
    "": True,
    "/": True,
    "/de/": True,
}

def performTest(url, expected):
    print("Testing: "+url)
    r = requests.get(baseurl + url, allow_redirects=False)
    status = r.status_code
    if status in (301,302):
        print("-> "+str(r.status_code)+": "+r.headers['Location'])
    print("-> "+str(r.status_code))
    
for url in tests:
    performTest(url, tests[url])