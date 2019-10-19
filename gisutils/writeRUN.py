#!/usr/bin/python3
# -*- coding: utf-8 -*-
"""
Created on Jan, 2019

This script was created to read the JSON file of watershed
and write the information into input files of the APEX model.

The steps include:
    1. read in the JSON file.
    2. write the APEX input files.
The files to be written include:
    1. Run file: runname, sitenumber, monthly wea station no,
       wind weather station no, subarea no, 0 normal soil, 
       subdaily weather file.
    2. Site file: APEX can only have one site file. Since we
       calculate the station based on latitude and longitude.
       The subfile can have one daily file each.
    3. Site list: one site, multiple watersheds.
    4. Sub files: one watersheds, one subfile
    5. Sub list: list of subfiles
    6. Soil files: use the ws sub list.
    7. Soil list: use the ws sub list: one number, one mukey.
    8. OPS list: same as soil list.
    9. Monthly weather file: only one, call climate list
    10. Monthly weather list: only one
    11. Monthly wind file: only one
    12. Monthly wind list: only one
    13. Daily weather file: we can also use one for simplicity.
    14. Daily weather list: one for each subarea.
    
    
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


#######################################################
# Input and output file defining
#######################################################
fddata = sys.argv[1]
scenariofdname = sys.argv[2]
#fddata = 'devfld'

fdapexrun = os.path.join(
    fddata,
    'apexruns',
    scenariofdname
    )

fin_wssubvarjson = os.path.join(
    fdapexrun,
    'runsub.json'
    )

#######################################################
# Defining classes
#######################################################
class apexfuncs():
    

    #######################################################
    def initrunfiles(self, runfolder):
    
        # Remove the file if it exist
        fn_run = r"%s/APEXRUN.DAT" %(runfolder)
        if os.path.isfile(fn_run):
            os.remove(fn_run)
        
        outfid_run = 0
        outfid_run = open(fn_run, "w")

        return outfid_run    
    
    

    #######################################################
    def closerunfiles(self, fid):
        
        fid.writelines("%10s%7i%7i%7i%7i%7i%7i\n" %(\
                    "XXXXXXXXXX", 0, 0, 0, 0, 0, 0\
                    ))
        
        fid.close()


    #######################################################
    def write_runlines(self, runfid, subvars, scenario):
    
        #print(subvars.keys())
        '''
        1. Run file: runname, sitenumber, monthly wea station no,
        wind weather station no, subarea no, 0 normal soil, 
        subdaily weather file.
        '''
        for wsid in range(len(subvars.keys())):
        # APEXRUN is read with free format in APEX.exe
            runfid.writelines(u"%-10s%7i%7i%7i%7i%7i%7i\n" %(\
                    "RSUB%i_%s" %(wsid+1, scenario[3:]) ,\
                            1,\
                            1,\
                            1,\
                            wsid+1,\
                            0, 0\
                            ))
    


class apexinputs():

    def __init__(self):
        """Constructor."""
        
        # Read in the json files into dict
        self.subvars = {}
        self.subvars = self.read_json(fin_wssubvarjson)        
        
        # Initiate all variables
        self.fidrun = 0
        self.fidrun = apexfuncs.initrunfiles(fdapexrun)
        apexfuncs.write_runlines(self.fidrun, self.subvars, scenariofdname)
        
        # Close files
        apexfuncs.closerunfiles(self.fidrun)
        

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

apexfuncs = apexfuncs()
apexinputs = apexinputs()











#print("--- %s seconds ---" % (time.time() - start_time))











#######################################################




