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



fin_wssubsollujson = os.path.join(
    fdapexrun,
    'wssubsollulatlon%s.json' %(scenariofdname[3:])
    )




#######################################################
# Defining classes
#######################################################
class apexfuncs():

    #######################################################
    def initopscom(self, runfolder):

        outfn_opscom = "OPSCCOM.DAT"
        fn_opscom = r"%s/%s" %(runfolder,
                               outfn_opscom)
        
        # Remove file if exists:
        if (os.path.exists(fn_opscom)):
            os.remove(fn_opscom)


        #if os.path.isfile(fn_opscom):
        #    os.remove(fn_opscom)
              
        outfid_opscom = 0    
        outfid_opscom = open(fn_opscom, "w")
        
        return outfid_opscom

    
    #######################################################
    def writeopscomline(self, fidopscom, sollulatlong):

        for key, value in sollulatlong.items():
            if 'URBAN' in value["iopsnm"]:
                fidopscom.writelines("%5s\t%s.OPC\n" %(key,
                                              "URBAN"))
            else:
                fidopscom.writelines("%5s\t%s.OPC\n" %(key,
                                              value["iopsnm"]))    

    
    
    #######################################################
    def closecomfiles(self, fid):
        
        fid.close()
    
        
    
    
    
    
    
    
    


class apexinputs():

    def __init__(self):
        """Constructor."""
        
        # Read in the json files into dict
        self.sollulatlong = {}
        self.sollulatlong = self.read_json(fin_wssubsollujson)
        
        # Initiate all variables
        self.fidopscom = 0
        self.fidopscom = apexfuncs.initopscom(fdapexrun)

        # Write contents in the list files. Here, we suppose to
        # have the number of lines same as the number of watersheds
        # Get the number of subareas
        apexfuncs.writeopscomline(self.fidopscom, self.sollulatlong)
        
        # Close files
        apexfuncs.closecomfiles(self.fidopscom)
  



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


apexfuncs = apexfuncs()
apexinputs = apexinputs()







    
    
    
