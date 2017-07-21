#!/bin/bash

# move libvirt rules..
mv /opt/libvirt/ /etc/
mv /opt/virtnetwork /etc/init.d/
mv /opt/libvirt-bin.conf /etc/init/
mv /opt/virtualizor /etc/init.d/
mv /opt/zzvirtservice /etc/init.d/

#Start libvirt! Rules will apply (iptables, network)
service libvirt-bin stop
service libvirt-bin start
service isc-dhcp-server start

#Flush libvirt firewall rules, and apply yours!
sh /etc/scripts/iptable-rules.sh
