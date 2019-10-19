#!usr/bin/bash

# Install virtualguest addition
wget https://download.virtualbox.org/virtualbox/5.2.18/VBoxGuestAdditions_5.2.18.iso
sudo mkdir /media/iso
sudo mount VBoxGuestAdditions_5.1.28.iso /media/iso
cd /media/iso
sudo ./VBoxLinuxAdditions.run

sudo adduser feng vboxsf


# Update and upgrade
sudo apt-get update -y
sudo apt-get upgrade -y

# Install apache2
sudo apt-get install apache2 -y

# Install php
sudo apt-get install php -y

# Install postgresql
sudo apt-get install postgresql postgresql-contrib -y

# Install phppgadmin
sudo apt-get install php-pgsql phppgadmin -y

# Install mapserver
sudo apt-get install libaprutil1-dev -y
sudo apt-get install libapr1-dev -y
sudo apt-get install libcurl4-gnutls-dev -y
sudo apt-get install libfcgi-dev -y
sudo apt-get install libgdal-dev -y
sudo apt-get install libgeos-dev -y
sudo apt-get install libsqlite-dev -y
sudo apt-get install libtiff-dev -y
sudo apt-get install libdb-dev -y
sudo apt-get install libpng-dev -y
sudo apt-get install libjpeg-dev -y
sudo apt-get install curl -y
sudo apt-get install libpcre3-dev -y
sudo apt-get install libpixman-1-dev -y
sudo apt-get install libgeos-dev -y
sudo apt-get install software-properties-common -y
sudo apt-add-repository ppa:ubuntugis/ubuntugis-unstable -y
sudo apt-get install cgi-mapserver -y
sudo apt-get install mapcache-cgi -y
sudo apt-get install libapache2-mod-mapcache -y
sudo apt-get install mapserver-bin -y
sudo apt-get install libapache2-mod-fastcgi -y
sudo service apache2 restart

sudo adduser feng www-data
sudo chown feng:www-data -R /var/www
sudo chmod 0755 -R /var/www
sudo a2enmod cgi && sudo service apache2 restart

# Install http-request2
sudo apt-get install php-http-request2 -y

# Install GDAL BIN and python gdal
sudo apt-get install gdal-bin -y
sudo apt-get install python-gdal -y

# Support 32 bit programs
sudo dpkg --add-architecture i386
sudo apt-get install libc6:i386 libncurses5:i386 libstdc++6:i38 -y


# Enable runnong gfortran programs
sudo apt-get install libgfortran3 -y
sudo apt-get install libgfortran3:i386 -y

# Install openmpi for taudem
sudo apt-get install openmpi-bin -y
sudo apt-get install openssh-server -y
sudo apt-get install libopenmpi-dbg -y
sudo apt-get install libopenmpi-dev -y
sudo apt-get instlal openmpi-common -y



# Reboot

#sudo make install :w

