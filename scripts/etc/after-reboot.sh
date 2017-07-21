#!/bin/bash

# move libvirt rules..
mv /opt/libvirt/ /etc/

#Start libvirt! Rules will apply (iptables, network)
service libvirt-bin stop
service libvirt-bin start

#Flush libvirt firewall rules, and apply yours!
sh /etc/scripts/iptable-rules


