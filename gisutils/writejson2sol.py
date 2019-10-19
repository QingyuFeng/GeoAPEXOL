#!/usr/bin/python3
# -*- coding: utf-8 -*-
"""
Created on Nov, 2018

write sol json to sol files

@author: qyfen
"""

#######################################################
# Environment setting
#######################################################
import os
import json
import sys

#######################################################
# Input and output file defining
#######################################################

fddata = sys.argv[1]
scenariofdname = sys.argv[2]
#fddata = 'devfld'

fdapexrun = os.path.join(
    fddata,
    'apexruns',
    scenariofdname
    )

fin_soljson = os.path.join(
    fdapexrun,
    'runsol.json'
    )

#######################################################
# Defining classes
#######################################################



class apexfuncs():
    

    def write_sol(self, usr_runfd, soljs, mk):
        # I will use a distionary for cont lines
        # It will be initiated first as the template for stop.
        solf_name = ''
    
        # Test whether new there is soil test n and P
        solf_name = "SOL%s.SOL" %(soljs["solmukey"]) 

        # Start writing sol files    
        wfid_sol = 0
        wfid_sol = open(r"%s/%s" %(usr_runfd, solf_name) , "w")
    
        # Write line 1: desctiption
        sol_l1 = 0
        sol_l1 = "%20s\n" %(soljs["line1"]["soilname"])
        wfid_sol.writelines(sol_l1)
    
        # Writing line 2
        sol_l2 = 0
        #    ! SOIL PROPERTIES
    
            # modify hydrologic soi group from ABCD  to `1234' AS REQUIRED IN APEX.
        if "/" in soljs["line2"]["hydrologicgroup_hsg"]:
            soljs["line2"]["hydrologicgroup_hsg"]=\
                soljs["line2"]["hydrologicgroup_hsg"].split("/")[0]
    
        if soljs["line2"]["hydrologicgroup_hsg"] == "A":
            soljs["line2"]["hydrologicgroup_hsg"] = 1
        elif soljs["line2"]["hydrologicgroup_hsg"] == "B":
            soljs["line2"]["hydrologicgroup_hsg"] = 2
        elif soljs["line2"]["hydrologicgroup_hsg"] == "C":
            soljs["line2"]["hydrologicgroup_hsg"] = 3        
        else:
            soljs["line2"]["hydrologicgroup_hsg"] = 4
    
        sol_l2 = "%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f\n"\
                        %(float(soljs["line2"]["abledo_salb"]),\
                          float(soljs["line2"]["hydrologicgroup_hsg"]), 0.00,\
                          float(soljs["line2"]["minwatertabledep_wtmn"]),\
                          0.00, 0.00, 0.00, 0.00, 0.00, 0.00)
        wfid_sol.writelines(sol_l2)
    
        # Line 3:  Same format as line 2, different parameters. 
        # Some values were set to prevent any potential model run failure.
        # the 5th variable ZQT, should be from 0.01 to 0.25.
        # the 6th and 7th variable ZF should be from 0.05 to 0.25
        # the 8 and 9 should be larger than 0.03 and 0.3
        # The 10th should be left blank
        sol_l3 = 0
        sol_l3 = "%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f%8.2f        \n"\
                    %(float(soljs["line3"]["min_layerdepth_tsla"]), 
                        float(soljs["line3"]["weatheringcode_xids"]),
                        float(soljs["line3"]["cultivationyears_rtn1"]),
                        float(soljs["line3"]["grouping_xidk"]),
                        float(soljs["line3"]["min_maxlayerthick_zqt"]),
                        float(soljs["line3"]["minprofilethick_zf"]),
                        float(soljs["line3"]["minlayerthick_ztk"]),
                        float(soljs["line3"]["org_c_biomass_fbm"]),
                        float(soljs["line3"]["org_c_passive_fhp"])
                        )
        wfid_sol.writelines(sol_l3)
    
        # Starting from line 4, the variables will be writen for 
        # properties for eacy layer, and each column represent one layer.
        # It is better to use a loop to do the writing.
    
        sol_layer_pro = [""]*52
        layeridxlst = [int(float(soljs["layerid"]["z1"])),
                       int(float(soljs["layerid"]["z2"])),
                        int(float(soljs["layerid"]["z3"])),
                        int(float(soljs["layerid"]["z4"])),
                        int(float(soljs["layerid"]["z5"])),
                        int(float(soljs["layerid"]["z6"])),
                        int(float(soljs["layerid"]["z7"])),
                        int(float(soljs["layerid"]["z8"])),
                        int(float(soljs["layerid"]["z9"])),
                        int(float(soljs["layerid"]["z10"]))
                        ]
        
        for layeridx in range(0, max(layeridxlst)):
            if layeridx < max(layeridxlst)-1:
                #print(layeridx)
                
        #  !  4  Z    = DEPTH TO BOTTOM OF LAYERS(m)            
                sol_layer_pro[3] = sol_layer_pro[3] + "%8.2f" \
                    %(float(soljs["line4_layerdepth"]["z%i" %(layeridx+1)])/100)
    #  !  5  BD   = BULK DENSITY(t/m3)                
                sol_layer_pro[4] = sol_layer_pro[4] + "%8.2f" \
                    %(float(soljs["line5_moistbulkdensity"]["z%i" %(layeridx+1)]))
    #  !  6  UW   = SOIL WATER CONTENT AT WILTING POINT(1500 KPA)(m/m)                                             
    #  !            (BLANK IF UNKNOWN)                
                sol_layer_pro[5] = sol_layer_pro[5] + "%8.2f" \
                    %(float(soljs["line6_wiltingpoint"]["z%i" %(layeridx+1)])/100)
    #  !  7  FC   = WATER CONTENT AT FIELD CAPACITY(33KPA)(m/m)                                                    
    #  !            (BLANK IF UNKNOWN)                
                sol_layer_pro[6] = sol_layer_pro[6] + "%8.2f" \
                    %(float(soljs["line7_fieldcapacity"]["z%i" %(layeridx+1)])/100)
    #  !  8  SAN  = % SAND                 
                sol_layer_pro[7] = sol_layer_pro[7] + "%8.2f" \
                    %(float(soljs["line8_sand"]["z%i" %(layeridx+1)]))
    #  !  9  SIL  = % SILT                
                sol_layer_pro[8] = sol_layer_pro[8] + "%8.2f" \
                    %(float(soljs["line9_silt"]["z%i" %(layeridx+1)]))
    #  ! 10  WN   = INITIAL ORGANIC N CONC(g/t)       (BLANK IF UNKNOWN)                
                sol_layer_pro[9] = sol_layer_pro[9] + "%8.2f" \
                    %(0.00)
    #  ! 11  PH   = SOIL PH                
                sol_layer_pro[10] = sol_layer_pro[10] + "%8.2f" \
                    %(float(soljs["line11_ph"]["z%i" %(layeridx+1)]))
    #  ! 12  SMB  = SUM OF BASES(cmol/kg)              (BLANK IF UNKNOWN)
                sol_layer_pro[11] = sol_layer_pro[11] + "%8.2f" \
                    %(float(soljs["line12_sumofbase_smb"]["z%i" %(layeridx+1)]))
    #  ! 13  WOC  = ORGANIC CARBON CONC(%)                
                sol_layer_pro[12] = sol_layer_pro[12] + "%8.2f" \
                    %(float(soljs["line13_orgc_conc_woc"]["z%i" %(layeridx+1)]))
    #  ! 14  CAC  = CALCIUM CARBONATE(%)                 
                sol_layer_pro[13] = sol_layer_pro[13] + "%8.2f" \
                    %(float(soljs["line14_caco3_cac"]["z%i" %(layeridx+1)]))
    #  ! 15  CEC  = CATION EXCHANGE CAPACITY(cmol/kg)(BLANK IF UNKNOWN                
                sol_layer_pro[14] = sol_layer_pro[14] + "%8.2f" \
                    %(float(soljs["line15_cec"]["z%i" %(layeridx+1)]))
    #  ! 16  ROK  = COARSE FRAGMENTS(% VOL)              (BLANK IF UNKNOWN)           
                sol_layer_pro[15] = sol_layer_pro[15] + "%8.2f" \
                    %(100-float(soljs["line16_rock_rok"]["z%i" %(layeridx+1)]))
    #  ! 17  CNDS = INITIAL SOL N CONC(g/t)            (BLANK IF UNKNOWN) 
                sol_layer_pro[16] = sol_layer_pro[16] + "%8.2f" \
                    %(float(soljs["line17_inisolnconc_cnds"]["z%i" %(layeridx+1)]))
    #  ! 18  SSF  = INITIAL SOL P CONC(g/t)       (BLANK IF UNKNOWN)
                sol_layer_pro[17] = sol_layer_pro[17] + "%8.2f" \
                    %(float(soljs["line18_soilp_ssf"]["z%i" %(layeridx+1)]))
    #  ! 19  RSD  = CROP RESIDUE(t/ha)                (BLANK IF UNKNOWN)   
                sol_layer_pro[18] = sol_layer_pro[18] + "%8.2f" \
                    %(0.00)
    #  ! 20  BDD  = BULK DENSITY(OVEN DRY)(t/m3)   (BLANK IF UNKNOWN)                
                sol_layer_pro[19] = sol_layer_pro[19] + "%8.2f" \
                    %(float(soljs["line20_drybd_bdd"]["z%i" %(layeridx+1)]))
    #  ! 21  PSP  = P SORPTION RATIO                   (BLANK IF UNKNOWN)                  
                sol_layer_pro[20] = sol_layer_pro[20] + "%8.2f" \
                    %(0.00) 
    #  ! 22  SATC = SATURATED CONDUCTIVITY(mm/h)     (BLANK IF UNKNOWN)
                sol_layer_pro[21] = sol_layer_pro[21] + "%8.2f" \
                    %(float(soljs["line22_ksat"]["z%i" %(layeridx+1)]))
    #  ! 23  HCL  = LATERAL HYDRAULIC CONDUCTIVITY(mm/h)                
                sol_layer_pro[22] = sol_layer_pro[22] + "%8.2f" \
                    %(float(soljs["line22_ksat"]["z%i" %(layeridx+1)])/2)
    #  ! 24  WPO  = INITIAL ORGANIC P CONC(g/t)      (BLANK IF UNKNOWN)                
                sol_layer_pro[23] = sol_layer_pro[23] + "%8.2f" \
                    %(float(soljs["line24_orgp_wpo"]["z%i" %(layeridx+1)]))
    #  ! 25  DHN  = EXCHANGEABLE K CONC (g/t)                
                sol_layer_pro[24] = sol_layer_pro[24] + "%8.2f" \
                    %(0.00)
    #  ! 26  ECND = ELECTRICAL COND (mmho/cm)                
                sol_layer_pro[25] = sol_layer_pro[25] + "%8.2f" \
                    %(float(soljs["line26_electricalcond_ec"]["z%i" %(layeridx+1)]))
    #  ! 27  STFR = FRACTION OF STORAGE INTERACTING WITH NO3 LEACHING                                              
    #  !                                               (BLANK IF UNKNOWN)                
                sol_layer_pro[26] = sol_layer_pro[26] + "%8.2f" \
                    %(0.00)
    #  ! 28  SWST = INITIAL SOIL WATER STORAGE (m/m)                
                sol_layer_pro[27] = sol_layer_pro[27] + "%8.2f" \
                    %(0.00)
    #  ! 29  CPRV = FRACTION INFLOW PARTITIONED TO VERTICLE CRACK OR PIPE FLOW                
                sol_layer_pro[28] = sol_layer_pro[28] + "%8.2f" \
                    %(0.00)
    #  ! 30  CPRH = FRACTION INFLOW PARTITIONED TO HORIZONTAL CRACK OR PIPE                                        
    #  !            FLOW                 
                sol_layer_pro[29] = sol_layer_pro[29] + "%8.2f" \
                    %(0.00)
    #  ! 31  WLS  = STRUCTURAL LITTER(kg/ha)           (BLANK IF UNKNOWN)                
                sol_layer_pro[30] = sol_layer_pro[30] + "%8.2f" \
                    %(0.00)
    #  ! 32  WLM  = METABOLIC LITTER(kg/ha)            (BLANK IF UNKNOWN)            
                sol_layer_pro[31] = sol_layer_pro[31] + "%8.2f" \
                    %(0.00)
    #  ! 33  WLSL = LIGNIN CONTENT OF STRUCTURAL LITTER(kg/ha)(B I U)                
                sol_layer_pro[32] = sol_layer_pro[32] + "%8.2f" \
                    %(0.00)
    #  ! 34  WLSC = CARBON CONTENT OF STRUCTURAL LITTER(kg/ha)(B I U) 
                sol_layer_pro[33] = sol_layer_pro[33] + "%8.2f" \
                    %(0.00)
    #  ! 35  WLMC = C CONTENT OF METABOLIC LITTER(kg/ha)(B I U)
                sol_layer_pro[34] = sol_layer_pro[34] + "%8.2f" \
                    %(0.00)
    #  ! 36  WLSLC= C CONTENT OF LIGNIN OF STRUCTURAL LITTER(kg/ha)(B I U)
                sol_layer_pro[35] = sol_layer_pro[35] + "%8.2f" \
                    %(0.00)
    #  ! 37  WLSLNC=N CONTENT OF LIGNIN OF STRUCTURAL LITTER(kg/ha)(BIU)
                sol_layer_pro[36] = sol_layer_pro[36] + "%8.2f" \
                    %(0.00)
    #  ! 38  WBMC = C CONTENT OF BIOMASS(kg/ha)(BIU)
                sol_layer_pro[37] = sol_layer_pro[37] + "%8.2f" \
                    %(0.00)
    #  ! 39  WHSC = C CONTENT OF SLOW HUMUS(kg/ha)(BIU)
                sol_layer_pro[38] = sol_layer_pro[38] + "%8.2f" \
                    %(0.00)
    #  ! 40  WHPC = C CONTENT OF PASSIVE HUMUS(kg/ha)(BIU)
                sol_layer_pro[39] = sol_layer_pro[39] + "%8.2f" \
                    %(0.00)
    #  ! 41  WLSN = N CONTENT OF STRUCTURAL LITTER(kg/ha)(BIU)
                sol_layer_pro[40] = sol_layer_pro[40] + "%8.2f" \
                    %(0.00)
    #  ! 42  WLMN = N CONTENT OF METABOLIC LITTER(kg/ha)(BIU)
                sol_layer_pro[41] = sol_layer_pro[41] + "%8.2f" \
                    %(0.00)
    #  ! 43  WBMN = N CONTENT OF BIOMASS(kg/ha)(BIU)
                sol_layer_pro[42] = sol_layer_pro[42] + "%8.2f" \
                    %(0.00)
    #  ! 44  WHSN = N CONTENT OF SLOW HUMUS(kg/ha)(BIU)
                sol_layer_pro[43] = sol_layer_pro[43] + "%8.2f" \
                    %(0.00)
    #  ! 45  WHPN = N CONTENT OF PASSIVE HUMUS(kg/ha)(BIU)
                sol_layer_pro[44] = sol_layer_pro[44] + "%8.2f" \
                    %(0.00)
    #  ! 46  FE26 = IRON CONTENT(%)
                sol_layer_pro[45] = sol_layer_pro[45] + "%8.2f" \
                    %(0.00)
    #  ! 47  SULF = SULFUR CONTENT(%)                 
                sol_layer_pro[46] = sol_layer_pro[46] + "%8.2f" \
                    %(0.00)
    #  ! 48  ASHZ = SOIL HORIZON(A,B,C)                                                                            
                sol_layer_pro[47] = sol_layer_pro[47] + "%8s" \
                    %(" ")
    #   ! 49  CGO2 = O2 CONC IN GAS PHASE (g/m3 OF SOIL AIR)
                sol_layer_pro[48] = sol_layer_pro[48] + "%8.2f" \
                    %(0.00)
    #   ! 50  CGCO2= CO2 CONC IN GAS PHASE (g/m3 OF SOIL AIR)                                                       
                sol_layer_pro[49] = sol_layer_pro[49] + "%8.2f" \
                    %(0.00)
    #   ! 51  CGN2O= N2O CONC IN GAS PHASE (g/m3 OF SOIL AIR)                 
                sol_layer_pro[50] = sol_layer_pro[50] + "%8.2f" \
                    %(0.00)
            else:
        #  !  4  Z    = DEPTH TO BOTTOM OF LAYERS(m)            
                sol_layer_pro[3] = sol_layer_pro[3] + "%8.2f\n" \
                    %(float(soljs["line4_layerdepth"]["z%i" %(layeridx+1)])/100)
    #  !  5  BD   = BULK DENSITY(t/m3)                
                sol_layer_pro[4] = sol_layer_pro[4] + "%8.2f\n" \
                    %(float(soljs["line5_moistbulkdensity"]["z%i" %(layeridx+1)]))
    #  !  6  UW   = SOIL WATER CONTENT AT WILTING POINT(1500 KPA)(m/m)                                             
    #  !            (BLANK IF UNKNOWN)                
                sol_layer_pro[5] = sol_layer_pro[5] + "%8.2f\n" \
                    %(float(soljs["line6_wiltingpoint"]["z%i" %(layeridx+1)])/100)
    #  !  7  FC   = WATER CONTENT AT FIELD CAPACITY(33KPA)(m/m)                                                    
    #  !            (BLANK IF UNKNOWN)                
                sol_layer_pro[6] = sol_layer_pro[6] + "%8.2f\n" \
                    %(float(soljs["line7_fieldcapacity"]["z%i" %(layeridx+1)])/100)
    #  !  8  SAN  = % SAND                 
                sol_layer_pro[7] = sol_layer_pro[7] + "%8.2f\n" \
                    %(float(soljs["line8_sand"]["z%i" %(layeridx+1)]))
    #  !  9  SIL  = % SILT                
                sol_layer_pro[8] = sol_layer_pro[8] + "%8.2f\n" \
                    %(float(soljs["line9_silt"]["z%i" %(layeridx+1)]))
    #  ! 10  WN   = INITIAL ORGANIC N CONC(g/t)       (BLANK IF UNKNOWN)                
                sol_layer_pro[9] = sol_layer_pro[9] + "%8.2f\n" \
                    %(0.00)
    #  ! 11  PH   = SOIL PH                
                sol_layer_pro[10] = sol_layer_pro[10] + "%8.2f\n" \
                    %(float(soljs["line11_ph"]["z%i" %(layeridx+1)]))
    #  ! 12  SMB  = SUM OF BASES(cmol/kg)              (BLANK IF UNKNOWN)
                sol_layer_pro[11] = sol_layer_pro[11] + "%8.2f\n" \
                    %(float(soljs["line12_sumofbase_smb"]["z%i" %(layeridx+1)]))
    #  ! 13  WOC  = ORGANIC CARBON CONC(%)                
                sol_layer_pro[12] = sol_layer_pro[12] + "%8.2f\n" \
                    %(float(soljs["line13_orgc_conc_woc"]["z%i" %(layeridx+1)]))
    #  ! 14  CAC  = CALCIUM CARBONATE(%)                 
                sol_layer_pro[13] = sol_layer_pro[13] + "%8.2f\n" \
                    %(float(soljs["line14_caco3_cac"]["z%i" %(layeridx+1)]))
    #  ! 15  CEC  = CATION EXCHANGE CAPACITY(cmol/kg)(BLANK IF UNKNOWN                
                sol_layer_pro[14] = sol_layer_pro[14] + "%8.2f\n" \
                    %(float(soljs["line15_cec"]["z%i" %(layeridx+1)]))
    #  ! 16  ROK  = COARSE FRAGMENTS(% VOL)              (BLANK IF UNKNOWN)           
                sol_layer_pro[15] = sol_layer_pro[15] + "%8.2f\n" \
                    %(100-float(soljs["line16_rock_rok"]["z%i" %(layeridx+1)]))
    #  ! 17  CNDS = INITIAL SOL N CONC(g/t)            (BLANK IF UNKNOWN) 
                sol_layer_pro[16] = sol_layer_pro[16] + "%8.2f\n" \
                    %(float(soljs["line17_inisolnconc_cnds"]["z%i" %(layeridx+1)]))
    #  ! 18  SSF  = INITIAL SOL P CONC(g/t)       (BLANK IF UNKNOWN)
                sol_layer_pro[17] = sol_layer_pro[17] + "%8.2f\n" \
                    %(float(soljs["line18_soilp_ssf"]["z%i" %(layeridx+1)]))
    #  ! 19  RSD  = CROP RESIDUE(t/ha)                (BLANK IF UNKNOWN)   
                sol_layer_pro[18] = sol_layer_pro[18] + "%8.2f\n" \
                    %(0.00)
    #  ! 20  BDD  = BULK DENSITY(OVEN DRY)(t/m3)   (BLANK IF UNKNOWN)                
                sol_layer_pro[19] = sol_layer_pro[19] + "%8.2f\n" \
                    %(float(soljs["line20_drybd_bdd"]["z%i" %(layeridx+1)]))
    #  ! 21  PSP  = P SORPTION RATIO                   (BLANK IF UNKNOWN)                  
                sol_layer_pro[20] = sol_layer_pro[20] + "%8.2f\n" \
                    %(0.00) 
    #  ! 22  SATC = SATURATED CONDUCTIVITY(mm/h)     (BLANK IF UNKNOWN)
                sol_layer_pro[21] = sol_layer_pro[21] + "%8.2f\n" \
                    %(float(soljs["line22_ksat"]["z%i" %(layeridx+1)]))
    #  ! 23  HCL  = LATERAL HYDRAULIC CONDUCTIVITY(mm/h)                
                sol_layer_pro[22] = sol_layer_pro[22] + "%8.2f\n" \
                    %(float(soljs["line22_ksat"]["z%i" %(layeridx+1)])/2)
    #  ! 24  WPO  = INITIAL ORGANIC P CONC(g/t)      (BLANK IF UNKNOWN)                
                sol_layer_pro[23] = sol_layer_pro[23] + "%8.2f\n" \
                    %(float(soljs["line24_orgp_wpo"]["z%i" %(layeridx+1)]))
    #  ! 25  DHN  = EXCHANGEABLE K CONC (g/t)                
                sol_layer_pro[24] = sol_layer_pro[24] + "%8.2f\n" \
                    %(0.00)
    #  ! 26  ECND = ELECTRICAL COND (mmho/cm)                
                sol_layer_pro[25] = sol_layer_pro[25] + "%8.2f\n" \
                    %(float(soljs["line26_electricalcond_ec"]["z%i" %(layeridx+1)]))
    #  ! 27  STFR = FRACTION OF STORAGE INTERACTING WITH NO3 LEACHING                                              
    #  !                                               (BLANK IF UNKNOWN)                
                sol_layer_pro[26] = sol_layer_pro[26] + "%8.2f\n" \
                    %(0.00)
    #  ! 28  SWST = INITIAL SOIL WATER STORAGE (m/m)                
                sol_layer_pro[27] = sol_layer_pro[27] + "%8.2f\n" \
                    %(0.00)
    #  ! 29  CPRV = FRACTION INFLOW PARTITIONED TO VERTICLE CRACK OR PIPE FLOW                
                sol_layer_pro[28] = sol_layer_pro[28] + "%8.2f\n" \
                    %(0.00)
    #  ! 30  CPRH = FRACTION INFLOW PARTITIONED TO HORIZONTAL CRACK OR PIPE                                        
    #  !            FLOW                 
                sol_layer_pro[29] = sol_layer_pro[29] + "%8.2f\n" \
                    %(0.00)
    #  ! 31  WLS  = STRUCTURAL LITTER(kg/ha)           (BLANK IF UNKNOWN)                
                sol_layer_pro[30] = sol_layer_pro[30] + "%8.2f\n" \
                    %(0.00)
    #  ! 32  WLM  = METABOLIC LITTER(kg/ha)            (BLANK IF UNKNOWN)            
                sol_layer_pro[31] = sol_layer_pro[31] + "%8.2f\n" \
                    %(0.00)
    #  ! 33  WLSL = LIGNIN CONTENT OF STRUCTURAL LITTER(kg/ha)(B I U)                
                sol_layer_pro[32] = sol_layer_pro[32] + "%8.2f\n" \
                    %(0.00)
    #  ! 34  WLSC = CARBON CONTENT OF STRUCTURAL LITTER(kg/ha)(B I U) 
                sol_layer_pro[33] = sol_layer_pro[33] + "%8.2f\n" \
                    %(0.00)
    #  ! 35  WLMC = C CONTENT OF METABOLIC LITTER(kg/ha)(B I U)
                sol_layer_pro[34] = sol_layer_pro[34] + "%8.2f\n" \
                    %(0.00)
    #  ! 36  WLSLC= C CONTENT OF LIGNIN OF STRUCTURAL LITTER(kg/ha)(B I U)
                sol_layer_pro[35] = sol_layer_pro[35] + "%8.2f\n" \
                    %(0.00)
    #  ! 37  WLSLNC=N CONTENT OF LIGNIN OF STRUCTURAL LITTER(kg/ha)(BIU)
                sol_layer_pro[36] = sol_layer_pro[36] + "%8.2f\n" \
                    %(0.00)
    #  ! 38  WBMC = C CONTENT OF BIOMASS(kg/ha)(BIU)
                sol_layer_pro[37] = sol_layer_pro[37] + "%8.2f\n" \
                    %(0.00)
    #  ! 39  WHSC = C CONTENT OF SLOW HUMUS(kg/ha)(BIU)
                sol_layer_pro[38] = sol_layer_pro[38] + "%8.2f\n" \
                    %(0.00)
    #  ! 40  WHPC = C CONTENT OF PASSIVE HUMUS(kg/ha)(BIU)
                sol_layer_pro[39] = sol_layer_pro[39] + "%8.2f\n" \
                    %(0.00)
    #  ! 41  WLSN = N CONTENT OF STRUCTURAL LITTER(kg/ha)(BIU)
                sol_layer_pro[40] = sol_layer_pro[40] + "%8.2f\n" \
                    %(0.00)
    #  ! 42  WLMN = N CONTENT OF METABOLIC LITTER(kg/ha)(BIU)
                sol_layer_pro[41] = sol_layer_pro[41] + "%8.2f\n" \
                    %(0.00)
    #  ! 43  WBMN = N CONTENT OF BIOMASS(kg/ha)(BIU)
                sol_layer_pro[42] = sol_layer_pro[42] + "%8.2f\n" \
                    %(0.00)
    #  ! 44  WHSN = N CONTENT OF SLOW HUMUS(kg/ha)(BIU)
                sol_layer_pro[43] = sol_layer_pro[43] + "%8.2f\n" \
                    %(0.00)
    #  ! 45  WHPN = N CONTENT OF PASSIVE HUMUS(kg/ha)(BIU)
                sol_layer_pro[44] = sol_layer_pro[44] + "%8.2f\n" \
                    %(0.00)
    #  ! 46  FE26 = IRON CONTENT(%)
                sol_layer_pro[45] = sol_layer_pro[45] + "%8.2f\n" \
                    %(0.00)
    #  ! 47  SULF = SULFUR CONTENT(%)                 
                sol_layer_pro[46] = sol_layer_pro[46] + "%8.2f\n" \
                    %(0.00)
    #  ! 48  ASHZ = SOIL HORIZON(A,B,C)                                                                            
                sol_layer_pro[47] = sol_layer_pro[47] + "%8s\n" \
                    %(" ")
    #   ! 49  CGO2 = O2 CONC IN GAS PHASE (g/m3 OF SOIL AIR)
                sol_layer_pro[48] = sol_layer_pro[48] + "%8.2f\n" \
                    %(0.00)
    #   ! 50  CGCO2= CO2 CONC IN GAS PHASE (g/m3 OF SOIL AIR)                                                       
                sol_layer_pro[49] = sol_layer_pro[49] + "%8.2f\n" \
                    %(0.00)
    #   ! 51  CGN2O= N2O CONC IN GAS PHASE (g/m3 OF SOIL AIR)                 
                sol_layer_pro[50] = sol_layer_pro[50] + "%8.2f\n" \
                    %(0.00)
    
        for layproidx in range(3, 51):
            wfid_sol.writelines(sol_layer_pro[layproidx])
    
        
        wfid_sol.close()
            
        





class apexsolf():

    def __init__(self):
        """Constructor."""
        
        # Read in the json files into dict
        self.runjson = {}
        self.runjson = self.read_json(fin_soljson)    

        #Create template of soil jsons. 
        #This will contain template for all soils
        self.writesolf(
                self.runjson,
                fdapexrun
                )
        
   
        

    def writesolf(self, runjson, fdapexrun):
        
        for mkey, value in runjson.items():
            #print(mkey, value["line2"]["hydrologicgroup_hsg"])
            apexfuncs.write_sol(fdapexrun, value, mkey)


        


    def read_json(self, jsonname):

        soljs = None
        
        with open(jsonname) as json_file:    
            soljs = json.loads(json_file.read())
#        pprint.pprint(soljs)
        json_file.close()
        
        return soljs


            
    


#######################################################
# Call Functions
#######################################################

apexfuncs = apexfuncs()
apexsolf = apexsolf()




