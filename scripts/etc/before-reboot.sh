#!/bin/bash
# So libvirt will not apply any kind of rule on server boot!
mv /etc/libvirt/ /opt/
mv /etc/init.d/virtnetwork /opt/
mv /etc/init/libvirt-bin.conf /opt/
mv /etc/init.d/virtualizor /opt/
mv /etc/init.d/zzvirtservice /opt/
service virtnetwork stop
service isc-dhcp-server stop
dhclient
