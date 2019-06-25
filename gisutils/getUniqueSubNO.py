#!/usr/bin/python
# -*- coding: utf-8 -*-
"""
Created on Oct 24, 2018

This was generated to get the subarea nos of a asc file

@author: qyfen
"""

#######################################################
# Environment setting
#######################################################
import sys


#######################################################
# Input and output file defining
#######################################################

#fin1 = "ascfile"
#fout = "unisubno.txt"

fin = sys.argv[1]
fout = sys.argv[2]

#######################################################
# Functions
#######################################################
def getunisubnoslist(fin, fout):
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
    print(lif)        
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
        


    

#######################################################
# Call Functions
#######################################################

getunisubnoslist(fin, fout)
    
    










#######################################################
