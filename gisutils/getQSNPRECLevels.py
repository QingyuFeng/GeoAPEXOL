#!/usr/bin/python
# -*- coding: utf-8 -*-
"""
Created on Jan 2019
get the levels of all classes and used for reclassification

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

fin_legendlevels = os.path.join(
     fd_rsnpmap,   
    'susrsltclasses.json'
    ) 

fout_legendlevels = os.path.join(
     fd_rsnpmap,   
    'susrsltclassesglobal.json'
    ) 


fnout_aaqsnpclassabs = os.path.join(
     fdapexrun,   
    'susresults.json'
    ) 


#######################################################
# Defining classes
#######################################################
class MainClass():

    def __init__(self):
        """Constructor."""

        # Get the legend classes
        self.msalegendlevel = self.readJSON(fin_legendlevels)

        # Get the all scenarios
        self.allscenarios = self.readJSON(fin_allscenario)
    
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

        # Read in the sus, get the values, get the max of each vari
        # store the results
        # Stores scenario:allwaterhed:susforallwatershed
#        self.allsce_susdf, self.qsnpmax = self.sus2wssubrsnp(
#                                            fdapexrun,
#                                            self.wssubflddict2, 
#                                            self.allscenarios)
        
        # Commented by Qingyu Feng
        # The SUS results has some problems and we always met **s in it.
        # I will get now all of the results from the MSA files.
#        print(self.allsce_susdf, self.qsnpmax)
        self.allsce_msa, self.qsnpmax = self.MSA2wssubrsnp(
                fdapexrun,
                self.wssubflddict2, 
                self.allscenarios)
           
        # Then, generate global levels and assign to each class
        self.wssubrsnp, self.msalegendlevelnew = self.getAssignLevelsMSA(
                self.allsce_msa,
                self.qsnpmax,
                self.msalegendlevel,
                self.wssubflddict2
                )
        
#        print(self.wssubrsnp)



    def getAssignLevelsMSA(self,
                        allsce_msa,
                        qsnpmax,
                        msalegendlevel,
                        wssubflddict2
                        ):
                
        # Generate levels based on max values:
        qlevel = self.generateLevels(float(qsnpmax[0]), 10)
        selevel = self.generateLevels(float(qsnpmax[1]), 10)
        nlevel = self.generateLevels(float(qsnpmax[2]), 10)
        plevel = self.generateLevels(float(qsnpmax[3]), 10)
        
        # Modify the suslegendlevel json based on new values
        newlevel = copy.deepcopy(msalegendlevel)
        for lidx in range(10):
            levkey = "level%i" %(lidx+1)
            newlevel['runoff'][levkey]["min"] = qlevel[lidx]
            newlevel['runoff'][levkey]["max"] = qlevel[lidx+1]
            newlevel['soilerosion'][levkey]["min"] = selevel[lidx]
            newlevel['soilerosion'][levkey]["max"] = selevel[lidx+1]
            newlevel['nitrogen'][levkey]["min"] = nlevel[lidx]
            newlevel['nitrogen'][levkey]["max"] = nlevel[lidx+1]
            newlevel['phosphorus'][levkey]["min"] = plevel[lidx]
            newlevel['phosphorus'][levkey]["max"] = plevel[lidx+1]
    
        allsceoutdict = {}
        
        # Modify the results to generate a dictionary 
        for ks, vs in allsce_msa.items():
            allsceoutdict[ks] = {}
            msarltdict = None
            msarltdict = copy.deepcopy(wssubflddict2)
            # Loop through the watersheds and read in asa
            for key, value in wssubflddict2.items():
                allsceoutdict[ks] = self.assignLevelsMSA(
                            allsce_msa[ks][key], 
                            qlevel,
                            selevel,
                            nlevel,
                            plevel,
                            msarltdict,
                            key)
                
        return allsceoutdict, newlevel



    def assignLevelsMSA(self, 
                     msa_ag, 
                    qlevel,
                    selevel,
                    nlevel,
                    plevel,
                    outdict,
                    key):        
        
        for k, v in outdict[key].items():
            outdict[key][k]  = [[],[]] # 0 for abs value, 0 for class
            # It seems that the rasters do not like 0s.
            # Before appending, add some code to make it really small
            # but not zero.            
            # Define classes here
            for qlid in range(1, len(qlevel)):
#                print(msa_ag[(k, "Q")])
                if self.checkinbetween(msa_ag[(k, "Q")], 
                                       qlevel[qlid-1],
                                       qlevel[qlid]):
                    outdict[key][k][0].append(msa_ag[(k, "Q")])
                    outdict[key][k][1].append(str(qlid))
                    break

            for selid in range(1, len(selevel)):
                if self.checkinbetween(msa_ag[(k, "MUSL")], 
                                       selevel[selid-1],
                                       selevel[selid]):
                    outdict[key][k][0].append(msa_ag[(k, "MUSL")])
                    outdict[key][k][1].append(str(selid))
                    break          

            for tnlid in range(1, len(nlevel)):
                if self.checkinbetween(msa_ag[(k, "TN")],
                                       nlevel[tnlid-1],
                                       nlevel[tnlid]):
                    outdict[key][k][0].append(msa_ag[(k, "TN")])
                    outdict[key][k][1].append(str(tnlid))
                    break 
 
            for tplid in range(1, len(plevel)):
                if self.checkinbetween(msa_ag[(k, "TP")],
                                       plevel[tplid-1],
                                       plevel[tplid]):
                    outdict[key][k][0].append(msa_ag[(k, "TP")])
                    outdict[key][k][1].append(str(tplid))
                    break  
      
        return outdict

              
        
    def MSA2wssubrsnp(self, fdapexrun,
                wssubflddict2, 
                allscenario): 

        allsce_msadf = {} 
        # maximum values of q, s, n and p
        qsnpmax = [0,0,0,0]

        for ks, vs in allscenario.items():
            if not "full" in ks:
                allsce_msadf[ks] = {}
                # Loop through the watersheds and read in asa
                for key, value in wssubflddict2.items():
                    fnmsarun = ''
                    fnmsarun = '%s/sce%s/RSUB%s_%s.MSA' %(fdapexrun,
                                                    vs,
                                                    key, 
                                                    vs)
                    print('processing ', fnmsarun)
                    msakey = None
                    qnspmax, msakey = self.readmsa2dflevel(
                            fnmsarun,
                            qsnpmax)
                    
                    allsce_msadf[ks][key] = msakey
            
        return allsce_msadf, qsnpmax
                
        
    def readmsa2dflevel(self, fnmsarun,
                            qsnpmax):        
        fid = open(fnmsarun, 'r')
        lif = fid.readlines()
        fid.close()
        
        # The first 10 lines are heads and we do not need it
        del(lif[0:10])
        
        # Separate the lines in each line and then put it into
        # a dataframe
        # create a dataframe to store the info.
        dfmsa = None
        
        for lidx in range(len(lif)):
            lif[lidx] = lif[lidx].split(' ')
            while '' in lif[lidx]:
                lif[lidx].remove('')
            lif[lidx][-1] = lif[lidx][-1][:-1]
            
            for idx2 in range(5, len(lif[lidx])-1):
                if math.isnan(float(lif[lidx][idx2])):
                    lif[lidx][idx2] = 0.00
                else:
                    lif[lidx][idx2] = float(lif[lidx][idx2])
            
        labels = ["OrderNO", "SubNO", "YearXXXX",
                  "YearOrder", "OutVar", "Jan",
                  "Feb", "Mar", "Apr", "May",
                  "June", "July", "Aug",  "Sept",
                  "Oct", "Nov", "Dec", "YearSum", "OutVar2"]
        
        dfmsa = pd.DataFrame.from_records(
                lif,
                columns=labels) 

#        self.avgSlpLen = self.dfasc[['subno', 'plen']].groupby('subno')['plen'].mean().to_dict()
        dict_subvarannavg = None
        dict_subvarannavg = dfmsa[['SubNO', 'OutVar',"YearSum"
            ]].groupby(['SubNO','OutVar'])['YearSum'].mean().to_dict()
        
        # Now we get the average annual values for each variable
        # at each subarea. But we need to add two more, the total nitrogen
        # and total phosphorus
        # Current variables include
        # [PRCP, Q, QDR,MUSL, QN, YN, SSFN, PRKN, QDRN
        #  YP, QP, QDRP, QRFN, MNP, YPM, YPO]
        # Loop through the subareas
        subnolst = None
        subnolst = dfmsa['SubNO'].unique()
                
        for subid in subnolst:
            dict_subvarannavg[(subid,"TN")] = [
                    dict_subvarannavg[(subid,"QN")]
                + dict_subvarannavg[(subid,"YN")]
                + dict_subvarannavg[(subid,"SSFN")]
                + dict_subvarannavg[(subid,"PRKN")]
                + dict_subvarannavg[(subid,"QDRN")]][0]
            dict_subvarannavg[(subid,"TP")] = [
                    dict_subvarannavg[(subid,"YP")]
                + dict_subvarannavg[(subid,"QP")]
                + dict_subvarannavg[(subid,"SSFN")]
                + dict_subvarannavg[(subid,"PRKN")]
                + dict_subvarannavg[(subid,"QDRN")]][0]
           
            if (dict_subvarannavg[(subid,"TN")] > 50.0):
                dict_subvarannavg[(subid,"TN")] = 49.0
            if (dict_subvarannavg[(subid,"TP")] > 10.0):
                dict_subvarannavg[(subid,"TP")] = 9.0
            # Get compare to the values to get max
            if (dict_subvarannavg[(subid,"Q")] > qsnpmax[0]):
                qsnpmax[0] = dict_subvarannavg[(subid,"Q")]
            if (dict_subvarannavg[(subid,"MUSL")] > qsnpmax[1]):
                qsnpmax[1] = dict_subvarannavg[(subid,"MUSL")]
            if (dict_subvarannavg[(subid,"TN")] > qsnpmax[2]):
                qsnpmax[2] = dict_subvarannavg[(subid,"TN")]
            if (dict_subvarannavg[(subid,"TP")] > qsnpmax[3]):
                qsnpmax[3] = dict_subvarannavg[(subid,"TP")]
              
        return qsnpmax, dict_subvarannavg
        

        
        
        
        
        
        
    def getAssignLevels(self,
                        allsce_susdf,
                        qsnpmax,
                        suslegendlevel,
                        wssubflddict2
                        ):
                
        # Generate levels based on max values:
        qlevel = self.generateLevels(float(qsnpmax[0]), 10)
        selevel = self.generateLevels(float(qsnpmax[1]), 10)
        nlevel = self.generateLevels(float(qsnpmax[2]), 10)
        plevel = self.generateLevels(float(qsnpmax[3]), 10)
        
        # Modify the suslegendlevel json based on new values
        newlevel = copy.deepcopy(suslegendlevel)
        for lidx in range(10):
            levkey = "level%i" %(lidx+1)
            newlevel['runoff'][levkey]["min"] = qlevel[lidx]
            newlevel['runoff'][levkey]["max"] = qlevel[lidx+1]
            newlevel['soilerosion'][levkey]["min"] = selevel[lidx]
            newlevel['soilerosion'][levkey]["max"] = selevel[lidx+1]
            newlevel['nitrogen'][levkey]["min"] = nlevel[lidx]
            newlevel['nitrogen'][levkey]["max"] = nlevel[lidx+1]
            newlevel['phosphorus'][levkey]["min"] = plevel[lidx]
            newlevel['phosphorus'][levkey]["max"] = plevel[lidx+1]
            
    
        
        allsceoutdict = {}
        
        # Modify the results to generate a dictionary 
        for ks, vs in allsce_susdf.items():

            allsceoutdict[ks] = {}
            susrltdict = None
            susrltdict = copy.deepcopy(wssubflddict2)
            # Loop through the watersheds and read in asa
            for key, value in wssubflddict2.items():
                
                allsceoutdict[ks] = self.assignLevels(
                            allsce_susdf[ks][key], 
                            qlevel,
                            selevel,
                            nlevel,
                            plevel,
                            susrltdict,
                            key)
                
        return allsceoutdict, newlevel
        
        
    def assignLevels(self, 
                     sus_ag, 
                    qlevel,
                    selevel,
                    nlevel,
                    plevel,
                    outdict,
                    key):        
        
        for k, v in outdict[key].items():
            outdict[key][k]  = [[],[]] # 0 for abs value, 0 for class
            # It seems that the rasters do not like 0s.
            # Before appending, add some code to make it really small
            # but not zero.            
            # Define classes here
            for qlid in range(1, len(qlevel)):
                if self.checkinbetween(sus_ag['Q'][k], 
                                       qlevel[qlid-1],
                                       qlevel[qlid]):
                    outdict[key][k][0].append(sus_ag['Q'][k])
                    outdict[key][k][1].append(str(qlid))
                    break

            for selid in range(1, len(selevel)):
                if self.checkinbetween(sus_ag['RUS2'][k], 
                                       selevel[selid-1],
                                       selevel[selid]):
                    outdict[key][k][0].append(sus_ag['RUS2'][k])
                    outdict[key][k][1].append(str(selid))
                    break          

            for tnlid in range(1, len(nlevel)):
                if self.checkinbetween(sus_ag['TN'][k],
                                       nlevel[tnlid-1],
                                       nlevel[tnlid]):
                    outdict[key][k][0].append(sus_ag['TN'][k])
                    outdict[key][k][1].append(str(tnlid))
                    break 
              
 
            for tplid in range(1, len(plevel)):
                if self.checkinbetween(sus_ag['TP'][k],
                                       plevel[tplid-1],
                                       plevel[tplid]):
                    outdict[key][k][0].append(sus_ag['TP'][k])
                    outdict[key][k][1].append(str(tplid))
                    break  
        
        
        
        return outdict
        
        
        
    def readJSON(self, fn_json):

        inf_usrjson = {}
        
        with open(fn_json) as json_file:    
            inf_usrjson = json.loads(json_file.read())
        #pprint.pprint(inf_usrjson)
        json_file.close()
        
        return inf_usrjson
                  
        
    def sus2wssubrsnp(self,
                      fdapexrun,
                      wssubflddict2,
                      allscenario
                      ):

        allsce_susdf = {} 
        # maximum values of q, s, n and p
        qsnpmax = [0,0,0,0]

        for ks, vs in allscenario.items():
            if not "full" in ks:
                allsce_susdf[ks] = {}
                # Loop through the watersheds and read in asa
                for key, value in wssubflddict2.items():
                    fnsusrun = ''
                    fnsusrun = '%s/RSUB%s_%s.SUS' %(fdapexrun,
                                                    key, 
                                                    vs)
                    print('processing ', fnsusrun)
                    suskey = None
                    qnspmax, suskey = self.readsus2dflevel(
                            fnsusrun,
                            qsnpmax)
                    
                    allsce_susdf[ks][key] = suskey
            
        return allsce_susdf, qsnpmax
        
        
    
    
    def readsus2dflevel(self, 
                fnsusrun,
                qsnpmax): 
        
        fid = open(fnsusrun, 'r')
        lif = fid.readlines()
        fid.close()
        
        del(lif[0:8])
        
        labels = lif[0].split(' ')
        while '' in labels:
            labels.remove('')
        labels[-1] = labels[-1][:-1]
    
        del(lif[0])
    
        # LIF contains non extra lines, I will create
        # a new list to store only value lines, which
        # will be used to create the datafreame
        lisus = []
    
    
        for idx in range(1, len(lif), 2):
            lif[idx] = lif[idx].split(' ')
            while '' in lif[idx]:
                lif[idx].remove('')
            lif[idx][-1] = lif[idx][-1][:-1]
            
            for idx2 in range(3, len(lif[idx])):
                if math.isnan(float(lif[idx][idx2])):
                    lif[idx][idx2] = 0.00
                else:
                    lif[idx][idx2] = float(lif[idx][idx2])
                    
                    # If the number is less than 0.01, I will
                    # set it to 0.01. The reason is we timed 
                    # the results by 1000. Then the minimum
                    # will be 10. Gdal does not like 0s. So,
                    # All 0s will be modified as 0.01.
                    if lif[idx][idx2] < 0.01: 
                        lif[idx][idx2] = 0.01
                        
            lisus.append(lif[idx])
    
        # The 0 element of lif is the label
        dfsus = pd.DataFrame.from_records(
                lisus,
                columns=labels) 
        
        # Add total nitrogen and total phpsphorus
        dfsus['TN'] = dfsus['YN'] + dfsus['QN']+ dfsus['SSFN'] + dfsus['PRKN']+ dfsus['QDRN'] + dfsus['QRFN']
                    
        dfsus['TP'] = dfsus['YPM'] + dfsus['YPO']+ dfsus['QDRP']

        
        
        # annual average: 
        sus_ag = {}
        sus_ag = dfsus[['ID', 'PRCP', 'ET', 
                'Q', 'SSF', 'RSSF', 'QDR','WYLD',
                'MUSL','RUS2', 
                'YN', 'QN', 'SSFN', 'PRKN', 'QDRN', 'QRFN', 'TN',
                'YPM', 'YPO', 'QDRP', 'TP']].groupby(['ID']).mean()
        
        # For the purpose of displaying the map to a better symbology, 
        # I will divide the results into 10 classes based on specific results.
        qtmax = dfsus['Q'].max()
#        qtmin = dfasa['Q'].min()
        setmax = dfsus['RUS2'].max()
#        setmin = dfasa['RUS2'].min()
        tntmax = dfsus['TN'].max()
#        tntmin = dfasa['TN'].min()
        tptmax = dfsus['TP'].max()
#        tptmin = dfasa['TP'].min()
        
        if qsnpmax[0] < qtmax:
            qsnpmax[0] = qtmax
        if qsnpmax[1] < setmax:
            qsnpmax[1] = setmax
        if qsnpmax[2] < tntmax:
            qsnpmax[2] = tntmax
        if qsnpmax[3] < tptmax:
            qsnpmax[3] = tptmax        
        
        return qsnpmax, sus_ag

        
        
#        
#        
#        # Generate new reclass levels
#        qlevel = self.generateLevels(qtmax, 10)
#        selevel = self.generateLevels(setmax, 10)
#        nlevel = self.generateLevels(tntmax, 10)
#        plevel = self.generateLevels(tptmax, 10)
#        
#        
#        
#        
#        
    


    def generateLevels(self, valmax, binnos):
    
        bins = [0]*11 # start from 0
        
        intervals = (valmax+0.01)/binnos
        
        for idx in range(1, binnos+1):
            bins[idx] = bins[idx-1] + intervals
                    
        return bins
            
        
        
    
    def checkinbetween(self, val, valmin, valmax):
        
        if ((val >= valmin) and (val < valmax)):
            return True
        else:
            return False
    
        
        
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
# Write the information into a json file
if os.path.isfile(fnout_aaqsnpclassabs):
    os.remove(fnout_aaqsnpclassabs)
if os.path.isfile(fout_legendlevels):
    os.remove(fout_legendlevels)
    
with open(fnout_aaqsnpclassabs, 'w') as outfile:
    json.dump(MainClass.wssubrsnp, outfile)
with open(fout_legendlevels, 'w') as outfile1:
    json.dump(MainClass.msalegendlevelnew, outfile1)


print("--- %s seconds ---" % (time.time() - start_time))

#######################################################


