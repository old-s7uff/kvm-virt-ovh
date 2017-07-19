# kvm-virt-ovh

why? - got some problems with network on ovh. so this will not install virtualizor fully as it is. but there are removed
someparts which virtualizor install anything which will touch network! 

How to use/install?

1 . `wget https://raw.githubusercontent.com/systemroot/kvm-virt-ovh/master/install-kvm`

2 . `chmod +x install-kvm`

3 . `nano install-kvm *(edit this line wrote your email https://github.com/systemroot/kvm-virt-ovh/blob/master/install-kvm#L147)*`

4 . `./install-kvm`

you will be asked to wrote mysql password. wrote whatever you want isn't problem. 

Then at the end you will be able to read "installation has end" means virtualizor is installed. 


To do. 

 1 . Script was created in fast way means there can be some errors. but it work!
 
 2 . if you reboot your server and virtualizor is down, just start it like (`/etc/init.d/virtualizor start`)
