#!/usr/bin/python
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
#import time
import numpy as np
import pandas as pd

#######################################################
# Input and output file defining
#######################################################

#fin1 = "ascfile"
#fout = "unisubno.txt"

fddata = sys.argv[1]
webdir = sys.argv[2]

#fddata = 'devfld'

fin_demw = os.path.join(
    fddata,
    'taudemlayers',
    'demw.tif'
    )

fin_demwasc = os.path.join(
    fddata,
    'taudemlayers',
    'demw.asc'
    )


fin_demwfld = os.path.join(
    fddata,
    'taudemlayers',
    'demwfld.asc'
    )

fin_tree = os.path.join(
    fddata,
    'taudemlayers',
    "tree.txt"
    )

fout_unisubno = os.path.join(
    fddata,
    'taudemlayers',
    "unisubnoinfld.txt"
    )


fd_reclassifieddemw = os.path.join(
    fddata,
    'taudemlayers'
    )

fout_subpairjson = os.path.join(
    fddata,
    'taudemlayers',
    'reclademw',
    'demwreclasspair.json'
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

        # subinfld: read in the demwfld asc and get
        # the subarea numbers that covered by the field boundary.
        self.subinfld = self.getunisubnoslist(
            fin_demwfld,
            fout_unisubno)
        
        # Read in asc data for later processing
        self.dtsubno = self.readASCstr(fin_demwasc)
        ## Create Pandas dataframe to store all asc data
        self.dfasc = pd.DataFrame(self.dtsubno[3],
                                  columns=['subno'])
        self.uniqsubno = self.dfasc['subno'].unique()

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
#        for k, v in self.treedict2.items():
#            print(k, v)
        # A graph representing the subareas 
        self.watershedGraph = self.graphForWS(
            self.treedict2)

#        print(self.watershedGraph)

        # A list of subarea numbers that are flowing out
        # the field boundaries. These will be used as the
        # root of the depth first search to identify the
        # watersheds
        self.fieldOutletSubs = self.getFieldWSOutlets(self.subinfld,
                                              self.treedict2)

#        print(self.fieldOutletSubs)
        # A list of list storing the path of each watershed using
        # the outlets in the outlet list.
        self.wssubdict = self.findWatersheds(
                                    self.watershedGraph,
                                    self.fieldOutletSubs)
        
        
        # remove watersheds that already contained in other watersheds
        # This is done here because we do not know which watersheds has
        # more subareas. The reason to do this is to remove extra simulation
        # and remove mainly single subarea. 
        # This does not happen all the time. It happens most of the time
        # because of more than 2 (for example 1, 2, 3 drains to 4).
        # There are two situation this happens:
        # Taudem to make the rule only at most 2 drains to one downstream. 
        # But some times only 1 drains to it, so a extra empty number was
        # added. Or the stream was two small or not delineated.
        # Other times there are 3 drains to 1. 
        # I solved the two situations by removing/reconnecting the streams
        # and remove extra watersheds caused by watershed finds by depthfirst
        # search algorithms. 
#        print(self.wssubdict)
        self.wssubdict2 = self.removeextraWS(self.wssubdict)
        


        # reclassify the demw to each watershed for testing
        self.reclassPair = self.reclassifyDEMW(
                                    self.wssubdict2)


    def removeextraWS(self, wssubdict):
            
        # This function was written to remove single subareas already
        # contained in other watersheds.
        # The steps include: 
        # 1. Construct a list ordered by length of watershed subarea numbers.
        # 2. Construct a list of watershed only have 1 subareas.
        # 3. Loop through each of the first list, if the one subarea is
        # found in one larger watershed, remove the subarea from the list.
        wslist = sorted([v for k, v in wssubdict.items()], key=len, reverse=True)
        
        singlesublist = [v for k, v in wssubdict.items() if (len(v) == 1)]
        
        subtobepoped = []
        
        for wsid in wslist:
            if len(wsid) > 1:
                for subno in singlesublist:
                    if subno[0] in wsid:
                        subtobepoped.append(subno[0])
                        
        for rmid in subtobepoped:
            wslist.remove([rmid])
            
        # Create a new dictionary
        wsdict = {}
        
        for wsid2 in range(len(wslist)):
            wsdict[wsid2+1] = wslist[wsid2]
        
        return wsdict




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
#            print('upstream1: ',tempvalue[1],substreamdict[tempvalue[1]])
            substreamdict[tempvalue[1]][0] = tempvalue[0]
#            print('upstream1 lat: ', tempvalue[1],substreamdict[tempvalue[1]])
            
#            print('upstream2...: ',tempvalue[2],substreamdict[tempvalue[2]])
            substreamdict[tempvalue[2]][0] = tempvalue[0]
#            print('upstream2 lat: ', tempvalue[2], substreamdict[tempvalue[2]])

            # Change the upstream of this subarea's downstream to
            # its first upstream
            # The downstream might have two upstream, only change the one
            # that is equal to the subarea to be removed.
#            print('downstream', tempvalue[0], substreamdict[tempvalue[0]][1])
#            print('downstream', tempvalue[0], substreamdict[tempvalue[0]][2])
            
            if (substreamdict[tempvalue[0]][1] == substrmtorm[subid]):
#                print('upstream1: ',substreamdict[tempvalue[0]])
                substreamdict[tempvalue[0]][1] = tempvalue[1]
#                print('upstream1 later: ',substreamdict[tempvalue[0]])
            elif (substreamdict[tempvalue[0]][2] == substrmtorm[subid]):
#                print('upstream2: ',substreamdict[tempvalue[0]])
                substreamdict[tempvalue[0]][2] = tempvalue[1]
#                print('upstream2 later: ',substreamdict[tempvalue[0]])
            
            # First remove keys in dict
            substreamdict.pop(substrmtorm[subid], None)
            # First remove keys in dict
            substreamdict.pop(subid, None)

        return substreamdict



    def reclassifyDEMW(self, wssubdict):
        '''
        Loop throught each watershed and reclassify the demw.
        '''
        reclassPairs = {}
        
        for key,value in wssubdict.items():
            if (len(value)> 0):
                # Reclassify the demw
#                print(value)
                reclassPairs[key] = self.reclassify(key, value)
        
        # Write the information into json files
        import json

        with open(fout_subpairjson, 'w') as fp:
            json.dump(reclassPairs, fp)


        return reclassPairs

            
    def reclassify(self, wsno, wssubids):

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

        # Storing the original and reclassified subarea numbers
        # This will have two elements as lists: the original (src_classes),
        # the new (des_classes1)
        reclasspair = []

        # used to construct commands
        src_classes = []
        # used for appending to reclasspair
        src_classes_store = []
        # des_classes: for display: I will reclassify
        # the subareas into 1 to max. This is because
        # mapserver is hard to handle 32 bit raster.
        # I will try with 24 bit or 16 bit later.
        # But here let's work on the 8 bit first.
        des_classes1 = []

        # I will create two reclass layers for display
        # The one des_classes1 contains new numbers, these will
        # be converted to subarea shapefiles.
        # des_classes2 will only be one number for the watershed.
        des_classes2 = []
        for subid in range(len(wssubids)):
            src_classes.append("==" + wssubids[subid])
            src_classes_store.append(wssubids[subid])
            des_classes1.append(subid+1)
            des_classes2.append(wsno)

        reclasspair.append(src_classes_store)
        reclasspair.append(des_classes1)

        
        src_classes = ",".join(src_classes)

        des_classes1 = ",".join(map(str, des_classes1))

        # output DEMREC name
        outrecdemw1 = os.path.join(
                    fd_reclassifieddemw,
                    'reclademw',
                    'recdemw%i.tif' %(wsno)) 
        #indemwtif = fin_demw
        
        # Then generate command
        cmd1 = ['python '
                + gdalrecpyfile
                + ' '
               + fin_demw
               + ' '
               + outrecdemw1
               + ' -c "'
               + src_classes
               + '" -r "'
               + des_classes1
               + '" -d 0 -n true']
#        print(cmd1)
        
        os.system(cmd1[0])

        des_classes2 = ",".join(map(str, des_classes2))

        # output DEMREC name
        outrecdemw2 = os.path.join(
                    fd_reclassifieddemw,
                    'reclademw',
                    'wsnorecws%i.tif' %(wsno)) 
        
        # Then generate command
        cmd2 = ['python '
                + gdalrecpyfile
                + ' '
               + fin_demw
               + ' '
               + outrecdemw2
               + ' -c "'
               + src_classes
               + '" -r "'
               + des_classes2
               + '" -d 0 -n true']

#        print(cmd2)
        os.system(cmd2[0])

        return reclasspair

       


    def findWatersheds(self, watershedGraph, fieldOutletSubs):
        '''
        This function will loop through the outlet list and
        find the corresponding watersheds using depthfirst search
        algorithm.
        '''
        subsinWS = {}
        wsOutlets = {}
        
        print(fieldOutletSubs)
        # there might be some disruption of numbers, 
        # making the ws no not continuous.
        # I add a counter, ever time, there need to be
        # one append, add one.
        wsctr = 0
        for olid in range(len(fieldOutletSubs)):
            subPurePath = self.dfs_iterative(
                                    watershedGraph,
                                    fieldOutletSubs[olid])       
            # Jusge whether the new watershed is contained
            # in the old paths
            if olid == 0:
                wsctr = wsctr + 1
                subsinWS[wsctr] = subPurePath
                wsOutlets[wsctr] = fieldOutletSubs[olid]
            else:
                newpathlen = len(subPurePath)
                for k, v in subsinWS.items():
                    vlen = 0
                    vlen = len(v)
                    if vlen >= newpathlen:
                        if not set(subPurePath).issubset(set(v)):
                            # This means the new one is the subset of the existing one,
                            wsctr = wsctr + 1
                            subsinWS[wsctr] = subPurePath
                            wsOutlets[wsctr] = fieldOutletSubs[olid]
                            break
                        else:
                            break
                    elif vlen < newpathlen:
                        if set(v).issubset(set(subPurePath)):
                            # This means the existing one is the subset of the new one:
                            # replace it with the new one
                            subsinWS[k] = subPurePath
                            wsOutlets[k] = fieldOutletSubs[k]
                            break
                        else:
                            wsctr = wsctr + 1
                            subsinWS[wsctr] = subPurePath
                            wsOutlets[wsctr] = fieldOutletSubs[olid]
                            break

                    
                    

        return subsinWS


    def contains(self, small, big):
        for i in range(len(big)-len(small)+1):
            for j in xrange(len(small)):
                if big[i+j] != small[j]:
                    break
            else:
                return i, i+len(small)
        return False

    

    def dfs_iterative(self, graph, start):
        stack, path = [start], []

        while stack:
            vertex = stack.pop()
            if vertex in path:
                continue
            path.append(vertex)
            for neighbor in graph[vertex]:
                stack.append(neighbor)

        return path



    def getFieldWSOutlets(self, subinfld, treedict):
        '''
        This function will loop through each sub no in field,
        find its downstream no and determine whether the downstream
        is in the field. If not, the downstream will be determined as
        a watershed outlet subarea.
        '''
        outletlist = []

        for subid in subinfld:
            if not treedict[subid][0] in subinfld:
                if not subid in outletlist:
                    outletlist.append(subid)
                    #print(subid, treedict[subid][0])
        return outletlist


    def graphForWS(self, treedict):

        '''
        represent the watershed using a unreachable graph
        wsGraph = {node: [neighbors]}
        '''
        wsGraph = {}

        for key, value in treedict.items():
            wsGraph[key] = [k for k, v in treedict.items()
                            if v[0] == key]
            
        return wsGraph



    def getunisubnoslist(self, fin, fout):
        # This will borrow the idea of topcmd2, which grid 
        # is read.
        rows = 0
        cols = 0
            
        fp = open(fin, 'r')
        lif=fp.readlines()
        fp.close()
            
        for lidx in range(len(lif)):
            lif[lidx] = lif[lidx].split(' ')
            while '' in lif[lidx]:
                lif[lidx].remove('')
                
            lif[lidx][-1] = lif[lidx][-1][:-1]
                    
                
        cols = int(lif[0][1])
        rows = int(lif[1][1])
        nodata = lif[5][1]

        del(lif[:7])
            
        subnolist = []
            
        for ridx in range(rows-1):
            for cidx in range(cols):
                if lif[ridx][cidx] != nodata:
                    if not lif[ridx][cidx] in subnolist:   
                        subnolist.append(lif[ridx][cidx])

        # After geting the numbers, writing them into
        # a txt file.
        fid = open(fout, "w")
        for sidx in subnolist:
            fid.writelines("%s\n" %(sidx))
        
        fid.close()
            
        return subnolist


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
            
            treedict[lif[lidx][0]] = lif[lidx][3:] + [lif[lidx][0]]

        return treedict   







#######################################################
# Call Functions
#######################################################

#start_time = time.time()

MainClass = MainClass()
  
#print("--- %s seconds ---" % (time.time() - start_time))

#######################################################

