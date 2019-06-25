#!/usr/bin/python3
# -*- coding: utf-8 -*-
#!/usr/bin/python3
# -*- coding: utf-8 -*-
"""
Created on Feb, 2019
@author: qyfen
"""

#######################################################
# Environment setting
#######################################################
import sys,os
import time
import json
import copy

#######################################################
# Input and output file defining
#######################################################

fddata = sys.argv[1]
scenario = sys.argv[2]

#fddata = 'devfld'
#scenario = 'trees'

fdapexrun = os.path.join(
    fddata,
    'apexruns'
    )

fin_wssubsollujson = os.path.join(
    fdapexrun,
    'wssubsollulatlon.json'
    )

fout_wssubsollujson = os.path.join(
    fdapexrun,
    'wssubsollulatlon%s.json' %(scenario)
    )



#######################################################
# Defining classes
#######################################################  
class apexinputs():

    def __init__(self):
        """Constructor."""
        
        # Readin the oldjson
        self.sollulatlong = {}
        self.sollulatlong = self.read_json(fin_wssubsollujson)
        
        # Modify the json
        self.newjson = self.modfallow(self.sollulatlong, scenario)


    def modfallow(self, oldjson, scenario):

        newjson = copy.deepcopy(oldjson)
            
        for k, v in newjson.items():
            
            if scenario == 'fallow':
                newjson[k]["iopsnm"] = "FALLOW"
            elif scenario == 'peregrass':
                newjson[k]["iopsnm"] = "PAST"
            elif scenario == 'trees':
                newjson[k]["iopsnm"] = "TREES"    
                
                
            
        return newjson


    def read_json(self, jsonname):

        inf_usrjson = None
        
        with open(jsonname) as json_file:    
            inf_usrjson = json.loads(json_file.read())
#        pprint.pprint(inf_usrjson)
        json_file.close()
        
        return inf_usrjson

            




#######################################################
# Call Functions
#######################################################

start_time = time.time()

apexinputs = apexinputs()

if os.path.isfile(fout_wssubsollujson):
    os.remove(fout_wssubsollujson)
with open(fout_wssubsollujson, 'w') as outfile:
    json.dump(apexinputs.newjson, outfile)








print("--- %s seconds ---" % (time.time() - start_time))











#######################################################




