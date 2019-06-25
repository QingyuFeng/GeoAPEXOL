#!/usr/bin/python3
# -*- coding: utf-8 -*-
"""
Created on Nov, 2018



@author: qyfen
"""

#######################################################
# Environment setting
#######################################################
import os
import time
import json
import sys
#import psycopg2, psycopg2.extras

#######################################################
# Input and output file defining
#######################################################

fddata = sys.argv[1]

#fddata = 'devfld'

fdapexrun = os.path.join(
    fddata,
    'apexruns'
    )

fin_soillujson = os.path.join(
    fdapexrun,
    'wssubsollulatlon.json'
    )

fin_soiltempjson = os.path.join(
    fdapexrun,
    'tmpsolfile.json'
    )

fout_soljson = os.path.join(
    fdapexrun,
    'runsol.json'
    )

#######################################################
# Defining classes
#######################################################
class psqldb():

    def __init__(self):

        self.host='localhost'
        self.dbname = 'apexwebinput'
        self.usr = 'postgres'
        self.pw = 'nserl'

        try:
            self.dbconn = psycopg2.connect(
                    host=self.host,
                    database=self.dbname,
                    user=self.usr,
                    password=self.pw
                    )
        except:
            print('Can not establish connection')


    def closedb(self, conn):

        if conn is not None:
            conn.close()
            print('Database connection closed.')



class apexfuncs():


    #######################################################
    def initsolcom(self, runfolder):

        # Remove the file if it exist
        outfn_solcom = "SOILCOM.DAT"
        fn_solcom = r"%s/%s" %(runfolder,
                           outfn_solcom)

        if os.path.isfile(fn_solcom):
            os.remove(fn_solcom)

        outfid_solcom = 0
        outfid_solcom = open(fn_solcom, "w")

        return outfid_solcom


    #######################################################
    def writesolcomline(self, fidsolcom, sollulatlong):

        for key, value in sollulatlong.items():
            fidsolcom.writelines("%5s\tSOL%s.SOL\n" %(key,
                                              value['mukey']))




    #######################################################
    def closecomfiles(self, fid):

        fid.close()


#





class apexsols():

    def __init__(self):
        """Constructor."""

        # Read in the json files into dict
        self.solluson = {}
        self.solluson = self.read_json(fin_soillujson)

        self.tmpjson = {}
        self.tmpjson = self.read_json(fin_soiltempjson)

        # Initiate all variables
        self.fidsolcom = 0
        self.fidsolcom = apexfuncs.initsolcom(fdapexrun)

        # Write contents in the list files.
        apexfuncs.writesolcomline(self.fidsolcom,
                                  self.solluson)

        # Close files
        apexfuncs.closecomfiles(self.fidsolcom)

        # Get unique soil list
        self.uniqsolmk = self.getUniqSolMK(self.solluson)

        #Create template of soil jsons.
        #This will contain template for all soils
        self.tempsoljson = self.createSolTemp(
                self.uniqsolmk,
                self.tmpjson)

#        # Write the soil files
#        self.tempsoljson1 = self.updatetempsoljson(
#                self.tempsoljson,
#                psqldb.dbconn)
#
#        # Close connection
#        psqldb.closedb(psqldb.dbconn)
#
#        for key, value in self.tempsoljson1.items():
#            print(key, value["line2"]["hydrologicgroup_hsg"])
#
#        # Write sol files
#        #self.writesols(fdapexrun, self.tempsoljson1)
#


    def writesols(self, fdapexrun, tempsoljson):

        for key, value in tempsoljson.items():
            print(key, value["line2"]["hydrologicgroup_hsg"])
            #apexfuncs.write_sol(fdapexrun, tempsoljson[key])



    def createSolTemp(self, uniqsolmk, tmpjson):

        for sidx in uniqsolmk:
            tmpjson[sidx] = tmpjson['soil1']

        del tmpjson['soil1']

        return tmpjson


    def updatetempsoljson(self, tmpjson, conn):

        for k, v in tmpjson.items():

            apexfuncs.writesol(tmpjson, k, conn)

        return tmpjson









    def getUniqSolMK(self, sollujson):

        sollst = []

        for k, v in sollujson.items():
            sollst.append(v['mukey'])

        sollst = list(set(sollst))

        return sollst




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

#start_time = time.time()

psqldb = psqldb()
apexfuncs = apexfuncs()
apexsols = apexsols()



with open(fout_soljson, 'w') as outfile3:
    json.dump(apexsols.tempsoljson, outfile3)









#print("--- %s seconds ---" % (time.time() - start_time))

