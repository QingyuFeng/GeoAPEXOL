# GeoAPEXOL
A web gis interface for the Agricultural Policy Environmental eXtender (APEX) model

This interface contains two types of simulation. One type is "Simulate a Watershed", the other type is "Simulate a Field". For the watershed simulation, users start with delineating the stream network, followed by determining the location of the outlet and delineating the watershed. For the field simulation, users draw the boundary of a field under consideration and get watersheds covered by the field. After these steps, users can determine their intended scenarios (currently 4 scenarios provided) and run the model.
The results will be presented in two formats, maps and tables... 

The package also include a serversetup.sh, which contains the packages required to run the interface. 
Currently, this interface is deployed at http://horizon.nserl.purdue.edu/geoapexol for inner testing. Users who are interested to test it can contact me for user name and password.

The geoapexol_index.php.png is a structure of function calls from the index.php, which may help users have a better understanding of the interface.
