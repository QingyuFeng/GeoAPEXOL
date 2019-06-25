#!/usr/bin/python3
# -*- coding: utf-8 -*-
"""
Created on Dec, 2018

This script was developed to find the watersheds covered by the
field boundary.
We had one before, but it has some flaws and the logic seems not
right and not easy for modify.

This script will test the new logic:
1. Find the subareas whose downstream are outside the field boundary.
2. Use depth first search to find all the upstreams.


@author: qyfen
"""

#######################################################
# Environment setting
#######################################################
import sys,os
import json
import glob
import numpy as np

#######################################################
# Input and output file defining
#######################################################

fddata = sys.argv[1]

#fddata = 'devfld'

fd_taulayers = os.path.join(
    fddata,
    'taudemlayers'
    )

fd_reclademw = os.path.join(
    fd_taulayers,
    'reclademw'
    )

fd_apexrun = os.path.join(
    fddata,
    'apexruns'
    )


fin_allscenario = os.path.join(
     fd_apexrun,   
    'allscenario.json'
    ) 

fin_aaqsnpclass = os.path.join(
     fd_apexrun,   
    'susresults.json'
    ) 

fout_wtedaaqsnp = os.path.join(
     fd_apexrun,   
    'fldwtedqsnp.json'
    ) 




#######################################################
# Defining classes
#######################################################

class fldqsnp():

    def __init__(self):
        """Constructor."""
        
        # Get all reclassified watershed rasters
        self.allwsinfld = glob.glob('%s/fldb8recdemw*.asc' %(fd_reclademw))
        
        # Get the all scenarios
        self.allscenarios = self.readJSON(fin_allscenario)
    
        # Get all ws sub no of asc into field
        self.fldwssubno = None
        self.fldwssubno = self.getwssubinfld(self.allwsinfld)
        
        # Calculate weights: based on whole field area
        self.fieldarea, self.fldwssubno = self.getFieldarea(self.fldwssubno)

        # Get the wsaaqsnp json  
        self.aaqsnpjson = self.readJSON(fin_aaqsnpclass)
    
        # Calculate the area weighted average
        self.awqsnpjson = self.getawqsnp(self.fldwssubno,
                                     self.aaqsnpjson, 
                                     self.allscenarios)



    def getawqsnp(self,
                  fldwssubno,
                  aaqsnpjson,
                  allscenarios):
        
        outawqsnp = {}     
        
        for scek, scev in allscenarios.items():
            if not "full" in scek:
                outawqsnp[scev] = [0.0, 0.0, 0.0, 0.0]
                
                for k, v in fldwssubno.items():
                
                    wsid = k[12:]
        
                    for subid in range(len(v[1])):
                        subno = v[0][subid]
                        outawqsnp[scev][0] = outawqsnp[scev][0] + float(
                            aaqsnpjson[scek][str(wsid)][subno][0][0])*float(
                            v[2][subid]) 
                        outawqsnp[scev][1] = outawqsnp[scev][1] + float(
                            aaqsnpjson[scek][str(wsid)][subno][0][1])*float(
                            v[2][subid]) 
                        outawqsnp[scev][2] = outawqsnp[scev][2] + float(
                            aaqsnpjson[scek][str(wsid)][subno][0][2])*float(
                            v[2][subid]) 
                        outawqsnp[scev][3] = outawqsnp[scev][3] + float(
                            aaqsnpjson[scek][str(wsid)][subno][0][3])*float(
                            v[2][subid]) 
    

        return outawqsnp


    def readJSON(self, fn_json):

        inf_usrjson = {}
        
        with open(fn_json) as json_file:    
            inf_usrjson = json.loads(json_file.read())
        #pprint.pprint(inf_usrjson)
        json_file.close()
        
        return inf_usrjson




    def getFieldarea(self, fldwssubno):
        
        totalArea = []
        
        for key, val in fldwssubno.items():
            totalArea = totalArea + val[1]
            
        totalArea = sum(totalArea)

        for k, v in fldwssubno.items():
            for area in v[1]:
                v[2].append(float(area)/float(totalArea))


        return totalArea, fldwssubno



    def getwssubinfld(self, allwsinfld):
        
        outdict = {}
        
        for wssub in allwsinfld:
            
            wssubfld = None
            wssubfld = self.readASCstr(wssub)
            while wssubfld[2] in wssubfld[3]:
                wssubfld[3].remove(wssubfld[2])
            
            key = os.path.basename(wssub[:-4])
            outdict[key] = [[],[],[]]
            # There will be two elements:
            # 0: the unique values to get the values from
            # qsnp json
            # 1: the counts to get the area
            outdict[key][0] = list(set(wssubfld[3]))
            
            for wsbubid in outdict[key][0]:
                outdict[key][1].append(wssubfld[3].count(wsbubid))
                        
        
        return outdict


    def readASCstr(self, finasc):

        # Store data into a list
        data = []
        cellsize = 0.0
        
        # Reading files to a list 

        with open(finasc, 'r') as f:
            lif = f.read().splitlines()

        for lidx in range(len(lif)):
            lif[lidx] = lif[lidx].split(' ')
            while '' in lif[lidx]:
                lif[lidx].remove('')
    
        # 0: ncols, 1: nrows, 5: NoDATA, 4: cellsize
        data.append(lif[0][1])
        data.append(lif[1][1])    
        data.append(lif[5][1])
        cellsize = float(lif[4][1])*1.0
        
        del(lif[:7])
        del(lif[-1])

        data.append(lif)

        # Convert 2d asc array into 1d array
        data[3] = list(np.asarray(data[3]).ravel())
        #print(data[3].shape)

        # Cell size is needed for area calculation
        data.append(cellsize)

        return data






#######################################################
# Call Functions
#######################################################

fldqsnp = fldqsnp()
if os.path.isfile(fout_wtedaaqsnp):
    os.remove(fout_wtedaaqsnp)
    
with open(fout_wtedaaqsnp, 'w') as outfile:
    json.dump(fldqsnp.awqsnpjson, outfile)


#######################################################

