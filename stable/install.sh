#!/bin/bash
clear

setenforce 0 >> /dev/null 2>&1

FILEREPO=http://files.virtualizor.com
LOG=/root/virtualizor.log

#----------------------------------
# Detecting the Architecture
#----------------------------------
if ([ `uname -i` == x86_64 ] || [ `uname -m` == x86_64 ]); then
	ARCH=64
else
	ARCH=32
fi
echo "-----------------------------------------------"
echo " Welcome to Softaculous Virtualizor Installer"
echo "-----------------------------------------------"
echo " "
#----------------------------------
# Some checks before we proceed
#----------------------------------

# Gets Distro type.
if [ -d /etc/pve ]; then
	OS=Proxmox
	REL=$(/usr/bin/pveversion)
elif [ -f /etc/debian_version ]; then
	OS=Ubuntu
	REL=$(cat /etc/issue)
elif [ -f /etc/redhat-release ]; then
	OS=redhat 
	REL=$(cat /etc/redhat-release)
else
	OS=$(uname -s)
	REL=$(uname -r)
fi


if [ "$OS" = Ubuntu ] ; then
	
	VER=$(lsb_release -r | cut -f2)
	
	if  [ "$VER" != "12.04" -a "$VER" != "14.04" -a "$VER" != "16.04" ]; then
		echo "Softaculous Virtualizor only supports Ubuntu 12.04 LTS, Ubuntu 14.04 LTS and Ubuntu 16.04 LTS"
		echo "Exiting installer"
		exit 1;
	fi

	if ! [ -f /etc/default/grub ] ; then
		echo "Softaculous Virtualizor only supports GRUB 2 for Ubuntu based server"
		echo "Follow the Below guide to upgrade to grub2 :-"
		echo "https://help.ubuntu.com/community/Grub2/Upgrading"
		echo "Exiting installer"
		exit 1;
	fi
fi

theos="$(echo $REL | egrep -i '(cent|Scie|Red|Ubuntu|xen|Virtuozzo|pve-manager)' )"

if [ "$?" -ne "0" ]; then
	echo "Softaculous Virtualizor can be installed only on CentOS, Redhat, Scientific Linux, Ubuntu, XenServer, Virtuozzo and Proxmox"
	echo "Exiting installer"
	exit 1;
fi

# Is Webuzo installed ?
if [ -d /usr/local/webuzo ]; then
	echo "Server has webuzo installed. Virtualizor can not be installed."
	echo "Exiting installer"
	exit 1;
fi

#----------------------------------
# Is there an existing Virtualizor
#----------------------------------
if [ -d /usr/local/virtualizor ]; then

	echo "An existing installation of Virtualizor has been detected !"
	echo "If you continue to install Virtualizor, the existing installation"
	echo "and all its Data will be lost"
	echo -n "Do you want to continue installing ? [y/N]"
	
	read over_ride_install

	if ([ "$over_ride_install" == "N" ] || [ "$over_ride_install" == "n" ]); then	
		echo "Exiting Installer"
		exit;
	fi

fi
#----------------------------------
# Install PHP, MySQL, Web Server
#----------------------------------
echo "2) Installing PHP, MySQL and Web Server"

# Stop all the services of EMPS if they were there.
/usr/local/emps/bin/mysqlctl stop >> $LOG 2>&1
/usr/local/emps/bin/nginxctl stop >> $LOG 2>&1
/usr/local/emps/bin/fpmctl stop >> $LOG 2>&1

# Remove the EMPS package
rm -rf /usr/local/emps/ >> $LOG 2>&1

# The necessary folders
mkdir /usr/local/emps >> $LOG 2>&1
mkdir /usr/local/virtualizor >> $LOG 2>&1

echo "1) Installing PHP, MySQL and Web Server" >> $LOG 2>&1
wget -N -O /usr/local/virtualizor/EMPS.tar.gz "http://files.softaculous.com/emps.php?arch=$ARCH" >> $LOG 2>&1

# Extract EMPS
tar -xvzf /usr/local/virtualizor/EMPS.tar.gz -C /usr/local/emps >> $LOG 2>&1
rm -rf /usr/local/virtualizor/EMPS.tar.gz >> $LOG 2>&1

#----------------------------------
# Download and Install Virtualizor
#----------------------------------
echo "3) Downloading and Installing Virtualizor"
echo "3) Downloading and Installing Virtualizor" >> $LOG 2>&1


#echo "copying install file"
mv /root/install.php /usr/local/virtualizor/install.php

# Run our installer
/usr/local/emps/bin/php -d zend_extension=/usr/local/emps/lib/php/ioncube_loader_lin_5.3.so /usr/local/virtualizor/install.php $*
phpret=$?
rm -rf /usr/local/virtualizor/install.php >> $LOG 2>&1
rm -rf /usr/local/virtualizor/upgrade.php >> $LOG 2>&1

# Was there an error
if ! [ $phpret == "8" ]; then
	echo " "
	echo "ERROR :"
	echo "There was an error while installing Virtualizor"
	echo "Please check /root/virtualizor.log for errors"
	echo "Exiting Installer"	
 	exit 1;
fi

#----------------------------------
# Starting Virtualizor Services
#----------------------------------
echo "Starting Virtualizor Services" >> $LOG 2>&1
/etc/init.d/virtualizor restart >> $LOG 2>&1

wget -O /tmp/ip.php http://softaculous.com/ip.php >> $LOG 2>&1 
ip=$(cat /tmp/ip.php)
rm -rf /tmp/ip.php

echo " "
echo "-------------------------------------"
echo " Installation Completed "
echo "-------------------------------------"
echo "Congratulations, Virtualizor has been successfully installed"
echo " "
/usr/local/emps/bin/php -r 'define("VIRTUALIZOR", 1); include("/usr/local/virtualizor/universal.php"); echo "API KEY : ".$globals["key"]."\nAPI Password : ".$globals["pass"];'
echo " "
echo " "
echo "You can login to the Virtualizor Admin Panel"
echo "using your ROOT details at the following URL :"
echo "https://$ip:4085/"
echo "OR"
echo "http://$ip:4084/"
echo " "
echo "You will need to reboot this machine to load the correct kernel"
echo -n "Do you want to reboot now ? [y/N]"
read rebBOOT

echo "Thank you for choosing Softaculous Virtualizor !"

if ([ "$rebBOOT" == "Y" ] || [ "$rebBOOT" == "y" ]); then	
	echo "The system is now being RESTARTED"
	reboot;
fi
