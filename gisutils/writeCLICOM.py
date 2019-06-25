#!/usr/bin/python3
# -*- coding: utf-8 -*-
"""
Created on Jan, 2019


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
fdclinear = sys.argv[2]

#fddata = 'devfld'
#fdclinear = 'clinear'

fdapexrun = os.path.join(
    fddata,
    'apexruns'
    )

fin_wssubsollujson = os.path.join(
    fdapexrun,
    'wssubsollulatlon.json'
    )

fout_clijson = os.path.join(
    fdapexrun,
    'runclistn.json'
    )


#######################################################
# Defining classes
#######################################################
class apexfuncs():
    
    
    #######################################################
    def initdlycom(self, runfolder):

        outfn_dlycom = "WDLSTCOM.DAT"
        fn_dlycom = r"%s/%s" %(runfolder,
                               outfn_dlycom)
        
        if os.path.isfile(fn_dlycom):
            os.remove(fn_dlycom)
                                
        outfid_dlycom = 0
        outfid_dlycom = open(fn_dlycom, "w")
        
        return outfid_dlycom
    
    
    #######################################################
    def writedlycomline(self, fiddlycom, cliStn, sollulatlong):
        # Free format
        # ID, .DLY name, lat, long, ST, Loc
        fiddlycom.writelines("%5i\t%s.DLY\t%5.3f\t%5.3f\t%10s\t%s\n"
                             %(1,
                               cliStn[3],
                               sollulatlong['1']['lat'],
                               sollulatlong['1']['lon'],
                               cliStn[0],
                               cliStn[1]
                               ))
    
    
    #######################################################
    def initwndcom(self, runfolder):

        outfn_wndcom = "WINDCOM.DAT"
        fn_wndcom = r"%s/%s" %(runfolder,
                               outfn_wndcom)
        
        if os.path.isfile(fn_wndcom):
            os.remove(fn_wndcom)
                                
        outfid_wndcom = 0
        outfid_wndcom = open(fn_wndcom, "w")
        
        return outfid_wndcom
        
    
    #######################################################
    def writewndcomline(self, fidwndcom, cliStn, sollulatlong):
        # Free format
        # ID, .DLY name, lat, long, ST, Loc
        fidwndcom.writelines("%5i\t%s.WND\t%5.3f\t%5.3f\t%10s\t%s\n"
                             %(1,
                               cliStn[3],
                               sollulatlong['1']['lat'],
                               sollulatlong['1']['lon'],
                               cliStn[0],
                               cliStn[1]
                               ))
    
    #######################################################
    def initwp1com(self, runfolder):

        outfn_wp1com = "WPM1COM.DAT"
        fn_wp1com = r"%s/%s" %(runfolder,
                               outfn_wp1com)
        
        if os.path.isfile(fn_wp1com):
            os.remove(fn_wp1com)
                                
        outfid_wp1com = 0
        outfid_wp1com = open(fn_wp1com, "w")
        
        return outfid_wp1com
        
    
    #######################################################
    def writewp1comline(self, fidwp1com, cliStn, sollulatlong):
        # Free format
        # ID, .DLY name, lat, long, ST, Loc
        fidwp1com.writelines("%5i\t%s.WP1\t%5.3f\t%5.3f\t%10s\t%s\n"
                             %(1,
                               cliStn[3],
                               sollulatlong['1']['lat'],
                               sollulatlong['1']['lon'],
                               cliStn[0],
                               cliStn[1]
                               ))    
    
    
    
    
    #######################################################
    def closecomfiles(self, fid):
        
        fid.close()
    
        
    
    
    
    
    
    
    


class apexinputs():

    def __init__(self):
        """Constructor."""
        
        # Read in the json files into dict
        self.sollulatlong = {}
        self.sollulatlong = self.read_json(fin_wssubsollujson)
        
        # Before writing the information, I need to get the name
        # of weather stations here for each watershed. We probably
        # will still keep only one station for each watershed. This
        # is a small watershed and usually will be one station.
        # Can be improved if needed.
        self.cliStn = self.get1CliStn(self.sollulatlong,
                                      fdapexrun,
                                      fdclinear)
        
        self.cliStnDict = {1:self.cliStn}
        # Initiate all variables
        self.fiddlycom = 0
        self.fidwndcom = 0
        self.fidwp1com = 0

        self.fiddlycom = apexfuncs.initdlycom(fdapexrun)
        self.fidwndcom = apexfuncs.initwndcom(fdapexrun)
        self.fidwp1com = apexfuncs.initwp1com(fdapexrun)        
        
        # Write contents in the list files. Here, we suppose to
        # have the number of lines same as the number of watersheds
        # Get the number of subareas
        apexfuncs.writedlycomline(self.fiddlycom, self.cliStn, self.sollulatlong)
        apexfuncs.writewndcomline(self.fidwndcom, self.cliStn, self.sollulatlong)
        apexfuncs.writewp1comline(self.fidwp1com, self.cliStn, self.sollulatlong)
        
        # Close files
        apexfuncs.closecomfiles(self.fiddlycom)
        apexfuncs.closecomfiles(self.fidwndcom)
        apexfuncs.closecomfiles(self.fidwp1com)
        



    def read_json(self, jsonname):

        inf_usrjson = None
        
        with open(jsonname) as json_file:    
            inf_usrjson = json.loads(json_file.read())
#        pprint.pprint(inf_usrjson)
        json_file.close()
        
        return inf_usrjson

            

    def get1CliStn(self, sollulatlong, fdapexrun, fdclinear):
        '''
        This function loop through the latitude and longitude, which
        will be used to by the CliNearStn program to find the 
        nearest climate station for each.
        '''
        weastnlist = '%s/stations2015.db' %(fdclinear)
        climNearCpp = '%s/climNearest' %(fdclinear)
        
        # Delete the station file first if it exist.
        foutstn = '%s/station.txt' %(fdapexrun)

        # Generate the commands to run the commands
        # "/climNearest.exe $ziplong $ziplat $rundir $weastnlist"        
        cmd = ['%s %f %f %s/ %s' %(
                    climNearCpp,
                    sollulatlong['1']['lon'],
                    sollulatlong['1']['lat'],
                    fdapexrun,
                    weastnlist
                    )]
        try:
            os.system(cmd[0])
        except OSError as e:
            sys.exit("failed to execute program") 
                        
        wsCliStn = self.readOutStn(foutstn)
        
        return wsCliStn    
    
    
    def readOutStn(self, foutstn):
        '''
        This function read the contents from the station.txt generated
        by the cliNearStn program and return them in to a list.
        Contents in station.txt:
            IN
            WEST LAFAYETTE 6 NW IN
            6.119195
            IN129430
            # Updated Nov 16, 2006
            
        '''

        fid = open(foutstn, 'r')
        lif = fid.readlines()
        fid.close()  
        
        for lidx in range(len(lif)):
            lif[lidx] = lif[lidx][:-1]
                
        return lif
            
    


#######################################################
# Call Functions
#######################################################

start_time = time.time()

apexfuncs = apexfuncs()
apexinputs = apexinputs()



with open(fout_clijson, 'w') as outfile:
    json.dump(apexinputs.cliStnDict, outfile)







#print("--- %s seconds ---" % (time.time() - start_time))











#######################################################




