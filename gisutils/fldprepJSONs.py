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


from utm.conversion import to_latlon, from_latlon, latlon_to_zone_number, latitude_to_zone_letter
from utm.error import OutOfRangeError


#fddata = 'devfld'

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
    'streamnet.asc'
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
    'var1wssub.json'
    )

fin_tmpsitjson = os.path.join(
    fddata,
    'apexruns',
    'tmpsitefile.json'
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

fout_wssubvarjson = os.path.join(
    fddata,
    'apexruns',
    'runsub.json'
    )

fout_wssubsollujson = os.path.join(
    fddata,
    'apexruns',
    'wssubsollulatlon.json'
    )

fout_sitejson = os.path.join(
    fddata,
    'apexruns',
    'runsite.json'
    )



#######################################################
# Defining classes
#######################################################
class InfoFromFiles():

    def __init__(self):
        """Constructor."""

        # Slope group to be modified. Included here for
        # Future reference
        self.slopeGroup = [0, 2, 5, 9999]

        # A temporary to store the classes of each watershed 
        self.wsSubinfoClasses = {}
        # A continuous counter starting from subarea 1 to watershed
        # 1, to the last one. This will be used to write the list files.
        self.wsSubCtr = 0

        # A dictionary to store the ws_subno, soil, landuse,
        # latitude, longitude. The ws_subno will be the order
        # number of the list in soil, management, and daily weather
        # station.
        self.wsSubSolOpsLatLon = {}

        # wssubflddict: A dictionary to store the lines
        # in the wssubfld. Each line contains the subareas
        # in one watershed covered by the field.
        self.wssubflddict = self.readWsSubFlds(fin_demwreclasspair)
        
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
    
        # In order to remove the potential problems of having
        # to simulate water, I will need to remove those land
        # uses that are water.
        self.waterlus = list(map(str, [83,87,92,111,112,190,195]))
        self.nodatalus = list(map(str, [0,81,88]))
        # ~ turn True to False
        
        # This may cause some issue where water are a lot.
        # I will add another calculation to get the percent
        # of water area, which will be treated as ponds.
        #self.dfasc = self.dfasc[
            #~self.dfasc['luid'].isin(self.waterlus)]

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
        

        # Add a slopeGroup For processing
        self.dfasc['slopeGroup'] = self.dfasc['slope'].apply(
                                        self.getslopeIndex)

        # Generate CropSoilSlopeNumber
        self.dfasc['subCropSoilSlope'] = self.dfasc.apply(
                                        self.addCropSoilSlope,
                                        axis=1)
        
        # Calculate average dem/elevation for the subarea
        self.avgElev = self.dfasc.groupby('subno')[
                'elev'].mean().to_dict()

        # Recording the apperance of each combination of crop, soil
        # slope at each subarea
        # Count appearances of each combination for all subareas
        self.subCSSCounts = self.dfasc[
                'subCropSoilSlope'].value_counts()

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

        self.subCSSCountsMax['lu'] = self.subCSSCountsMax.apply(
                                self.assignLu,
                                axis=1)
        
        # Get the max combination for each subarea
        self.subCSSMaxDict, self.pondfrac = self.getCSSComb(
                self.subCSSCountsMax,
                self.waterlus)
        

        # Count appearances of each subarea no to get the subarea areas
        self.subareaArea = self.dfasc['subno'].value_counts().to_dict()

        ## Average upland slope
        # this is the average of slopes for all grids
        # Calculate average slope of each subarea
        self.avgSlope = self.dfasc.groupby('subno')['slope'].mean().to_dict()
        #print(self.avgSlope)
        ## Average upland slopelength:
        # This is calculated as the average of flowpath length
        # from the plen file (results of gridnet)
        # This value may need to be updated later. The method
        # to calculate it needs further exploration
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

        ## Subarea areas
        self.cellsize = float(self.dtsubno[4])

        ## Channel length:
        # Channel length is the length from the subarea outlet to
        # the most distant point. We have P len.
        # If the subarea is an extreme subarea, channel length is the
        # maximum plen value for all channel cells. 
        # If the subarea is an routing subarea, channel length need to be
        # larger than reach length. It will be the reach length + the maximum plen.
        # for non channel area.
        # self.channenLen: maximum plen for channel
        self.channenLen = self.dfasc[self.dfasc['strm01'] == '1'  \
                ][['subno', 'plen']].groupby('subno')['plen'].max().to_dict()


#        # Calculate the percent of pond in one subarea
#        self.pondPercent = 






    def modifywssubdict(self, wssubflddict):
        
        wsdict2 = copy.deepcopy(wssubflddict)
        
        for key, value in wssubflddict.items():
            wsdict2[key] = {}
            for vidx in range(len(value[0])):
                #print(wsdict2[key][0][vidx])
                wsdict2[key][value[0][vidx]] = value[1][vidx]

        
        return wsdict2
                
        



    def rmExtraStrm(self, subdemwlst, substreamdict):
        '''
        This function removed the subareas from subnoinstream
        which do not exist in subnoindemw. 
        '''
        substrm = substreamdict.keys()
        
        substrmtorm = [i for i in substrm
                       if not i in subdemwlst]
        
        for subid in substrmtorm:
            
            # Get the upstream and downstrema of the value to be removed
            tempvalue = None
            tempvalue = substreamdict[subid]
            
            # The streams connected by this need to be modified
            # value of dict [sub, downstrm, upstream1, upstream2]
            # upstream1 = tempvalue[2]
            #print('target: ',tempvalue)
            
            # Change the downstream of this subarea's upstream to
            # its downstream
            if not substreamdict[tempvalue[2]][1] == '-1':
                #print('upstream1: ',substreamdict[tempvalue[2]])
                substreamdict[tempvalue[2]][1] = tempvalue[1]
                #print('upstream1 lat: ',substreamdict[tempvalue[2]])
                
            if not substreamdict[tempvalue[3]][1] == '-1':
                #print('upstream2: ',substreamdict[tempvalue[3]])
                substreamdict[tempvalue[3]][1] = tempvalue[1]
                #print('upstream2 lat: ',substreamdict[tempvalue[3]])
                
            # Change the upstream of this subarea's downstream to
            # its first upstream
            #print('downstream', substreamdict[tempvalue[1]])
            if not substreamdict[tempvalue[1]][2] == '-1':
                #print('downstream1: ',substreamdict[tempvalue[1]])
                substreamdict[tempvalue[1]][2] = tempvalue[2]
                #print('downstream1 lat: ',substreamdict[tempvalue[1]])
                
            # First remove keys in dict
            substreamdict.pop(subid, None)

        return substreamdict
        



    def getCSSComb(self, df, waterlus):

        '''
        Add Get the maximum combination for each subarea
        '''
        outdict = {}
        pondpercent = {}

        subNos = df['subno'].unique()

        for sid in subNos:

            dftemp = 0
            dftemp = df[df['subno'] == sid]
                        
            dftemp = dftemp[dftemp[
                    'subCropSoilSlope']==dftemp[
                            'subCropSoilSlope'].max()]

            outdict[int(sid)] = dftemp.index[0].split('_')
            
            totalarea = 0
            totalarea = dftemp['subCropSoilSlope'].sum()
            
            # Count the counts of water land uses
            dfwstmp = dftemp[dftemp['lu'].isin(waterlus)]
            
            pondarea = 0
            pondarea = dfwstmp['subCropSoilSlope'].sum()
            
            pondpercent[int(sid)] = pondarea/totalarea
                        
            
        
        return outdict, pondpercent


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


    def assignLu(self, df):

        '''
        Add one column of combination of subarea no, crop/landuse, soil,
        and slopeGroup
        '''
        lu = df['comblst'][1]
        
        return lu



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


    def readJSON(self, fn_json):

        inf_usrjson = {}
        
        with open(fn_json) as json_file:    
            inf_usrjson = json.loads(json_file.read())
        #pprint.pprint(inf_usrjson)
        json_file.close()
        
        return inf_usrjson




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

        #columnname = []
        values = []
        outdict = {}
        
        with open(finCSV) as csv_file:
            csv_reader = csv.reader(csv_file, delimiter=',')
            line_count = 0
            for row in csv_reader:
                if line_count == 0:
                    #columnname = row
                    line_count += 1
                else:
                    values.append(row)
                    line_count += 1
                    
        for vid in values:
            outdict[vid[0]] = vid
        
        return outdict

    


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
            

    def readWsSubFlds(self, fn_json):

        inf_usrjson = {}
        
        with open(fn_json) as json_file:    
            inf_usrjson = json.loads(json_file.read())
        #pprint.pprint(inf_usrjson)
        json_file.close()
        
        return inf_usrjson





class WsSubInfo:

    def __init__(self, wsidx):
        
        """Constructor."""
        self.WsSubInfoReset()

        # A dataframe containing only information for the watershed
        # being processing.
        # Getting the subarea nos of this watershed.
        self.Subnos = None
        self.Subnos = InfoFromFiles.wssubflddict[wsidx][0]

        # Removing unneeded information in the subarea
        # wsSubInfoDf: dataframe containing only subareas of the
        # watershed being processing.
        self.InfoDf = None
        self.InfoDf = copy.deepcopy(InfoFromFiles.dfasc[
            InfoFromFiles.dfasc['subno'].isin(self.Subnos)])#.copy()

        # Finding the graph of the subarea.
        # To find the graph, the stream file sometimes does not
        # match the demw. I will use information from the tree file
        # find the graphs.
        self.wsGraph = None
        self.wsGraph = self.graphForWS(
            self.Subnos,
            InfoFromFiles.treedict2)
        
        # In order to use the dfs algorithm, here, we need to
        # figure out which is the outlet subarea. These watershed
        # was generated using depth first search. The first one
        # is the outlet subarea.
        self.wsOutlet = None
        self.wsOutlet = self.Subnos[0]

        # Getting the routing and pure paths
        ## self.subRouting: a list contains the minus values
        ## when the area of the subarea need to be minus in apex
        ## sub file.
        
        ## self.subPurePath: a list contains the routing path
        ## of the subareas. This was included because the strmAtt
        ## is a dictionary with positive keys. 
        self.subRouting = []
        self.subPurePath = []
        self.subPurePath, self.subRouting = self.dfs_iterative(
                                    self.wsGraph,
                                    self.wsOutlet)
        



    def WsSubInfoReset(self):

        self.Subnos = 0
        self.InfoDf = 0
        self.wsGraph = {}
        self.wsOutlet = 0
        self.subRouting = []
        self.subPurePath = []



    def modifywsJSON(self,
                     wsidx,
                     json,
                     wsSubCtr,
                     wssubsolopslatlong,
                     subPurePath,
                     subRouting,
                     InfoFromFiles
                     ):

        wsjsonkey2 = None
        wsjsonkey2 = 'watershed%i' %(wsidx+1)
        # Reset all variables to prevent continuous
        json[wsjsonkey2]['model_setup']['subid_snum'] = []
        json[wsjsonkey2]['model_setup'][
                'description_title'] = []
        json[wsjsonkey2]['geographic']['latitude_xct'
                ] = []
        json[wsjsonkey2]['geographic']['longitude_yct'
                ] = []
        json[wsjsonkey2]['geographic']['avg_upland_slp'
                ] = []
        json[wsjsonkey2]['geographic']['avg_upland_slplen_splg'
                ] = []          
        json[wsjsonkey2]['geographic']['uplandmanningn_upn'
                ] = []     
        json[wsjsonkey2]['soil']['soilid'] = []
        json[wsjsonkey2]['management']['opeartionid_iops'
                ] = []
        json[wsjsonkey2]['management']['OPSName_Reference'
                ] = []
        json[wsjsonkey2]['land_use_type']['land_useid_luns'
                ] = []
        json[wsjsonkey2]['geographic']['channelslope_chs'
                ] = []
        json[wsjsonkey2]['geographic']['reach_slope_rchs'
                ] = []
        json[wsjsonkey2]['geographic']['channelmanningn_chn'
                ] = []
        json[wsjsonkey2]['geographic']['channellength_chl'
                ] = []
        json[wsjsonkey2]['geographic']['reach_length_rchl'
                ] = []
        json[wsjsonkey2]['geographic']['wsa_ha'
                ] = []
        json[wsjsonkey2]['geographic']['subarea_elev_sael'
                ] = []
#        json[wsjsonkey2]['pond']['frac_pond_pcof'
#                ] = []
        json[wsjsonkey2]['drainage']['drainage_depth_idr'
                ] = []

        subPath = []
        subPathSign = []
        for sidx in subPurePath[::-1]:
            subPath.append(sidx)
        for sidx2 in subPurePath[::-1]:
            subPathSign.append(sidx2)

        for subid in range(len(subPath)):

            # Updating the counter
            wsSubCtr = wsSubCtr + 1
            wssubsolopslatlong[wsSubCtr] = {}
            
            # Add the subarea to the JSON
            subNo = 0
            subNo = int(subPath[subid])

            # Append ws sub no to the dictionary
            wssubsolopslatlong[wsSubCtr]['wsno'] = wsidx
            wssubsolopslatlong[wsSubCtr]['subno'] = subNo
            
            # Updating the counter
            # subNorecal: is the original number from 
            # taudem delineation. It is used in the tree
            # for routing. And here we can safely use
            # new numbers?
            subNorecal = InfoFromFiles.wssubflddict2[
                    str(wsidx+1)][str(subNo)]
            wssubsolopslatlong[wsSubCtr]['subNorecal'] = subNorecal

            #print(subid, subNo)
            # Subarea information are stored in lists
            # Update subarea NO: for routing 
            json[wsjsonkey2]['model_setup'][
                'subid_snum'].append(subNorecal)

            # Update description line:
            json[wsjsonkey2]['model_setup'][
                'description_title'].append(subid+1)
            
            # Updating Latitude and longitude
            json[wsjsonkey2]['geographic']['latitude_xct'
                ].append(InfoFromFiles.subLatLong[subNo][0])
            json[wsjsonkey2]['geographic']['longitude_yct'
                ].append(InfoFromFiles.subLatLong[subNo][1])
            json[wsjsonkey2]['geographic']['subarea_elev_sael'
                ].append(InfoFromFiles.avgElev[str(subNo)])


            # Append iops no to the dictionary
            wssubsolopslatlong[wsSubCtr]['lat'] = InfoFromFiles.subLatLong[subNo][0]
            wssubsolopslatlong[wsSubCtr]['lon'] = InfoFromFiles.subLatLong[subNo][1]
            
            # Updating Average upland slope
            json[wsjsonkey2]['geographic']['avg_upland_slp'
                ].append(InfoFromFiles.avgSlope[str(subNo)])

            # Updating Average upland slope length
            json[wsjsonkey2]['geographic']['avg_upland_slplen_splg'
                ].append(InfoFromFiles.avgSlpLen[str(subNo)])            

            # Updating Manning N upland
            # tempCSS: temp list storing the crop soil slope
            # combination [sub, nass lu, soil mukey, slop group]
            tempCSS = []
            tempCSS = InfoFromFiles.subCSSMaxDict[subNo]
            json[wsjsonkey2]['geographic']['uplandmanningn_upn'
                ].append(InfoFromFiles.nassLuMgtUpn[tempCSS[1]][12])

            # Updating soil id
            #json['soil']['soilid'].append(tempCSS[2])
            json[wsjsonkey2]['soil']['soilid'].append(wsSubCtr)
            # Append soil no to the dictionary
            wssubsolopslatlong[wsSubCtr]['mukey'] = tempCSS[2]

            # Update IOPS NO
            #json['management']['opeartionid_iops'
                #].append(InfoFromFiles.nassLuMgtUpn[tempCSS[1]][4])
            json[wsjsonkey2]['management']['opeartionid_iops'
                ].append(wsSubCtr)
            json[wsjsonkey2]['management']['OPSName_Reference'
                ].append(InfoFromFiles.nassLuMgtUpn[tempCSS[1]][3])

            # Append iops no to the dictionary
            wssubsolopslatlong[wsSubCtr]['iopsno'] = InfoFromFiles.nassLuMgtUpn[tempCSS[1]][4]
            wssubsolopslatlong[wsSubCtr]['iopsnm'] = InfoFromFiles.nassLuMgtUpn[tempCSS[1]][3]

            # Updating land use no
            # There are other conditions, we will use the first
            # as default. TODO: may need to refine this later.
            json[wsjsonkey2]['land_use_type']['land_useid_luns'
                ].append(InfoFromFiles.nassLuMgtUpn[tempCSS[1]][5])

            # Updating Channel slope:TODO: Calculate the channel and
            # Reach slope later some how
            #print(GetSubInfo.strmAtt[subNo][10])
            json[wsjsonkey2]['geographic']['channelslope_chs'
                ].append(InfoFromFiles.strmAtt[str(subNo)][10])

            # Updating Reach slope: 
            json[wsjsonkey2]['geographic']['reach_slope_rchs'
                ].append(InfoFromFiles.strmAtt[str(subNo)][10])

            # Updating Channel manning n
            json[wsjsonkey2]['geographic']['channelmanningn_chn'
                ].append(InfoFromFiles.channelManN[0][4])

            # Updating Channel Length and reach length
            # Reach (stream in TauDEM) length: strmAtt[str(subNo)][6]
            # If it is an extreme watershed, channel length is the max Plen
            if InfoFromFiles.strmAtt[str(subNo)][2] == '-1':
                json[wsjsonkey2]['geographic']['channellength_chl'
                    ].append(InfoFromFiles.channenLen[str(subNo)]/1000.0)
                json[wsjsonkey2]['geographic']['reach_length_rchl'
                    ].append(InfoFromFiles.channenLen[str(subNo)]/1000.0)

            # If it is a routing watershed, channel length is the reach len
            # + max channel TODO: will be modified to get the channel length
            # for the watershed outlet
            else:
                json[wsjsonkey2]['geographic']['channellength_chl'
                    ].append(float(
                        InfoFromFiles.strmAtt[str(subNo)][6])/1000.0+
                        InfoFromFiles.channenLen[str(subNo)]/1000.0)
                json[wsjsonkey2]['geographic']['reach_length_rchl'
                    ].append(float(
                        InfoFromFiles.strmAtt[str(subNo)][6])/1000.0)            

            # Updating Watershed area:
            #print(float(GetSubInfo.subareaArea[str(subNo)])*GetSubInfo.cellsize/10000.0)
            # The area for adding should be minus
            if '-' in self.subRouting[subid]:
                json[wsjsonkey2]['geographic']['wsa_ha'
                    ].append('-%.5f' %(InfoFromFiles.subareaArea[str(subNo)]
                         *InfoFromFiles.cellsize/10000.0))
            else:
                json[wsjsonkey2]['geographic']['wsa_ha'
                ].append('%.5f' %(InfoFromFiles.subareaArea[str(subNo)]
                         *InfoFromFiles.cellsize/10000.0))           

#            json[wsjsonkey2]['pond']['frac_pond_pcof'
#                ].append(InfoFromFiles.pondfrac[subNo])
            # At this time, tile drainage is sitll unknow, but need to be 
            # initiated.
            json[wsjsonkey2]['drainage']['drainage_depth_idr'
                ].append('0')



        return wsSubCtr, wssubsolopslatlong



    def dfs_iterative(self, graph, start):
        '''
        Using iterative pathways to find the routes.
        This algorithm used the stack data structure.
        Starting with a root, pop the root out and
        check whether this is in the path. At root,
        no, so append it. Then, find the neighbours
        of this root, append all of them. Then, check
        whether these are visited. If not, append to path.
        visit the vertex of the vertex, until the end
        of the path. Then, go back toh the visited vertex,
        till the neighbours of the root. Stack, first in,
        first come out. It always find the end of the branch.
        '''
        
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


    # This one is not used. The path generated always has some problem
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
                path, pathminus = self.dfs_recursive(
                    graph, neighbor, path, pathminus)

        return path, pathminus


    def graphForWS(self, subnos, dicttree):

        '''
        represent the watershed using a unreachable graph
        wsGraph = {node: [neighbors]}
        '''

        wsGraph = {}

        for sidx in subnos:
            wsGraph[sidx] = [k for k, v in dicttree.items()
                            if v[1] == sidx]
        
        return wsGraph


class apexfuncs():
    
    def updatejson_sit(self, json_sit, infosrc):
        
        json_sit["model_setup"]["siteid"]= "1"
        json_sit["model_setup"]["description_line1"]= "Site1"
        json_sit["model_setup"]["generation_date"]= time.strftime("%d/%m/%Y") 
        json_sit["model_setup"]["nvcn"]= "4"
        json_sit["model_setup"]["outflow_release_method_isao"]= "0"

        json_sit["geographic"]["latitude_ylat"]= infosrc[
                "watershed1"]["geographic"]["latitude_xct"][0]
        json_sit["geographic"]["longitude_xlog"]= infosrc[
                "watershed1"]["geographic"]["longitude_yct"][0]
        json_sit["geographic"]["elevation_elev"]= infosrc[
                "watershed1"]["geographic"]["subarea_elev_sael"][0]

        json_sit["runoff"]["peakrunoffrate_apm"]= "1.00"
        json_sit["co2"]["co2conc_atmos_co2x"]= "330.00"
        json_sit["nitrogen"]["no3n_irrigation_cqnx"]= "0.00"
        json_sit["nitrogen"]["nitrogen_conc_rainfall_rfnx"]= "0.00"
        json_sit["manure"]["manure_p_app_upr"]= "0.00"
        json_sit["manure"]["manure_n_app_unr"]= "0.00"
        json_sit["irrigation"]["auto_irrig_adj_fir0"]= "0.00"
        json_sit["channel"]["basin_channel_length_bchl"] = infosrc[
                "watershed1"]["geographic"]["channellength_chl"][0]
        json_sit["channel"]["basin_chalnel_slp_bchs"]= infosrc[
                "watershed1"]["geographic"]["channelslope_chs"][0]

        return json_sit

            

#######################################################
# Call Functions
#######################################################

# New classes
InfoFromFiles = InfoFromFiles()
apexfuncs = apexfuncs()


## Json file storing all necessary variables
# After getting the information for routing, it is time
# to process the information into the json files.
wsvarjson = InfoFromFiles.readJSON(fin_wssubjson) 
       
sitejson = InfoFromFiles.readJSON(fin_tmpsitjson)



# Create a dictionary that have required number of watersheds
# in the dictionary
for wsk, wsv in InfoFromFiles.wssubflddict.items():
    wsjsonkey = 'watershed%s' %(wsk)
    wsvarjson[wsjsonkey] = wsvarjson['tempws']
    
# If key is not present in dictionary, then del can throw KeyError
try:
    del wsvarjson["tempws"]
except KeyError:
    print("Key 'tempws' not found")
       

           
wsvarjson2 = {}
# Then, updating the json file
for wsid2 in range(len(InfoFromFiles.wssubflddict)):
    
    tempwsInfo = None
    tempwsInfo = WsSubInfo(str(wsid2+1))
    
    wsjsonkey2 = 'watershed%i' %(wsid2+1)
    # Update the json file
    InfoFromFiles.wsSubCtr,InfoFromFiles.wsSubSolOpsLatLon = tempwsInfo.modifywsJSON(
            wsid2,
            wsvarjson,
            InfoFromFiles.wsSubCtr,
            InfoFromFiles.wsSubSolOpsLatLon,
            tempwsInfo.subPurePath,
            tempwsInfo.subRouting,
            InfoFromFiles
            )

    
    wsvarjson2[wsjsonkey2] = copy.deepcopy(wsvarjson[wsjsonkey2])

# Update site json information
sitejson = apexfuncs.updatejson_sit(
        sitejson,
        wsvarjson2) 
    
    
    
    
# Write the information into a json file    
with open(fout_wssubvarjson, 'w') as outfile:
    json.dump(wsvarjson2, outfile)

with open(fout_wssubsollujson, 'w') as outfile2:
    json.dump(InfoFromFiles.wsSubSolOpsLatLon, outfile2)

with open(fout_sitejson, 'w') as outfile3:
    json.dump(sitejson, outfile3)



# Update 



#print("--- %s seconds ---" % (time.time() - start_time))











#######################################################

