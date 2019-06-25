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
fdclinear = sys.argv[2]

#fddata = 'devolt'
#fdclinear = 'clinear'



fdapexrun = os.path.join(
    fddata,
    'apexruns'
    )

fin_wssubvarjson = os.path.join(
    fdapexrun,
    'wssubvar.json'
    )

fin_wssubsollujson = os.path.join(
    fdapexrun,
    'wssubsollulatlon.json'
    )

fin_tmpsit = os.path.join(
    fdapexrun,
    'tmpsitefile.json'
    ) 



#######################################################
# Defining classes
#######################################################
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
    def initrunfiles(self, runfolder):
    
        # Remove the file if it exist
        fn_run = r"%s/APEXRUN.DAT" %(runfolder)
        if os.path.isfile(fn_run):
            os.remove(fn_run)
        
        outfid_run = 0
        outfid_run = open(fn_run, "w")

        return outfid_run    
    
    

    #######################################################
    def closerunfiles(self, fid):
        
        fid.writelines("%10s%7i%7i%7i%7i%7i%7i\n" %(\
                    "XXXXXXXXXX", 0, 0, 0, 0, 0, 0\
                    ))
        
        fid.close()


    #######################################################
    def write_runlines(self, runfid, subvars):
    
        #print(subvars.keys())
        '''
        1. Run file: runname, sitenumber, monthly wea station no,
        wind weather station no, subarea no, 0 normal soil, 
        subdaily weather file.
        '''
        for wsid in range(len(subvars.keys())):
        # APEXRUN is read with free format in APEX.exe
            runfid.writelines(u"%-10s%7i%7i%7i%7i%7i%7i\n" %(\
                            "RSUB%i" %(wsid+1 ) ,\
                            1,\
                            1,\
                            1,\
                            wsid+1,\
                            0, 0\
                            ))
    




    
    #######################################################
    def initsitecom(self, runfolder):

        # Remove the file if it exist
        outfn_sitcom = "SITECOM.DAT"
        fn_sitcom = r"%s/%s" %(runfolder,
                               outfn_sitcom)
        
        if os.path.isfile(fn_sitcom):
            os.remove(fn_sitcom)
            
        outfid_sitcom = 0
        outfid_sitcom = open(fn_sitcom, "w")
        return outfid_sitcom

    
    #######################################################
    def writesitcomline(self, fidsitcom):

        fidsitcom.writelines("%5i\tSIT%s.SIT\n" %(1,1))
    
    
    
    
    
    
    #######################################################
    def initsubcom(self, runfolder):

        outfn_subcom = "SUBACOM.DAT"
        fn_subcom = r"%s/%s" %(runfolder,
                           outfn_subcom)
        
        if os.path.isfile(fn_subcom):
            os.remove(fn_subcom)
                                
        outfid_subcom = 0        
        outfid_subcom = open(fn_subcom, "w")
        
        return outfid_subcom

    
    #######################################################
    def writesubcomline(self, fidsubcom, subvars):

        for wsid in range(len(subvars.keys())):
            fidsubcom.writelines("%5i\tSUB%s.SUB\n" %(wsid+1,
                                              wsid+1))
        
    
    
    
    
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
    def initopscom(self, runfolder):

        outfn_opscom = "OPSCCOM.DAT"
        fn_opscom = r"%s/%s" %(runfolder,
                               outfn_opscom)
        
        if os.path.isfile(fn_opscom):
            os.remove(fn_opscom)
              
        outfid_opscom = 0    
        outfid_opscom = open(fn_opscom, "w")
        
        return outfid_opscom

    
    #######################################################
    def writeopscomline(self, fidopscom, sollulatlong):

        for key, value in sollulatlong.items():
            fidopscom.writelines("%5s\tOP%s.OPC\n" %(key,
                                              value["iopsnm"]))    

    
    
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
        self.subvars = {}
        self.sollulatlong = {}
        
        self.subvars = self.read_json(fin_wssubvarjson)        
        self.sollulatlong = self.read_json(fin_wssubsollujson)
        
        # Get template of site and cont
        # We do not have sub json since the subvar was included
        self.sitejson = {}        
        self.sitejson = self.read_json(fin_tmpsit)

        # Modify the temp json values for writing
        # update site json
        self.sitejson = apexfuncs.updatejson_sit(
                self.sitejson,
                self.subvars) 
        
        # Before writing the information, I need to get the name
        # of weather stations here for each watershed. We probably
        # will still keep only one station for each watershed. This
        # is a small watershed and usually will be one station.
        # Can be improved if needed.
        self.cliStn = self.get1CliStn(self.sollulatlong,
                                      fdapexrun,
                                      fdclinear)
        
        
        # Initiate all variables
        self.fidrun = 0
        self.fidsitcom = 0
        self.fidsubcom = 0
        self.fidsolcom = 0
        self.fidopscom = 0
        self.fiddlycom = 0
        self.fidwndcom = 0
        self.fidwp1com = 0

        
        self.fidrun = apexfuncs.initrunfiles(fdapexrun)
        self.fidsitcom = apexfuncs.initsitecom(fdapexrun)
        self.fidsubcom = apexfuncs.initsubcom(fdapexrun)
        self.fidsolcom = apexfuncs.initsolcom(fdapexrun)
        self.fidopscom = apexfuncs.initopscom(fdapexrun)
        self.fiddlycom = apexfuncs.initdlycom(fdapexrun)
        self.fidwndcom = apexfuncs.initwndcom(fdapexrun)
        self.fidwp1com = apexfuncs.initwp1com(fdapexrun)        
        

        # Write contents in the list files. Here, we suppose to
        # have the number of lines same as the number of watersheds
        # Get the number of subareas
        apexfuncs.write_runlines(self.fidrun, self.subvars)
        apexfuncs.writesitcomline(self.fidsitcom)
        apexfuncs.writesubcomline(self.fidsubcom, self.subvars)
        apexfuncs.writesolcomline(self.fidsolcom, self.sollulatlong)
        apexfuncs.writeopscomline(self.fidopscom, self.sollulatlong)
        apexfuncs.writedlycomline(self.fiddlycom, self.cliStn, self.sollulatlong)
        apexfuncs.writewndcomline(self.fidwndcom, self.cliStn, self.sollulatlong)
        apexfuncs.writewp1comline(self.fidwp1com, self.cliStn, self.sollulatlong)







        
        # Close files
        apexfuncs.closerunfiles(self.fidrun)
        apexfuncs.closecomfiles(self.fidsitcom)
        apexfuncs.closecomfiles(self.fidsubcom)
        apexfuncs.closecomfiles(self.fidsolcom)
        apexfuncs.closecomfiles(self.fidopscom)
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
            sys.exit("failed to execute program ")
                        
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











#print("--- %s seconds ---" % (time.time() - start_time))











#######################################################

