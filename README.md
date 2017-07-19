# kvm-virt-ovh
![](http://i.imgur.com/xApRr8o.png)

why? - got some problems with network on ovh. so this will not install virtualizor fully as it is. but here are removed
someparts of official installation script.(with "parts" i mean all those parts of script which install or do anything with network are removed!")

**How to use/install?**

1 . `wget https://raw.githubusercontent.com/systemroot/kvm-virt-ovh/master/install-kvm`

2 . `chmod +x install-kvm`

3 . `nano install-kvm` *(edit this line wrote your email https://github.com/systemroot/kvm-virt-ovh/blob/master/install-kvm#L159)*

4 . `./install-kvm`

Then at the end you will be able to read "installation has end" means virtualizor is installed. 

if you reboot your server and virtualizor is down, just start it like (`/etc/init.d/virtualizor start`)


**To do. **

 1 . Creating a better script. Script was created in fast way means there can be some errors. but it work!
 

**INFO.**

1 . **CREATED FOR UBUNTU 14.04 (KVM)** Don't blame if you read any kind of error in installation or after installation! it's beta.

2 . Don't add any other extra option like "interface=" or "lvg=" in installation script. you can add them in Virtualizor GUI.
