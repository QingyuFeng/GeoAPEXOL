#!/usr/bin/python
# -*- coding: utf-8 -*-
"""
Created on Jan 2019


@author: qyfen
"""

#######################################################
# Environment setting
#######################################################
import sys,os
import time
import numpy as np
import json

import matplotlib.pyplot as plt
import webcolors


#######################################################
# Input and output file defining
#######################################################

fddata = sys.argv[1]

#fddata = 'devfld'

fdout_rsnpmap = os.path.join(
    fddata,
    'apexoutjsonmap'
    )

fin_suslegendjson= os.path.join(
    fdout_rsnpmap,
    'bslnasarsltlegend.json'
    )

fin_figname= os.path.join(
    fdout_rsnpmap,
    'legend.tif'
    )



#######################################################
# Defining classes
#######################################################
class LegendFig():

    def __init__(self):
        """Constructor."""

        # Get the asa classes levels for generating legend picture
        self.qsnpclasslegend = self.readJSON(fin_suslegendjson)

        
        alllevels, alllevellabel, alllevelrgbs, alltitle = self.generateLegendFig(self.qsnpclasslegend)
        
        
        self.makeFigure( 
                   alllevels, 
                    alllevellabel,
                    alllevelrgbs,
                    alltitle,
                    fin_figname)
        #self.practiceFigure()



    def generateLegendFig(self, legenddict):
        
        # First process the data: the goal is to
        # create several lists to be used while 
        # generating figures.
         
        alllevels = []
        alllevellabel = []
        alllevelrgbs = []
        alltitle = ['Runoff (mm)',
                    'Erosion (ton/ha)',
                'Total N (kg/ha)',
                'Total P (kg/ha)'
                ]
             
         
         
        for k1, v1 in legenddict.items():

            levels = []
            levellabel = []
            levelrgbs = []
             
             
            for k2, v2 in v1.items():
                levels.append(k1.replace('level', ''))
                
                if k1 == 'runoff':
                    levellabel.append('%i to %i' %(
                         int(v2["min"]),
                         int(v2["max"])))

                else:
                    levellabel.append('%3.2f to %3.2f' %(
                         v2["min"],
                         v2["max"]))
                    
                    
                 
                rgbfloat = None
                rgbfloat = webcolors.rgb_to_rgb_percent((v2["RGB1"],
                                     v2["RGB2"],
                                     v2["RGB3"]))

                a = float(rgbfloat[0].replace(
                         '%', ''))/100.0
                b = float(rgbfloat[1].replace(
                         '%', ''))/100.0
                c = float(rgbfloat[2].replace(
                         '%', ''))/100.0
                 
                levelrgbs.append((a, b, c))
                 
             
            alllevels.append(levels)
            alllevellabel.append(levellabel)
            alllevelrgbs.append(levelrgbs)
            
             
        return alllevels, alllevellabel, alllevelrgbs, alltitle
            

        
    def makeFigure(self, 
                   levels, 
                    levellabel,
                    levelrgbs,
                    alltitle,
                    figname):
        
        # Initialize the plot
        fig = plt.figure(figsize=(4, 6))

        # Create a 1 col 4 row figure to be displayed 
        # next to the map      
        
        for fidx in range(len(levels)):
        
            ax = fig.add_subplot(2,2, fidx+1)
            
            # This is the horizontal bar position
            y_pos = np.arange(len(levels[fidx]))
            lengthofbar =  np.ones(len(levels[fidx]))*1
            
            ax.barh(y_pos, 
                    lengthofbar,
                    align='center',
                    #color=['black', 'red', 'green', 'blue', 'cyan'],
                    height=0.8,
                    color=levelrgbs[fidx]
                    )
            ax.set_title(alltitle[fidx],
                         fontsize=15)
            
            ax.set_yticks(y_pos)
            ax.set_yticklabels(levels[fidx])
            ax.invert_yaxis()  # labels read top-to-bottom
            ax.set_xticks([0, 1, 2, 3, 5])

            ax.axis('off')
            
            rects = ax.patches
            # For each bar: Place a label
            for rid in range(len(rects)):
                # Get X and Y placement of label from rect.
                x_value = rects[rid].get_width()
                y_value = rects[rid].get_y() + rects[rid].get_height() / 2
            
                # Number of points between bar and label. Change to your liking.
                space = 5
                # Vertical alignment for positive values
                ha = 'left'
            
                # If value of bar is negative: Place label left of bar
                if x_value < 0:
                    # Invert space to place label to the left
                    space *= -1
                    # Horizontally align label at right
                    ha = 'right'
                        
            
                # Use X value as label and format number with one decimal place
                label = levellabel[fidx][rid]
            
                # Create annotation
                plt.annotate(
                    label,                      # Use `label` as label
                    (x_value, y_value),         # Place label at end of the bar
                    xytext=(space, 0),          # Horizontally shift label by `space`
                    textcoords="offset points", # Interpret `xytext` as offset in points
                    va='center',                # Vertically center label
                    ha=ha,
                         fontsize=10)                      # Horizontally align label differently for
                                                # positive and negative values. 
            
            
            
# Code for adding labels to vertical bars          
#            rects = ax.patches
#            for rect, label in zip(rects, levellabel[fidx]):
#                height = rect.get_height()
#                ax.text(rect.get_x() + rect.get_width() / 2, height + 5, label,
#                        ha='center', va='bottom')
        
        plt.subplots_adjust(left=0.01,
                            bottom=0.1, 
                            right=0.8, 
                            top=0.9, 
                            wspace=0,
                            hspace=0.5)
#        plt.tight_layout()
#        plt.show()
        
        plt.savefig(figname, dpi=90)
        plt.close()


        
    def readJSON(self, fn_json):

        inf_usrjson = {}
        
        with open(fn_json) as json_file:    
            inf_usrjson = json.loads(json_file.read())
        #pprint.pprint(inf_usrjson)
        json_file.close()
        
        return inf_usrjson
        
        
    
    
    
    
    
    
    # Old functions of learning making a plot
    def getFigure(self):
        # Fixing random state for reproducibility
        
        plt.rcdefaults()
        fig, ax = plt.subplots()
        
        # Example data
        people = ('Tom', 'Dick', 'Harry', 'Slim', 'Jim')
        y_pos = np.arange(len(people))
        performance =  np.ones(len(people))*2
        
        ax.barh(y_pos, 
                performance,
                align='center',
                color=['black', 'red', 'green', 'blue', 'cyan'],
                height=0.8)
        
        ax.set_yticks(y_pos)
        ax.set_yticklabels(people)
        ax.invert_yaxis()  # labels read top-to-bottom
        ax.set_xlabel('Performance')
        ax.set_title('How fast do you want to go today?')
        ax.set_xticks([0, 1, 2, 3, 4])
        # create a list to collect the plt.patches data
        totals = []
        
        # set individual bar lables using above list
        for i in ax.patches:
            # get_width pulls left or right; get_y pushes up or down
            ax.text(i.get_width()+.3, 
                    i.get_y()+.38,
                    "test",
                    fontsize=15,
                    color='dimgrey')
        

        
        plt.show()

        
    # Old functions of learning making a plot
    def autolabel(self, rects):
        """
        Attach a text label above each bar displaying its height
        """
        for rect in rects:
            height = rect.get_height()
            ax.text(rect.get_x() + rect.get_width()/2., 1.05*height,
                '%d' % int(height),
                ha='center', va='bottom')



    # Old functions of learning making a plot
    def practiceFigure(self):
        
                
        # Initialize the plot
        fig = plt.figure()
        ax1 = fig.add_subplot(131)
        ax2 = fig.add_subplot(132)
        ax3 = fig.add_subplot(133)
        
        # Plot the data
        ax1.bar([1,2,3],[3,4,5])
        ax2.barh([0.5,1,2.5],[0,1,2])
        ax2.axhline(0.45)
        ax1.axvline(0.65)
        ax3.scatter(np.linspace(0, 1, 5), np.linspace(0, 5, 5))
        
        # Delete `ax3`
        fig.delaxes(ax3)
        
        # Show the plot
        plt.show()
        






#######################################################
# Call Functions
#######################################################

start_time = time.time()

LegendFig = LegendFig()
# Write the information into a json file    

print("--- %s seconds ---" % (time.time() - start_time))

#######################################################

