#!/usr/bin/python3
# -*- coding: utf-8 -*-
"""
Created on Jan, 2019


All the information shall come from the wssubjson and the
wssubsoillulatlon file.

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
fvtiledep = sys.argv[2]
scenariofdname = sys.argv[3]

#fvtiledep = '1.00'

#fddata = 'devfld'

fdapexrun = os.path.join(
    fddata,
    'apexruns',
    scenariofdname
    )

fin_runsubjson = os.path.join(
    fdapexrun,
    'runsub.json'
    )


fin_runsoljson = os.path.join(
    fdapexrun,
    'runsol.json'
    )


fin_soillujson = os.path.join(
    fdapexrun,
    'wssubsollulatlon.json'
    )


#######################################################
# Defining classes
#######################################################
#class apexfuncs():






class apexsubs():

    def __init__(self):
        """Constructor."""

        # Read in the json files into dict
        self.subjson = {}
        self.subjson = self.read_json(fin_runsubjson)

        self.soljson = {}
        self.soljson = self.read_json(fin_runsoljson)

        # Get the dictionary store the hsg of each mukey
        self.solmkhsg = {}
        self.solmkhsg = self.getMkHSG(self.soljson,
                                      self.solmkhsg)

        # Read in the json files into dict
        self.solluson = {}
        self.solluson = self.read_json(fin_soillujson)

        # Modify sub json to install tile drainage
        self.modsubjson = {}
        self.modsubjson = self.modifySUBjson(self.solluson,
                                             self.solmkhsg,
                                             self.subjson)



    def modifySUBjson(self, solluson, solmkhsg, subjson):

        modsubjs = copy.deepcopy(subjson)

        for wsk, wsv in subjson.items():
            for subidx in range(len(wsv["model_setup"]["subid_snum"])):
                solid = wsv["soil"]["soilid"][subidx]
                soilhsg = solmkhsg[solluson[str(solid)]['mukey']]

                if ((soilhsg == 'C') or (soilhsg == 'D')):

                    modsubjs[wsk]["drainage"][
                            "drainage_depth_idr"][subidx] = fvtiledep

        return modsubjs




    def getMkHSG(self, soljson, solmkhsg):

        for k, v in soljson.items():
            if "/" in v["line2"]["hydrologicgroup_hsg"]:
                v["line2"]["hydrologicgroup_hsg"]=\
                    v["line2"]["hydrologicgroup_hsg"].split("/")[0]
            solmkhsg[k] = v["line2"]["hydrologicgroup_hsg"]

        return solmkhsg



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

#apexfuncs = apexfuncs()
apexsubs = apexsubs()




os.remove(fin_runsubjson)
with open(fin_runsubjson, 'w') as outfile:
    json.dump(apexsubs.modsubjson, outfile)






print("--- %s seconds ---" % (time.time() - start_time))











#######################################################

