#!/usr/bin/python

import json
import subprocess

from pprint import pprint

#Import the list of email addresses from a file
json_data=open('emailUsers.json').read()
data = json.loads(json_data)

# This gives me one email address: 
#pprint(data["results"][0]["email"])

#log file directory
logdir = "/var/log/captureui.log*"

#open file for writing
f = open('search_results.txt','w')

#Now go through every email address, and check the log files to see if
# an email has been sent. Then log the results.
# If an email has not been sent, then include the number
i = 0
for item in data["results"]:
        email = item['email']
        print "searching for " + email +"\n"
        cmd = ["""grep '""" + email + """' """ + logdir]
        #print cmd
        try:
                output = subprocess.check_output(cmd,shell=True,stderr=subprocess.STDOUT)
                print output
                f.write(output)
        except subprocess.CalledProcessError as e:
                i += 1
                notfound =  "Did not find " + email + "address in the log file " +str(i) +"\n"
                print notfound
                f.write(notfound)

print "done!"

