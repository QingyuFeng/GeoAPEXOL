#!/usr/bin/python3
# -*- coding: utf-8 -*-
"""
Created on Dec, 2018

This script was developed to convert the watershed
file to remove 0s in the demw. Both mapserver and 
apex model hate 0s in the subarea.


@author: qyfen
"""

#######################################################
# Environment setting
#######################################################
import sys,os
import json

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


fd_reclassifieddemw = os.path.join(
    fddata,
    'taudemlayers',
    'reclademw'
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
        self.uniqsubnos = self.getunisubnoslist(
            fin_demwasc)



        # reclassify the demw to each watershed for testing
        self.reclassPair = self.reclassifyDEMW(
                                    self.uniqsubnos)


    def getunisubnoslist(self, fin):
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

        return subnolist



    def reclassifyDEMW(self, subnolst):
        '''
        Loop throught each watershed and reclassify the demw.
        '''
        reclassPairs = {}

        reclassPairs['1'] = self.reclassify(subnolst)

        # Write the information into json files
        with open(fout_subpairjson, 'w') as fp:
            json.dump(reclassPairs, fp)


        return reclassPairs


    def reclassify(self, wssubids):

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
        des_classes = []

        maxsubno = max(map(int, wssubids))

        for subid in range(len(wssubids)):
            src_classes.append("==" + wssubids[subid])
            src_classes_store.append(wssubids[subid])
            if (wssubids[subid] == '0'):
                des_classes.append('%i' %(maxsubno + 1))
            else:
                des_classes.append(wssubids[subid])

        reclasspair.append(src_classes_store)
        reclasspair.append(des_classes)


        src_classes = ",".join(src_classes)

        des_classes = ",".join(map(str, des_classes))

        # output DEMREC name
        outrecdemw1 = os.path.join(
                    fd_reclassifieddemw,
                    'recdemw1.tif')
        #indemwtif = fin_demw

        # Then generate command
        cmd1 = ['python3 '
                + gdalrecpyfile
                + ' '
               + fin_demw
               + ' '
               + outrecdemw1
               + ' -c "'
               + src_classes
               + '" -r "'
               + des_classes
               + '" -d 0 -n true']
        os.system(cmd1[0])


        return reclasspair




#######################################################
# Call Functions
#######################################################

#start_time = time.time()

MainClass = MainClass()

#print("--- %s seconds ---" % (time.time() - start_time))

#######################################################

