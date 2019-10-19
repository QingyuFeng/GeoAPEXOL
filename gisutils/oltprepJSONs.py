#!/usr/bin/python3
# -*- coding: utf-8 -*-
"""
Created on Nov, 2018

This script was developed to read the grid files and
calculate values for watersheds and subareas.
This will be done for each watersheds.

Values of variables to be determined include:
	1. Watershed ID
	2. Subarea ID
	3. Watershed area
	4. Latitude
	5. Longitude
	6. Average upland slope
	7. Average upland slope length
	8. Manning N
	9. Channel slope
	10. Channel length
	11. Channel manning n
	12. Reach length
	13. Reach slope
	14. Land use number
	15. Soil number
	16. Crop number
	17. Elevation

Thus, the input layers required are:
1. demw.asc: for watershed id and subarea id
2. sd8Slope.asc: for slope
3. plen.asc and streamNet.asc: for average slope length
4. manning n table
5. landuse number table: for each crop (need to be created)
6. stream.shp: for channel slope, and length
7. channal manning n table
8. soilws.asc: for soil number
9. nassws.asc: for crop number
10. json file showing the input and output
11. dem.asc: need to clip the dem to ws area

The program was designed to run fast. Only one loop and
try to process with matrix, probably dataframe

The output will be a json file showing the variable of
all subareas.

Datastructure:
watershed, subareaid, all variables.

In this program, the subarea land use number and soil number
will be the dominant combination of soil-landuse-slope.
This can be modified later if needed.


@author: qyfen
"""

#######################################################
# Environment setting
#######################################################
import sys,os
import time
import json
from osgeo import ogr
import numpy as np
import pandas as pd
import copy


#######################################################
# Input and output file defining
#######################################################

fddata = sys.argv[1]
fin_nassmgtupn = sys.argv[2]
fin_chn = sys.argv[3]
utmfd =  sys.argv[4]
scenariofdname = sys.argv[5]


from utm.conversion import to_latlon, from_latlon, latlon_to_zone_number, latitude_to_zone_letter
from utm.error import OutOfRangeError


#fddata = 'devolt'

#fin_nassmgtupn = os.path.join(
#    'table_nassmgtupn.csv'
#    )

#fin_chn = os.path.join(
#    'table_chn.txt'
#    )


fin_elev = os.path.join(
    fddata,
    'gislayers',
    'elev.asc'
    )


fin_tree = os.path.join(
    fddata,
    'taudemlayers',
    "tree.txt"
    )


fin_demw = os.path.join(
    fddata,
    'taudemlayers',
    'demw.asc'
    )

fin_slp = os.path.join(
    fddata,
    'taudemlayers',
    'sd8Slope.asc'
    )

fin_plen = os.path.join(
    fddata,
    'taudemlayers',
    'plen.asc'
    )

fin_streamshp = os.path.join(
    fddata,
    'taudemlayers',
    'stream.shp'
    )

fin_streamasc = os.path.join(
    fddata,
    'taudemlayers',
    'streamnetolt.asc'
    )

fin_soil = os.path.join(
    fddata,
    'soils',
    'soil.asc'
    )

fin_lu = os.path.join(
    fddata,
    'landuse',
    'nass.asc'
    )

fin_wssubjson = os.path.join(
    fddata,
    'apexruns',
    scenariofdname,
    'var1wssub.json'
    )

fin_wsshp = os.path.join(
    fddata,
    'taudemlayers',
    'esridemw',
    'out.shp'
    )


fin_demwreclasspair = os.path.join(
    fddata,
    'taudemlayers',
    'reclademw',
    'demwreclasspair.json'
    )


fin_tmpsitjson = os.path.join(
    fddata,
    'apexruns',
    scenariofdname,
    'tmpsitefile.json'
    ) 


fout_wssubvarjson = os.path.join(
    fddata,
    'apexruns',
    scenariofdname,
    'runsub.json'
    )

fout_wssubsollujson = os.path.join(
    fddata,
    'apexruns',
    scenariofdname,
    'wssubsollulatlon.json'
    )

fout_sitejson = os.path.join(
    fddata,
    'apexruns',
    scenariofdname,
    'runsite.json'
    )
#######################################################
# Defining classes
#######################################################
class GetSubInfo():
    def __init__(self):
        """Constructor."""

        # Slope group to be modified. Included here for
        # Future reference
        self.slopeGroup = [0, 2, 5, 9999]

        # A dictionary to store the ws_subno, soil, landuse,
        # latitude, longitude. The ws_subno will be the order
        # number of the list in soil, management, and daily weather
        # station.
        self.wsSubSolOpsLatLon = {}


        # wssubflddict: A dictionary to store the lines
        # in the wssubfld. Each line contains the subareas
        # in one watershed covered by the field.
        self.wssubflddict = self.readWsSubnos(fin_demwreclasspair)
        
        # For the purpose of matching demw and new reclassif
        # sub nos, the dict is processed to a new format 
        # with nested dict: ws_key1, subdemw_key2:subrecno_value.
        self.wssubflddict2 = self.modifywssubdict(
                self.wssubflddict) 



        # Read in asc data for later processing
        self.dtsubno = self.readASCstr(fin_demw)
        self.dtslp = self.readASCfloat(fin_slp)
        self.dtplen = self.readASCfloat(fin_plen)
        self.dtsol = self.readASCstr(fin_soil)
        self.dtlu = self.readASCstr(fin_lu)
        self.dtstrmasc = self.readASCstr(fin_streamasc)
        self.dtelev = self.readASCfloat(fin_elev)

        ## Create Pandas dataframe to store all asc data
        self.dfasc = pd.DataFrame(self.dtsubno[3],
                                  columns=['subno'])

        self.dfasc['slope'] = self.dtslp[3]
        self.dfasc['plen'] = self.dtplen[3]
        self.dfasc['soilid'] = self.dtsol[3]
        self.dfasc['luid'] = self.dtlu[3]
        self.dfasc['strm01'] = self.dtstrmasc[3] 
        self.dfasc['elev'] = self.dtelev[3]

        ## Start processing the dataframe for various variables
        ## Subarea no
        # This might not be used. Keep it commented.
        # 1. Delete rows where subarea are nodata:
        #     This will reduce the processing time.
        self.dfasc = self.dfasc.drop(self.dfasc[
                self.dfasc.subno == self.dtsubno[2]].index)

        self.uniqsubno = self.dfasc['subno'].unique()
                
        # Stream routing orders
        ## Stores the order of subareas. This will
        ## serve as the first line of each subarea in the .sub
        ## file, starting with an extreme and flow to 
        ## the outlet of the watershed.

        # Get the stream attribute information
        ## Stream attributes
        # The attritube table from the stream.shp.
        # This will privide channel length, channel slope,
        # and other useful information
        self.strmAtt = self.readSHP(fin_streamshp)

        # treedict: a dictionary storing the connection among
        # different subareas, including downstream, upstream,
        # stream order, etc.
        self.treedict = self.readsubtree(
                    fin_tree)
        
        # Either the streamAtt or the tree file has some problem
        # of having numbers that does not exist in the demw.
        # All the attributes of subareas area processed using
        # stream number in the demw file. So, I need to process
        # the missing numbers in demw but existing in streamAtt
        # or tree file. 
        self.treedict2 = self.rmExtraStrm(self.uniqsubno, 
                                         self.treedict)
        
        ## Outlet and watershed graph are the input for the
        ## dfs_recursive function
        ## Outlet of the watershed: identified by getting the stream
        ## number, whose downstream is -1
        self.outletStrNo = [k for k,v in self.strmAtt.items() if v[1]=='-1'][0]
        ## Representing watershed in Graph format:
        ## basically a dictionary: wsGraph = {streamNo: [neighbour1, nb2]}
        
        self.watershedGraph = self.graphForWS(self.treedict2)
        ## self.subRouting: a list contains the minus values
        ## when the area of the subarea need to be minus in apex
        ## sub file.

        #print('graph', self.watershedGraph)
        
        self.subRouting = []
        ## self.subPurePath: a list contains the routing path
        ## of the subareas. This was included because the strmAtt
        ## is a dictionary with positive keys. 
        self.subPurePath = []
        self.subPurePath, self.subRouting = self.dfs_iterative(
                                    self.watershedGraph,
                                    self.outletStrNo)

        # Add a slopeGroup For processing
        self.dfasc['slopeGroup'] = self.dfasc['slope'].apply(
                                        self.getslopeIndex)
        
        # In order to remove the potential problems of having
        # to simulate water, I will need to remove those land
        # uses that are water.
        self.waterlus = map(str, [0,81,83,87,92,111,112,190,195])
        # ~ turn True to False
        #self.dfasc = self.dfasc[~self.dfasc['luid'].isin(self.waterlus)]
       
        # Deal with the 0 values in soil and land use
#        print(self.dfasc['soilid'].value_counts().idxmax())
#        print(self.dfasc['luid'].value_counts().idxmax())
#        print(self.dfasc['slope'].value_counts().idxmax())
        # Mocify the 0 values or water to the most frequent values in
        # soil
        # 1. Get unique values of the soil list
        self.uniqsoils = self.dfasc['soilid'].unique()

        if ("0" in self.uniqsoils):
            # Find the most frequent soil ids in the soil list(This should
            # not be 0)
            self.mostsoilid = self.dfasc['soilid'].value_counts().idxmax()
            if (self.mostsoilid == '0') or (self.mostsoilid == '1'):    
                self.mostsoilid = "164331"
            # Create an np array to be added in the pandas to replace the 0s
            # with the most soil id
            self.mostsoilarray = np.array([self.mostsoilid] * len(self.dfasc[self.dfasc['soilid'] == '0']))

            # Modify
            self.dfasc.loc[self.dfasc['soilid'] == '0', "soilid"] = self.mostsoilarray

        # Deal with land use
        # If there is water or other unwanted land use
        # Mocify the 0 values or water to the most frequent values in
        # landuse
        # 1. Get unique values of the soil list
        self.uniqlus = self.dfasc['luid'].unique()
        for luuid in self.uniqlus:
            if (luuid in self.waterlus):
#                print(len(self.dfasc[self.dfasc['luid'] == luuid]))
                # Find the most frequent soil ids in the soil list(This should
                # not be 0)
                self.mostluid = self.dfasc['luid'].value_counts().idxmax()

                if self.mostluid in self.waterlus:
                    self.mostluid = "1"
                # Create an np array to be added in the pandas to replace the 0s
                # with the most soil id
                self.mostluarray = np.array([self.mostluid] * len(self.dfasc[self.dfasc['luid'] == luuid]))

#                print(self.dfasc.loc[self.dfasc['luid'] == luuid, "luid"])

                # Modify
                self.dfasc.loc[self.dfasc['luid'] == luuid, "luid"] = self.mostluarray


        # Modify for the scenarios
        self.scenarioname = None
        self.scenariono = None
        self.scenarioname = scenariofdname[3:]
         
        if (self.scenarioname == "fallow"):
            self.scenariono = "65"
            # Create an array of the length of the dataframe 
            # and all lu numbers were changed to the scenario no
            self.modscenluarray = np.array([self.scenariono] * len(
                self.dfasc))
            # Modify the lu numbers in the dataframe to match
            # the intended scenario
            self.dfasc['luid'] = self.modscenluarray
        elif (self.scenarioname == "trees"):
            self.scenariono = "141"
            # Create an array of the length of the dataframe
            # and all lu numbers were changed to the scenario no
            self.modscenluarray = np.array([self.scenariono] * len(
                self.dfasc))
            # Modify the lu numbers in the dataframe to match
            # the intended scenario
            self.dfasc['luid'] = self.modscenluarray
        elif (self.scenarioname == "peregrass"):
            self.scenariono = "176"
            # Create an array of the length of the dataframe
            # and all lu numbers were changed to the scenario no
            self.modscenluarray = np.array([self.scenariono] * len(
                self.dfasc))
            # Modify the lu numbers in the dataframe to match
            # the intended scenario
            self.dfasc['luid'] = self.modscenluarray


        # Generate CropSoilSlopeNumber
        self.dfasc['subCropSoilSlope'] = self.dfasc.apply(
                                        self.addCropSoilSlope,
                                        axis=1)
        

        # Calculate average dem/elevation for the subarea
        self.avgElev = self.dfasc.groupby('subno')['elev'].mean().to_dict()

        ## Processing subarea information
        # Recording the apperance of each combination of crop, soil
        # slope at each subarea
        # Count appearances of each combination for all subareas
        self.subCSSCounts = self.dfasc['subCropSoilSlope'].value_counts()

        # Here we used the major combination of slope, soil, landuse
        # (nass) combination for determining subareas
        # The subCSSCounts is a series. We would like to process it to
        # a dataframe and then processing to get the combination
        # in a subarea with the maximum area
        self.subCSSCountsMax = self.subCSSCounts.to_frame()
        self.subCSSCountsMax['comb'] = self.subCSSCountsMax.index
        # Break the combination to list to add new columns on that.
        self.subCSSCountsMax['comblst'] = self.subCSSCountsMax.apply(
                                self.breakCSSComb,
                                axis=1)

        self.subCSSCountsMax['subno'] = self.subCSSCountsMax.apply(
                                self.assignSub,
                                axis=1)
        # Get the max combination for each subarea
        self.subCSSMaxDict = self.getCSSComb(self.subCSSCountsMax)
        #print(self.subCSSMaxDict)
        
        ## Subarea areas
        self.cellsize = float(self.dtsubno[4])
        # Count appearances of each subarea no to get the subarea areas
        self.subareaArea = self.dfasc['subno'].value_counts().to_dict()

        ## Average upland slope
        # this is the average of slopes for all grids
        # Calculate average slope of each subarea
        self.avgSlope = self.dfasc.groupby('subno')['slope'].mean().to_dict()
        
        ## Average upland slopelength:
        # This is calculated as the average of flowpath length
        # from the plen file (results of gridnet)
        # This value may need to be updated later. The method
        # to calculate it needs further exploration
#        self.avgSlpLen = self.dfasc[self.dfasc['strm01'] == '0'  \
#                ][['subno', 'plen']].groupby('subno')['plen'].mean().to_dict()
        self.avgSlpLen = self.dfasc[['subno', 'plen']].groupby('subno')['plen'].mean().to_dict()
        ## Subarea Centroid
        # The latitude and longitude values for the
        # centroids of each subarea.
        self.subLatLong = self.getCentroid(fin_wsshp)
        #print(self.subLatLong)
        # Land use number manning N and management options
        self.nassLuMgtUpn = self.readCSVtoDict(fin_nassmgtupn)

        # Channel manning N
        self.channelManN = self.readChMnTb(fin_chn)

        ## Channel length:
        # Channel length is the length from the subarea outlet to
        # the most distant point. We have P len.
        # If the subarea is an extreme subarea, channel length is the
        # maximum plen value for all channel cells. 
        # If the subarea is an routing subarea, channel length need to be
        # larger than reach length. It will be the reach length + the maximum plen.
        # for non channel area.
        # self.channenLen: maximum plen for channel

        self.rchchannenLen = self.getrchChannelLen(self.strmAtt)
        self.channenLen = self.dfasc[self.dfasc['strm01'] == '1'  \
                ][['subno', 'plen']].groupby('subno')['plen'].max().to_dict()
        
        

    def getrchChannelLen(self, strmshpatt):
        
        channellen = {}
        for k, v in strmshpatt.items():
            channellen[k] = float(v[6])
            
        return channellen
                    
        
        
    
    def modifywssubdict(self, wssubflddict):
        
        wsdict2 = copy.deepcopy(wssubflddict)
        
        for key, value in wssubflddict.items():
            wsdict2[key] = {}
            for vidx in range(len(value[0])):
                #print(wsdict2[key][0][vidx])
                wsdict2[key][value[0][vidx]] = value[1][vidx]

        
        return wsdict2
                
            
    
    def readJSON(self, fn_json):

        inf_usrjson = {}
        
        with open(fn_json) as json_file:    
            inf_usrjson = json.loads(json_file.read())
        #pprint.pprint(inf_usrjson)
        json_file.close()
        
        return inf_usrjson


    def readWsSubnos(self, fn_json):

        inf_usrjson = {}
        
        with open(fn_json) as json_file:    
            inf_usrjson = json.loads(json_file.read())
        #pprint.pprint(inf_usrjson)
        json_file.close()
        
        return inf_usrjson

    
    
    
    
    def rmExtraStrm(self, subdemwlst, substreamdict):
        '''
        This function removed the subareas from subnoinstream
        which do not exist in subnoindemw. 
        '''
        # substrm is the list of subnumbers in the tree file
        # substrmtorm is the stream number only exists in the 
        # tree file but not in the demw files (the raster map)
        substrm = substreamdict.keys()
        
        substrmtorm = [i for i in substrm
                       if not i in subdemwlst]
        
        # Here I just removed them by reconnecting the streams
#        print(substrmtorm)
        
        print(substrmtorm)
        
        for subid in range(len(substrmtorm)):
            
#            print("processing subarea: ", subid)
            # Get the upstream and downstrema of the value to be removed
            tempvalue = None
            tempvalue = substreamdict[substrmtorm[subid]]
#            print('temo...: ',subid, tempvalue)
            
            # The streams connected by this need to be modified
            # value of dict [sub, downstrm, upstream1, upstream2]
            # upstream1 = tempvalue[2]
            
            # Change this subarea's downstream's upstream to
            # this subarea's downstream
            # There are two upstreams.
            # Since we are removing, and the upstreams will have 
            # a downstream, which is the one to be removed. This downstream
            # will be changed to the to-be-removed stream's downstream 
            # to make the connection. 
            # index 0 is downstream
           

            if (substreamdict[tempvalue[1]][2] == substrmtorm[subid]):
#                print('downstream upstream 1: ',substreamdict[tempvalue[1]])
                substreamdict[tempvalue[1]][2] = tempvalue[2]
#                print('downstream upstream 1 later: ',substreamdict[tempvalue[1]])
            elif (substreamdict[tempvalue[1]][3] == substrmtorm[subid]):
#                print('downstream upstream 2: ',substreamdict[tempvalue[1]])
                substreamdict[tempvalue[1]][3] = tempvalue[2]
#                print('downstream upstream 2 later: ',substreamdict[tempvalue[1]])

#            print('upstream2...: ',tempvalue[2],substreamdict[tempvalue[2]])
            substreamdict[tempvalue[2]][1] = tempvalue[1]
#            print('upstream2 lat: ', tempvalue[2],substreamdict[tempvalue[2]])

#            print('upstream2...: ',tempvalue[3],substreamdict[tempvalue[3]])
            substreamdict[tempvalue[3]][1] = tempvalue[1]
#            print('upstream2 lat: ', tempvalue[3], substreamdict[tempvalue[3]])

            # First remove keys in dict
            substreamdict.pop(substrmtorm[subid], None)
            # First remove keys in dict
            substreamdict.pop(subid, None)

        return substreamdict    






    def dfs_iterative(self, graph, start):
        stack, path, pathminus = [start], [], []

        # Stack as the starting point
        while stack:
            # Signed vertex: for path routing
            signedvertex = stack.pop()
            # remove sign for looping
            vertex = str(abs(int(signedvertex)))
            # Mark vertex as visited.
            if vertex in path:
                continue
            # If not visited, append it.
            path.append(vertex)
            pathminus.append(signedvertex)

            for nbid in range(len(graph[vertex])):
                if nbid > 0:
                    neighbor = '-%s' %(graph[vertex][nbid])
                else:
                    neighbor = graph[vertex][nbid]
                    
                stack.append(neighbor)

        return path, pathminus


        
    # Read in the tree file
    def readsubtree(self, fntree):

        fid = open(fntree, 'r')
        lif = fid.readlines()
        fid.close

        treedict = {}
        
        for lidx in range(len(lif)):
            lif[lidx] = lif[lidx].split('\t')
            while '' in lif[lidx]:
                lif[lidx].remove('')
                
            lif[lidx][-1] = lif[lidx][-1][:-1]
            # 3: downstream
            # 4: 1st upstream
            # 5: 2nd upstream
            # 0: this stream
            
            treedict[lif[lidx][0]] = [lif[lidx][0]]+lif[lidx][3:]

        return treedict  





    def getCSSComb(self, df):

        '''
        Add Get the maximum combination for each subarea
        '''
        outdict = {}

        subNos = df['subno'].unique()

        for sid in subNos:

            dftemp = 0
            dftemp = df[df['subno'] == sid]
            #.groupby('comb')['subCropSoilSlope'].max()
            dftemp = dftemp[dftemp['subCropSoilSlope']==dftemp['subCropSoilSlope'].max()]

            outdict[int(sid)] = dftemp.index[0].split('_')
        
        return outdict


    def breakCSSComb(self, df):

        '''
        Add one column of combination of subarea no, crop/landuse, soil,
        and slopeGroup
        '''
        cropSoilSlope = df['comb'].split('_')
        
        return cropSoilSlope


    def assignSub(self, df):

        '''
        Add one column of combination of subarea no, crop/landuse, soil,
        and slopeGroup
        '''
        subno = df['comblst'][0]
        
        return subno





    def readChMnTb(self, fintable):
        '''
        Read the table containing manning N for channels
        with different conditions.

        fintable: path of the file.
        It is a text file with the manning n for different conditions.

        The table cantains complete information for determining
        channel manning N.
        Here the Earth will be used as default. The inclusion
        of this table will further assist simulation of 
        channel erosion.
        '''

        fid = open(fintable, 'r')
        lif = fid.readlines()
        fid.close()

        for idx in range(len(lif)):
            lif[idx] = lif[idx].split(',')

        return lif



    def readCSVtoDict(self, finCSV):
        '''
        Use to read the fin_nassmgtupn table.
        This table contains information for nass value, and
        the following columns.
        Term definition:
        #Group: the group of the crop for better sorting.
        #APEXMGT: the name of the OPS files that will be used
        to simulate the tillage management. This can be updated
        later for more detailed control.
        #LUNOCV: land use no for curve number value determination.
        This was determined based on the land use table of the
        apex user manual 1501. For most crops, I will use the
        straight poor as default, which is the first one.
        This can be refined later.
        UPN: the upland manning's N number. I will use the
        conventional tillage residue as default value.

        The output of this function will return a dictionary
        with NASSvalue as keys since we have this from land use.
        Then LUNO and UPN can be determined.

        Columnnames:
        
        '0NASSValue',
        '1CropName', '2Group', '3APEXMGT', '4APEXMGTNO',
        '5LUNOCV_Straight_Poor', '6LUNOCV_Straight_Good',
        '7LUNOCV_Contoured_Poor', '8LUNOCV_Contoured_Good',
        '9LUNOCV_ContouredTerraced_Poor',
        '10LUNOCV_ContouredTerrace_Good',
        '11UPN_Conventional_Tillage_No_Residue',
        '12UPN_Conventional_Tillage_Residue',
        '13UPN_Chisel_Plow_No_Residue',
        '14UPN_Chisel_Plow_Residue',
        '15UPN_Fall_Disking_Residue',
        '16UPN_No_Till_No_Residue',
        '17UPN_No_Till_With_Residue_0to1ton',
        '18UPN_No_Till_With_Residue_2to9ton'
        '''

        import csv

        columnname = []
        values = []
        outdict = {}
        
        with open(finCSV) as csv_file:
            csv_reader = csv.reader(csv_file, delimiter=',')
            line_count = 0
            for row in csv_reader:
                if line_count == 0:
                    columnname = row
                    line_count += 1
                else:
                    values.append(row)
                    line_count += 1
                    
        for vid in values:
            outdict[vid[0]] = vid
        
        return outdict




    def readASCfloat(self, finasc):

        # Store data into a list
        data = []
        
        # Reading files to a list 

        with open(finasc, 'r') as f:
            lif = f.read().splitlines()

        # The file some times contain wrong lines.
        # TODO: check whether the value rows are the same
        # The demws.asc for dem/elevation has one more row


        for lidx in range(len(lif)):
            lif[lidx] = lif[lidx].split(' ')
            while '' in lif[lidx]:
                lif[lidx].remove('')

            if lidx > 5:              
                lif[lidx] = list(map(float, lif[lidx]))
        # 0: ncols, 1: nrows, 5: NoDATA, 4: cellsize
        data.append(lif[0][1])
        data.append(lif[1][1])    
        data.append(lif[5][1])
        
        #print(lif[:4])
        del(lif[:7])
        del(lif[-1])

        data.append(lif)

        # Convert 2d asc array into 1d array
        data[3] = np.asarray(data[3]).ravel()
        #print(data[3].shape)

        return data


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
        data[3] = np.asarray(data[3]).ravel()
        #print(data[3].shape)

        # Cell size is needed for area calculation
        data.append(cellsize)

        return data



        
    def readSHP(self, finshp):
        '''
        Read shapefile and return all values in the
        attritube table.
        '''
        subStrAtt = dict()
        
        driver = ogr.GetDriverByName('ESRI Shapefile')

        dataSource = driver.Open(finshp, 0)
        layer = dataSource.GetLayer()

        # Get the field name
        '''
        According to TauDEM document for stream Reach
        and Watershed tool:
        Field names in this shapefile:
        ['0LINKNO', '1DSLINKNO', '2USLINKNO1',
        '3USLINKNO2', '4DSNODEID', '5strmOrder',
        '6Length', '7Magnitude', '8DSContArea',
        '9strmDrop', '10Slope', 'StraightL',
        'USContArea', 'WSNO', 'DOUTEND',
        'DOUTSTART', 'DOUTMID']
        * LINKNO: link number
        * DSLINKNO: downstream link, -1 indicates that this
        does not exist.
        * USLINKNO1: first upstream link
        * USLINKNO2: second upstream link
        * DSNODEID: node identifier for node at downstream
        end of each stream
        * strmOrder: strahler stream order
        * length: units are the horizontal map units of the
        underlying DEM grid
        * Magnitude: Shreve magnitude of the link. This is the
        total number of sources upstream
        * DSContArea: Drainage area at the downstream end of
        the link.
        * strmDrop: Drop in elevation from the start to the
        end of the link
        * Slope: Average slope of the link: drop/length
        * StraightL: Straight line distrance from the start
        to the end of the link
        * USContArea: Drainage area at the upstream end of
        the link
        * WSNO: Watershed NO:
        * DOUTEND: Distance to the eventual outlent from the
        downstream end of the link
        * DOUTMID: distance to the eventual outlet from the
        midpoint of the link.
        
        '''
        field_names = [field.name for field in layer.schema]

        # Get the value of each field for all layers
        for feature in layer:
            values_list = [str(feature.GetField(j)) for j in field_names]
            
            subStrAtt[str(values_list[0])] = values_list

        return subStrAtt
            

    def getCentroid(self, shapefile):
        '''
        Get the centroid of subarea. This was calculated from
        subarea shapefile
        '''
        subCentCoord = dict()

        driver = ogr.GetDriverByName("ESRI Shapefile")
        dataSource = driver.Open(shapefile, 0)
        layer = dataSource.GetLayer()

        field_names = [field.name for field in layer.schema]

        # Get the value of each field for all layers
        for feature in layer:
            # There is only one field 'DN' in the shapefile.
            values_list = [feature.GetField(j) for j in field_names]
            
            # Now we will write the centroid co-ordinates to a text file
            # Using 'with' will close the file when the loop ends-dont need to call close
            geom = feature.GetGeometryRef()
            centroid = str([geom.Centroid().ExportToWkt()])
            # Centroid now looks like '['POINT (499702.238761793 4477029.64828273)']'
            centroid = self.CentroidStr2Float(centroid)
            centroidUtm = to_latlon(centroid[0], centroid[1], 16, 'U')
            #print(centroidUtm)
            
            subCentCoord[values_list[0]] = centroidUtm

        return subCentCoord


    def CentroidStr2Float(self, centroidStr):

        newCentroid = ''.join((ch if ch in '0123456789.-e' else ' ') for ch in centroidStr)
        listOfNumbers = [float(i) for i in newCentroid.split()]
        
        return listOfNumbers
            


    def graphForWS(self, streamAtt):

        '''
        represent the watershed using a unreachable graph
        wsGraph = {node: [neighbors]}
        '''

        wsGraph = {}

        for key, value in streamAtt.items():
            wsGraph[key] = [k for k, v in streamAtt.items()
                            if v[1] == key]

        return wsGraph



    def dfs_recursive(self, graph, vertex, path=[], pathminus=[]):
        '''
        Using Depth first search algorithm to find the routing schemes
        Basic idea is:
        1. Represent the watershed connection in graphs. Using the function above.
        2. Starting from the root(outlet of the subarea), find all its neighbours.
        For each of the neighbour, of the first step, find all its upstreams. For
        each upstream, find the upstreams using the same function, until the very
        end of the branch. Then, come back for the second upstream, and repeat.
        3. At each level, append the visited path into a list, if it is not visited,
        go search, if visited (marked by not in), skip.
        May be not well explained, but it worked now, I will come back to provide
        better understanding. (TODO).

        to call the function:
        graph: graph representation of the watershed.
        vertex: start with the outlet.
        '''
        absvertex = str(abs(int(vertex)))
        
        path += [absvertex]
        
        pathminus += [vertex]
        
        for nbid in range(len(graph[absvertex])):
            neighbor = graph[absvertex][nbid]
            
            if neighbor not in path:
                if nbid > 0:
                    neighbor = '-%s' %(neighbor)
                path, pathminus = self.dfs_recursive(graph, neighbor, path, pathminus)

        return path, pathminus




    def getslopeIndex(self, slopePercent):

        '''
        slopePercent: slope value in asc is value, percent should
        time 100.
        '''
        n = len(self.slopeGroup)
        for index in range(n):
            if slopePercent*100 < self.slopeGroup[index]:
                return index
        return n

    def addCropSoilSlope(self, df):

        '''
        Add one column of combination of subarea no, crop/landuse, soil,
        and slopeGroup
        '''
        cropSoilSlope = '%s_%s_%s_%i' %(df['subno'],
                                     df['luid'],
                                     df['soilid'],
                                     df['slopeGroup'])
        
        return cropSoilSlope
        
        
    def addCSSCounts(self, subCSSComb):
        '''
        Add a column count the appearance for each combination of
        crop, soil, slopeGroup in each subarea
        '''
        
        return self.subCSSCounts[subCSSComb]
        
    def addsubArea(self, subno):
        '''
        Add a column count the appearance for each combination of
        crop, soil, slopeGroup in each subarea
        '''
        return self.subareaArea[subno]


class WatershedJSON():

    def __init__(self):

        ## Json file storing all necessary variables
        # After getting the information for routing, it is time
        # to process the information into the json files.
        self.WsJSON = None
        self.WsJSON = self.readJSON(fin_wssubjson)

        self.WsJSON, GetSubInfo.wsSubSolOpsLatLon = self.modifywsJSON(
            self.WsJSON, GetSubInfo.wsSubSolOpsLatLon)



    def readJSON(self, fn_json):

        inf_usrjson = {}
        
        with open(fn_json) as json_file:    
            inf_usrjson = json.loads(json_file.read())
        #pprint.pprint(inf_usrjson)
        json_file.close()
        
        return inf_usrjson



    def modifywsJSON(self, 
                       json,
                    wssubsolopslatlong
                       ):
        
        subPath = []
        subPathSign = []

        for sidx in GetSubInfo.subPurePath[::-1]:
            subPath.append(sidx)
        for sidx2 in GetSubInfo.subPurePath[::-1]:
            subPathSign.append(sidx2)

        for subid in range(len(subPath)):
            # Add the subarea to the JSON
            subNo = 0
            subNo = int(subPath[subid])

            #print(subid, subNo)
            wssubsolopslatlong[subid+1] = {}

            subNorecal = GetSubInfo.wssubflddict2[
                    str(1)][str(subNo)]
            wssubsolopslatlong[subid+1]['subNorecal'] = subNorecal

            # Append ws sub no to the dictionary
            wssubsolopslatlong[subid+1]['wsno'] = 1
            wssubsolopslatlong[subid+1]['subno'] = subNo

            
            # Subarea information are stored in lists
            # Update subarea NO: for routing 
            json['tempws']['model_setup'][
                'subid_snum'].append(subNorecal)

            # Update description line:
            json['tempws']['model_setup'][
                'description_title'].append(subid+1)

            # Updating Latitude and longitude
            json['tempws']['geographic']['latitude_xct'
                ].append(GetSubInfo.subLatLong[subNo][0])
            json['tempws']['geographic']['longitude_yct'
                ].append(GetSubInfo.subLatLong[subNo][1])
            json['tempws']['geographic']['subarea_elev_sael'
                ].append(GetSubInfo.avgElev[str(subNo)])
            # Append iops no to the dictionary
            wssubsolopslatlong[subid+1]['lat'] = GetSubInfo.subLatLong[subNo][0]
            wssubsolopslatlong[subid+1]['lon'] = GetSubInfo.subLatLong[subNo][1]


            # Updating Average upland slope
            slplen = 0.0
            slplen = GetSubInfo.avgSlope[str(subNo)]

            if (slplen > 50.0):
                json['tempws']['geographic']['avg_upland_slp'
                    ].append(50.0)
            elif( (slplen > 0.0) and (slplen <= 80.0)):
                json['tempws']['geographic']['avg_upland_slp'
                    ].append(slplen)
            else:
                json['tempws']['geographic']['avg_upland_slp'
                    ].append(5.0)

            # Updating Average upland slope length
            json['tempws']['geographic']['avg_upland_slplen_splg'
                ].append(GetSubInfo.avgSlpLen[str(subNo)])            

            # Updating Manning N upland
            # tempCSS: temp list storing the crop soil slope
            # combination [sub, nass lu, soil mukey, slop group]
            tempCSS = []
            tempCSS = GetSubInfo.subCSSMaxDict[subNo]
            json['tempws']['geographic']['uplandmanningn_upn'
                ].append(GetSubInfo.nassLuMgtUpn[tempCSS[1]][12])

            # Updating soil id
            json['tempws']['soil']['soilid'
                ].append(subid+1)

            # Append soil no to the dictionary
            wssubsolopslatlong[subid+1]['mukey'] = tempCSS[2]

            # Update IOPS NO
            json['tempws']['management']['opeartionid_iops'
                ].append(subid+1)
            json['tempws']['management']['OPSName_Reference'
                ].append(GetSubInfo.nassLuMgtUpn[tempCSS[1]][3])


            # Append iops no to the dictionary
            wssubsolopslatlong[subid+1]['iopsno'] = GetSubInfo.nassLuMgtUpn[tempCSS[1]][4]
            wssubsolopslatlong[subid+1]['iopsnm'] = GetSubInfo.nassLuMgtUpn[tempCSS[1]][3]
            # Updating land use no
            # There are other conditions, we will use the first
            # as default. TODO: may need to refine this later.
            json['tempws']['land_use_type']['land_useid_luns'
                ].append(GetSubInfo.nassLuMgtUpn[tempCSS[1]][5])

            # Updating Channel slope:TODO: Calculate the channel and
            # Reach slope later some how
            #print(GetSubInfo.strmAtt[subNo][10])
            json['tempws']['geographic']['channelslope_chs'
                ].append(GetSubInfo.strmAtt[str(subNo)][10])

            # Updating Reach slope: 
            json['tempws']['geographic']['reach_slope_rchs'
                ].append(GetSubInfo.strmAtt[str(subNo)][10])

            # Updating Channel manning n
            json['tempws']['geographic']['channelmanningn_chn'
                ].append(GetSubInfo.channelManN[0][4])


            subarea_area = 0.0
            subarea_area = GetSubInfo.subareaArea[str(subNo)]*GetSubInfo.cellsize*GetSubInfo.cellsize/10000.0
            rchchllen = 0.0
            chllen = 0.0
            rchchllen = GetSubInfo.rchchannenLen[str(subNo)]/1000.0
            chllen = GetSubInfo.channenLen[str(subNo)]/1000.0
            # make sure we have value not 0, had a minimum of 30 m
            if (rchchllen < 0.03):
                rchchllen = 0.03
            if (chllen < 0.03):
                chllen = 0.03 
            if (rchchllen >= chllen):
                chllen = rchchllen + 0.01
            print("reach, channel", rchchllen, chllen)
            if (subarea_area < 20.0):
                # Updating Channel Length and reach length
                # Reach (stream in TauDEM) length: strmAtt[str(subNo)][6]
                # If it is an extreme watershed, channel length is the max Plen
                if ((GetSubInfo.strmAtt[str(subNo)][2] == '-1')
                    and (GetSubInfo.strmAtt[str(subNo)][3] == '-1')):
                    json['tempws']['geographic']['channellength_chl'
                     ].append(0.5)
                    json['tempws']['geographic']['reach_length_rchl'
                     ].append(0.5)

                # If it is a routing watershed, channel length is the reach len
                # + max channel TODO: will be modified to get the channel length
                # for the watershed outlet
                else:
                    json['tempws']['geographic']['channellength_chl'
                        ].append(0.8)
                    json['tempws']['geographic']['reach_length_rchl'
                        ].append(0.5)

            else:
                # Updating Channel Length and reach length
                # Reach (stream in TauDEM) length: strmAtt[str(subNo)][6]
                # If it is an extreme watershed, channel length is the max Plen
                if ((GetSubInfo.strmAtt[str(subNo)][2] == '-1')
                    and (GetSubInfo.strmAtt[str(subNo)][3] == '-1')):
                    json['tempws']['geographic']['channellength_chl'
                        ].append(rchchllen)
                    json['tempws']['geographic']['reach_length_rchl'
                        ].append(rchchllen)

                # If it is a routing watershed, channel length is the reach len
                # + max channel TODO: will be modified to get the channel length
                # for the watershed outlet
                else:
                    json['tempws']['geographic']['channellength_chl'
                        ].append(chllen)
                    json['tempws']['geographic']['reach_length_rchl'
                        ].append(rchchllen)            

            # Updating Watershed area:
            #print(float(GetSubInfo.subareaArea[str(subNo)])*GetSubInfo.cellsize/10000.0)
            # The area for adding should be minus
            if '-' in GetSubInfo.subRouting[subid]:
                json['tempws']['geographic']['wsa_ha'
                    ].append('-%.5f' %(GetSubInfo.subareaArea[str(subNo)]
                         *GetSubInfo.cellsize*GetSubInfo.cellsize/10000.0))
            else:
                json['tempws']['geographic']['wsa_ha'
                ].append('%.5f' %(GetSubInfo.subareaArea[str(subNo)]
                         *GetSubInfo.cellsize*GetSubInfo.cellsize/10000.0))           

            # At this time, tile drainage is sitll unknow, but need to be 
            # initiated.
            json['tempws']['drainage']['drainage_depth_idr'
                ].append('0')
                   


        return json, wssubsolopslatlong


class apexfuncs():
    
    def updatejson_sit(self, json_sit, infosrc):
        
        json_sit["model_setup"]["siteid"]= "1"
        json_sit["model_setup"]["description_line1"]= "Site1"
        json_sit["model_setup"]["generation_date"]= time.strftime("%d/%m/%Y") 
        json_sit["model_setup"]["nvcn"]= "4"
        json_sit["model_setup"]["outflow_release_method_isao"]= "0"

        json_sit["geographic"]["latitude_ylat"]= infosrc[
                "tempws"]["geographic"]["latitude_xct"][0]
        json_sit["geographic"]["longitude_xlog"]= infosrc[
                "tempws"]["geographic"]["longitude_yct"][0]
        json_sit["geographic"]["elevation_elev"]= infosrc[
                "tempws"]["geographic"]["subarea_elev_sael"][0]

        json_sit["runoff"]["peakrunoffrate_apm"]= "1.00"
        json_sit["co2"]["co2conc_atmos_co2x"]= "330.00"
        json_sit["nitrogen"]["no3n_irrigation_cqnx"]= "0.00"
        json_sit["nitrogen"]["nitrogen_conc_rainfall_rfnx"]= "0.00"
        json_sit["manure"]["manure_p_app_upr"]= "0.00"
        json_sit["manure"]["manure_n_app_unr"]= "0.00"
        json_sit["irrigation"]["auto_irrig_adj_fir0"]= "0.00"
        json_sit["channel"]["basin_channel_length_bchl"] = infosrc[
                "tempws"]["geographic"]["channellength_chl"][0]
        json_sit["channel"]["basin_chalnel_slp_bchs"]= infosrc[
                "tempws"]["geographic"]["channelslope_chs"][0]

        return json_sit

    

#######################################################
# Call Functions
#######################################################

#start_time = time.time()

# Define classes
GetSubInfo = GetSubInfo()
apexfuncs = apexfuncs()


sitejson = GetSubInfo.readJSON(fin_tmpsitjson)


WatershedJSON=WatershedJSON()


# Update site json information
sitejson = apexfuncs.updatejson_sit(
        sitejson,
        WatershedJSON.WsJSON) 
    
# Change the key of dictioanry for processing in later steps    
WatershedJSON.WsJSON["watershed1"] = WatershedJSON.WsJSON.pop('tempws')
    


with open(fout_wssubvarjson, 'w') as outfile:
    json.dump(WatershedJSON.WsJSON, outfile)

with open(fout_wssubsollujson, 'w') as outfile2:
    json.dump(GetSubInfo.wsSubSolOpsLatLon, outfile2)

with open(fout_sitejson, 'w') as outfile3:
    json.dump(sitejson, outfile3)




#print("--- %s seconds ---" % (time.time() - start_time))











#######################################################


