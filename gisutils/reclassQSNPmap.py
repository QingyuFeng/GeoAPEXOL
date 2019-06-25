#!/usr/bin/python
# -*- coding: utf-8 -*-
"""
Created on Feb 2019

Reclassify the dem to levels based on the levels defined before.
Input will be the classes
Output will be the tifs.


@author: qyfen
"""

#######################################################
# Environment setting
#######################################################
import sys,os
import time
import copy
import json
import numpy as np
import pandas as pd
import math


#######################################################
# Input and output file defining
#######################################################

fddata = sys.argv[1]
webdir = sys.argv[2]

#fddata = 'devfld'

fdapexrun = os.path.join(
    fddata,
    'apexruns'
    )

fin_allscenario = os.path.join(
     fdapexrun,   
    'allscenario.json'
    ) 


fd_reclassifieddemw = os.path.join(
    fddata,
    'taudemlayers'
    )

fin_demwreclasspair = os.path.join(
    fd_reclassifieddemw,
    'reclademw',
    'demwreclasspair.json'
    )


fd_rsnpmap = os.path.join(
    fddata,
    'apexoutjsonmap'
    )


fin_susqsnpvalueslevels = os.path.join(
     fdapexrun,   
    'susresults.json'
    ) 

gdalrecpyfile = os.path.join(
    webdir,
    'gisutils',
    'gdal_reclassify.py'
    )


#######################################################
# Defining classes
#######################################################
class MainClass():

    def __init__(self):
        """Constructor."""

        # Get the subarea information
        # wssubflddict: A dictionary to store the lines
        # in the wssubfld. Each line contains the subareas
        # in one watershed covered by the field.
        self.susqsnpvallvl = self.readJSON(fin_susqsnpvalueslevels)       
#        import pprint
#        pprint.pprint(self.susqsnpvallvl)
        # Get the subarea information
        # wssubflddict: A dictionary to store the lines
        # in the wssubfld. Each line contains the subareas
        # in one watershed covered by the field.
        self.wssubflddict = self.readWsSubFlds(fin_demwreclasspair)

        # This modification will change the structure of the dictionary.
        # From the former steps, we matched the subarea no in the 
        # simulation (subfiles) to that of displayed in the map.
        # Here, this modification will change the list into a 
        # dictionary, with reclassified subno as key and used to
        # store the output from each subarea.
        self.wssubflddict2 = self.modifywssubdict(self.wssubflddict)        

        # Get the all scenarios
        self.allscenarios = self.readJSON(fin_allscenario)
    

        # Then, convert the raster into the corresponding
        # output values
        self.geneRSNPmapallSce(
                self.susqsnpvallvl,
                 fd_reclassifieddemw,
                 self.wssubflddict2,
                 self.allscenarios)
        
        
    def geneRSNPmapallSce(self, 
                  susqsnpvallvl,
                 fd_reclassifieddemw,
                 wssubflddict2,
                 allscenarios):        
        # Loop through all scenarios
        for scek, vallvl in susqsnpvallvl.items():
            # Reclass each watershed
            self.geneRSNPmap( 
                        vallvl,
                        fd_rsnpmap,
                        allscenarios[scek],
                        wssubflddict2)

        
        
        
        
    def readJSON(self, fn_json):

        inf_usrjson = {}
        
        with open(fn_json) as json_file:    
            inf_usrjson = json.loads(json_file.read())
        #pprint.pprint(inf_usrjson)
        json_file.close()
        
        return inf_usrjson
        
        
        
        
    def geneRSNPmap(self,
                    allwsvallvl,
                    fdout_rsnpmap,
                    scenario,
                    wssubflddict2):
        # Loop through all watersheds
        for wsno, rsnpval in allwsvallvl.items():
            # There are four variables to generate:
            # source classes
            # dest classes
            # source raster
            # destination raster
            srcclass = []
            destclaq = []
            destclarsl2 = []
            destclatn = []
            destclatp = []
            srcrst = ''
            destrstq = ''
            destrstrsl2 = ''
            destrsttn = ''
            destrsttp = ''
            
            srcrst = os.path.join(
                        fd_reclassifieddemw,
                        'reclademw',
                        'b8recdemw%s.tif' %(wsno))
            destrstq =  os.path.join(
                        fdout_rsnpmap,
                        'aaq%s_%s.tif' %(wsno, scenario))
            destrstrsl2 =  os.path.join(
                        fdout_rsnpmap,
                        'aarsl2%s_%s.tif' %(wsno, scenario))
            destrsttn =  os.path.join(
                        fdout_rsnpmap,
                        'aatn%s_%s.tif' %(wsno, scenario))
            destrsttp =  os.path.join(
                        fdout_rsnpmap,
                        'aatp%s_%s.tif' %(wsno, scenario))
            
            # Create subarea reclassify pairs: old new classes
            for subdemwno, subnewno in wssubflddict2[wsno].items():
                srcclass.append("==" + str(subdemwno))
                # rsnp[subdemwno][[0][1]] [0] has four class
                # values: Q, SE, N, and P

                destclaq.append(rsnpval[str(subdemwno)][1][0])
                destclarsl2.append(rsnpval[str(subdemwno)][1][1])
                destclatn.append(rsnpval[str(subdemwno)][1][2])
                destclatp.append(rsnpval[str(subdemwno)][1][3])
            
            print()
                
            self.reclassify( 
                   srcclass,
                   destclaq,
                   srcrst,
                   destrstq)                
                
            self.reclassify(
                   srcclass,
                   destclarsl2,
                   srcrst,
                   destrstrsl2)
            
            self.reclassify(
                   srcclass,
                   destclatn,
                   srcrst,
                   destrsttn)
            
            self.reclassify( 
                   srcclass,
                   destclatp,
                   srcrst,
                   destrsttp)                
                
            
    def reclassify(self, 
                   src_classes,
                   des_classes,
                   srcrast,
                   destrast):

        """
        # gdal_reclassify.py [-c source_classes] [-r dest_classes] [-d default] [-n default_as_nodata] src_dataset dst_dataset

        Example of using the tool:
        python gdal_reclassify.py source_dataset.tif destination_dataset.tif -c "<30, <50, <80, ==130, <210"
        -r "1, 2, 3, 4, 5" -d 0 -n true -p "COMPRESS=LZW"

        Steps include:
            1. generate the source classes
            2. generate the dest_classes
            3. generate the command
            4. run the command

        """
        
        srcclasses = ",".join(src_classes)
        destclasses = ",".join(des_classes)

        # Delete dest file if exist:
        if (os.path.exists(destrast)):
            os.remove(destrast)
        
        # Then generate command
        cmd1 = ['python '
                + gdalrecpyfile
                + ' '
               + srcrast
               + ' '
               + destrast
               + ' -c "'
               + srcclasses
               + '" -r "'
               + destclasses
               + '" -d 0 -n true']
        os.system(cmd1[0])

       

    def modifywssubdict(self, wssubflddict):
        
        wsdict2 = copy.deepcopy(wssubflddict)
        
        for key, value in wssubflddict.items():
            wsdict2[key] = {}
            # Here key is the watershed number.
            # value has [0 as demw subno, 1 as reclassified
            # subarea no]
            for vidx in range(len(value[1])):
                #print(wsdict2[key][0][vidx])
                wsdict2[key][str(value[1][vidx])] = value[0][vidx]

        
        return wsdict2
    
    
    
    
    def readWsSubFlds(self, fn_json):

        inf_usrjson = {}
        
        with open(fn_json) as json_file:    
            inf_usrjson = json.loads(json_file.read())
        #pprint.pprint(inf_usrjson)
        json_file.close()
        
        return inf_usrjson    
                    
        
               
    
    



#######################################################
# Call Functions
#######################################################

start_time = time.time()

MainClass = MainClass()



print("--- %s seconds ---" % (time.time() - start_time))

#######################################################

