#

**Ignore iptable rules which libvirt-bin will apply!**

1 . ``curl -s -O https://raw.githubusercontent.com/systemroot/kvm-virt-ovh/master/new-install``

2 . ``sh new-install``

3 . ``apt-get install libvirt-bin -y``

After libvirt-bin installation you will be able to see the private network if you wrote ``ifconfig`` and some iptable rules
if you wrote ``iptable -L``

but if you now execute ``reboot``
everything will be the same if you execute ``ifconfig``

but not everything will be the same if you execute ``iptables -L``

Add your custom iptable rules into ``/etc/scripts/iptable-rules.sh``

While your machine is rebooting, no libvirt will start. libvirt will start after machine is started! (this script is for Ubuntu 14.04)
