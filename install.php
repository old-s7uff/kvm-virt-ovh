<?php
$boot_file = '/etc/grub.conf';
if(!file_exists($boot_file) && file_exists('/boot/grub/menu.lst')){
	$boot_file = '/boot/grub/menu.lst';
}
// Set the environment variables for the binaries
if(strtoupper(substr(PHP_OS, 0, 3)) != 'WIN'){
	putenv('PATH=/usr/kerberos/sbin:/usr/kerberos/bin:/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin:/root/bin:/usr/local/emps/bin:/usr/local/emps/sbin');
}
//////////////////////////
// Some INSTALL Functions
//////////////////////////
function install_handle_storage($virt){
	global $args, $globals, $ostemplates, $oses, $distro;
	// Is there an LV ?
	if(!empty($globals['lv'])){
		// Search the LV Group
		exec($globals['com']['vgdisplay'].' '.$globals['lv'].' >> /dev/null 2>&1', $vgout, $vgret);
		
		if($vgret != 0){
			echo "	ERROR : Logical Volume Group NOT FOUND \n Please specify the correct Logical Volume Group and then RE-Run this installer \n";
			shell_exec('echo "ERROR : Logical Volume Group NOT FOUND \n Please specify the correct Logical Volume Group and then RE-Run this installer" >> '.$args['log'].' 2>&1');
			return false;
		}
		// DO YOU have enough space IN IT
		exec($globals['com']['vgdisplay'].' -C --nosuffix -o Free --units K "'.$globals['lv'].'" 2>/dev/null', $volgr);
		
		if(empty($volgr[1])){
			echo "	ERROR : Logical Volume Group - '".$globals['lv']."' is FULL\n Please specify a Logical Volume Group which has enough space to create VPS(s) \n";
			shell_exec('echo "ERROR : Logical Volume Group - "'.$globals['lv'].'" is FULL\n Please specify a Logical Volume Group which has enough space to create VPS(s)" >> '.$args['log'].' 2>&1');
			return false;
		}
	// No storage	
	}
	return true;
}

// Installs Master only
function install_master(){
	global $args, $globals, $ostemplates, $oses, $distro;
	echo "	This is MASTER ! No Kernel to install ... Continuing \n";
	shell_exec('echo "This is MASTER ! No Kernel to install ... Continuing" >> '.$args['log'].' 2>&1');
	return true;
}
// Will Install KVM
function install_kvm(){
	
	global $args, $globals, $ostemplates, $oses, $distro;
	
	if(!isset($distro)){	
		$distro	 = get_distro();		
	}
	exec('uname -i', $out);
	
	// KVM not allowed in 32 Bit Operating System
	if($out[0] != 'x86_64'){
		echo "	ERROR : KVM can not be installed in 32 Bit Operating System \n";
		shell_exec('echo "ERROR : KVM can not be installed in 32 Bit Operating System" >> '.$args['log'].' 2>&1');
		return false;
	}
	// Handle Storage
	if(!install_handle_storage('kvm')){
		return false;	
	}
	
	// Does it support Virtualization
	exec('/bin/cat /proc/cpuinfo | grep vmx', $out, $intel);
	exec('/bin/cat /proc/cpuinfo | grep svm', $out, $amd);
	
	// $args['ovc'] - Override Virtualization Check
	if($intel != '0' && $amd != '0' && empty($args['ovc'])){
		echo "	ERROR : Your CPU doesnt support Hardware Level Virtualization OR you havent enabled Virtualization from the BIOS. Please enable Virtualization from the BIOS and then RE-Run this installer \n";
		shell_exec('echo "ERROR : Your CPU doesnt support Hardware Level Virtualization OR you havent enabled Virtualization from the BIOS. Please enable Virtualization from the BIOS and then RE-Run this installer" >> '.$args['log'].' 2>&1');
		return false;
	}
	exec('/sbin/lsmod | grep kvm', $out, $ret);
	
	if($distro == 'ubuntu'){
		exec('lsb_release -r', $out1, $ret1);
	}else{
		$release = file_get_contents('/etc/redhat-release');
	}
	
	// Is it installed or are we in Import Mode
	if($ret != '0' || !empty($args['import']) || ($distro == 'ubuntu' && preg_match('/(14.04|16.04)/is', $out1[0])) || (empty($distro) && preg_match('/release 7/is', $release))){
	
		if($distro == 'ubuntu'){
			
			// For skipping libguestfs-tools interactive mode
			putenv('DEBIAN_FRONTEND=noninteractive');
			
			$list = array("qemu-kvm", "libvirt-bin", "virtinst", "ntfs-3g", "sysv-rc-conf", "qemu-utils", "libvncserver0", "python-numpy", "chntpw", "qemu-img", "libguestfs-tools", "guestmount");
			
		}else{
		
			// yum install guestfish = libguestfs-tools-c and changes are related to 7
			// We have removed the guestfish as libguestfs-tools-c install guestfish functionality and libconfig need to be install seperatly for centos 7 for other it is installed as dependancy.
			$list = array("kvm", "kmod-kvm", "qemu-kvm", "ntfsprogs", "ntfs-3g", "libvirt", "virt-top", "libvncserver", "numpy", "chntpw", "qemu-img", "libconfig", "libguestfs-tools", "libguestfs-tools-c", "libguestfs-winsupport", "net-tools");
			
		}
		// Install the packages
		_package_install($list, $args['log']);
		echo "	KVM Module has been installed \n";
		shell_exec('echo "KVM Module has been installed" >> '.$args['log'].' 2>&1');
		
	}else{
		echo "	KVM already installed ... Continuing \n";
		shell_exec('echo "KVM already installed ... Continuing" >> '.$args['log'].' 2>&1');
	}
	
	// Was libvirt installed ?
	if(!is_dir('/etc/libvirt')){
		echo "	Errors while installing libvirt for KVM \n";
		return false;
	}
	
	// IP Forwarding Settings
	sysctl_configure('net.ipv4.ip_forward', 1);
	sysctl_configure('net.ipv6.conf.all.forwarding', 1);
	
	// Ensure the OS Templates folder is there
	@mkosdir();
	
	// Delete virbr0
	$osid = $GLOBALS['latest_os']['kvm'];
	
	// Save a OS Template
	$res = makequery("REPLACE INTO `os`
						SET osid = '$osid'");
	
	// Download if allowed
	if(empty($args['noos'])){
		shell_exec('wget -O /var/virtualizor/kvm/'.$oses[$osid]['filename'].' '.$oses[$osid]['url'].' >> '.$args['log'].' 2>&1');
	}
	
	// Edit the libvirt conf - Add the Sleep 5 after pre start script - Ubuntu
	if(file_exists('/etc/init/libvirt-bin.conf') && !empty($distro)){
	
		$file = file('/etc/init/libvirt-bin.conf');
		foreach($file as $k => $v){
			if(preg_match('/\bpre-start\b/is', $v)){
				$file[$k] = 'pre-start script'."\n\t".'sleep 5'."\n";
			}
		}
		// Write the changes
		writefile('/etc/init/libvirt-bin.conf', implode('', $file), 1);
	}
	
	// Is the distro ubuntu ?
	if($distro == 'ubuntu'){
		
		// Update the guestfs appliance
		@shell_exec('update-guestfs-appliance >> '.$args['log'].' 2>&1');
	}
	
	return true;

}
// Verifies the email address
function _emailvalidation($email){
	if(!preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\+._-])*@([a-zA-Z0-9_-])+([.])+([a-zA-Z0-9\._-]+)+$/", $email)){	
		return false;	
	}else{	
		return true;	
	}
}

// Installer function. works for RHEL and Debian based systems
// Params : $package : array or string provided with packages name
// $distro : Centos or Ubuntu. 
// $log : log path 
function _package_install($package, $log){
	
	global $globals, $distro;
	
	$package = (is_array($package) ? $package : array($package));
	
	$package_list = implode(' ', $package);
	
	if($distro == 'ubuntu' || $distro == 'proxmox'){
		foreach($package as $k => $v){
			shell_exec('apt-get -y install '.$v.' >> '.$log.' 2>&1');
		}
	}
	}

}
// chkconfig script.
// If priority given, will be applied on UBUNTU only.
function _init_config($service, $priority = ''){

	global $globals, $distro;
	// run according to distro
	if($distro == 'ubuntu'){
		shell_exec('update-rc.d '.$service.' defaults '.$priority.'');
	}
}
// Are we in MULTI Virt mode ?
if(defined('VIRTUALIZOR_MULTI')){
	return true;
}
//****************************
// Starting the INSTALL CODE
//****************************

if(empty($argv)){
	die('');
}
// The Log File
$args['log'] = '/root/virtualizor.log';

foreach($argv as $k => $v){
	$v = explode('=', $v);
	if(empty($v[1])) continue;
	$args[$v[0]] = $v[1];
}

// If no kernel specified then it is OpenVZ
if(empty($args['kernel'])){
	$args['kernel'] = 'openvz';
}

// Is it multi kernel
$args['kernel'] = str_replace('-', ' ', $args['kernel']);

// Check for allowed kernel arguments
$allowed_virts = array('kvm lxc', 'kvm openvz', 'openvz', 'kvm', 'lxc', 'xen', 'xcp', 'virtuozzo', 'proxmox');

if(!in_array($args['kernel'], $allowed_virts) && empty($args['master'])){
	echo "ERROR : Invalid kernel Specified : ".str_replace(' ', '-', $args['kernel']) ."\n";
	shell_exec('echo "ERROR : Invalid kernel Specified : '.str_replace(' ', '-', $args['kernel']).'"  >> '.$args['log'].' 2>&1');
	exit(16);
}

// Some OS specific commands :
$globals['com']['vgdisplay'] = (is_file('/sbin/vgdisplay') ? '/sbin/vgdisplay' : '/usr/sbin/vgdisplay');

// Verify the email address
if(!_emailvalidation($args['email'])){
	echo "	ERROR : Email address is not valid. Please give a VALID email address \n";
	shell_exec('echo "ERROR : Email address is not valid. Please give a VALID email address" >> '.$args['log'].' 2>&1');
	exit(16);
}
// Get the current distro
function get_distro(){

	// find the destro ubuntu or centos
	if(file_exists('/etc/redhat-release')){
		// Its CentOS
		$distro = '';
	}elseif(file_exists('/etc/debian_version')){
		// Its Ububtu
		$distro = 'ubuntu';
	}
	
	return $distro;
}
// find the distro ubuntu or centos
if(file_exists('/etc/redhat-release')){
	// Its CentOS
	$distro = $data['distro'] = '';
	
	$release = file_get_contents('/etc/redhat-release');
	
}elseif(is_dir('/etc/pve')){
	// Its proxmox
	$distro = $data['distro'] = 'proxmox';
	
	//Get Proxmox version
	exec('/usr/bin/pveversion', $proxmox_version);
	if(preg_match("/3.4/is", $proxmox_version[0])){
		$prox_v = 3;
	}else{
		$prox_v = 4;
	}
}elseif(file_exists('/etc/debian_version')){
	// Its Ububtu
	$distro = $data['distro'] = 'ubuntu';
}

// Download the Package
shell_exec('cd /root; wget -O latest.zip "http://www.virtualizor.com/updates.php?install=true&version=latest'.(!empty($args['beta']) ? '&beta=1' : '').'" >> '.$args['log'].' 2>&1');

// Unzip the Package
shell_exec('cd /root; unzip -o latest.zip -d /usr/local/virtualizor >> '.$args['log'].' 2>&1');

// Remove the Package
shell_exec('cd /root; /bin/rm -rf latest.zip >> '.$args['log'].' 2>&1');

// Now copy the CONF files
exec('ln -s /usr/local/virtualizor/conf/emps/nginx.conf /usr/local/emps/etc/nginx/nginx.conf >> '.$args['log'].' 2>&1');
exec('ln -s /usr/local/virtualizor/conf/emps/php-fpm.conf /usr/local/emps/etc/php-fpm.conf >> '.$args['log'].' 2>&1');
exec('ln -s /usr/local/virtualizor/conf/emps/php.ini /usr/local/emps/etc/php.ini >> '.$args['log'].' 2>&1');
exec('ln -s /usr/local/virtualizor/conf/emps/my.cnf /usr/local/emps/etc/my.cnf >> '.$args['log'].' 2>&1');
exec('ln -s /usr/local/virtualizor/conf/emps/emps /etc/init.d/virtualizor >> '.$args['log'].' 2>&1');

// Put the Auto start
@chmod('/usr/local/virtualizor/conf/emps/emps', 0755);
@chmod('/etc/init.d/virtualizor', 0755);

// Start MySQL
exec('/usr/local/emps/bin/mysqlctl start >> '.$args['log'].' 2>&1');

// Default is OpenVZ
if(empty($args['kernel'])){	
	$args['kernel'] = 'openvz';
}
@define('VIRTUALIZOR', 1);

//Set error reporting
error_reporting(E_ALL & ~E_DEPRECATED);

///////////////////////////
// TASKS TO DO
// 1) Make universal.php
// 2) Get license.php
// 3) Set the CRON
// 4) Import Database
///////////////////////////

// Include the RAW universal.php
include_once('_universal.php');

// Dont Connect!
$globals['dbconnect'] = 1;
//Some global vars
include_once($globals['path'].'/globals.php');

//The necessary functions to run this SOFTACULOUS Software
include_once($globals['mainfiles'].'/functions.php');

// EMPS Updater cron
$first_day = rand(1,10);
$second_day = $first_day + 15;

//===========================
// 1) Make the universal.php
//===========================
echo "		- Configuring Virtualizor\n";

$data['cookie_name'] = 'SIMCookies'.rand(1, 9999);
$data['dbpass'] = generateRandStr(10);
$data['soft_email'] = $args['email'];
$data['kernel'] = ($args['kernel'] == 'virtuozzo' ? 'vzk vzo' : $args['kernel']);
$data['kernel'] = ($data['kernel'] == 'proxmox' ? ($prox_v == 4 ? 'proxk proxl' : 'proxk proxo') : $data['kernel']);
$data['key'] = generateRandStr(32);
$data['pass'] = generateRandStr(32);
$data['lv'] = (empty($args['lvg']) ? '' : $args['lvg']);
//$data['disk_path'] = (empty($args['filedisk']) ? '' : $args['filedisk']);
$data['cron_time'] = rand(1, 59).' '.rand(1, 23).' * * *';
$globals['emps_cron_time'] = rand(1, 59).' '.rand(1, 23).' '.$first_day.','.$second_day.' * *';
$data['novnc'] = 1;
$data['turnoff_virtdf'] = 1;

// Proxmox Bridge
if($args['kernel'] == 'proxmox'){
	$args['bridge'] = (empty($args['bridge']) ? 'vmbr0' : $args['bridge']);
}

// Do we have a bridge ?
if(!empty($args['bridge'])){
	$globals['bridge'] = $args['bridge'];
	$data['bridge'] = $globals['bridge'];
}

// Do we have an interface ?
if(!empty($args['interface'])){
	$globals['interface'] = $args['interface'];
	$data['interface'] = $globals['interface'];
}

// Save it and reload as well
saveglobals($data, 1);


//===========================
// 2) Load the License
//===========================
echo "		- Fetching License\n";
loadlicense(1);

if(!file_exists($globals['path'].'/license2.php')){
	echo "	ERROR : Could not download the license file \n";
	shell_exec('echo "ERROR : Could not download the license file" >> '.$args['log'].' 2>&1');
	return false;
}

//===========================
// 3) Set the CRON
//===========================
echo "		- Setting up the CRON Job\n";
add_cron($globals['cron_time']);


//===========================
// 4) Import Database
//===========================
echo "		- Importing Database\n";

$globals['conn'] = @mysql_connect($globals['dbhost'], $globals['dbuser'], '', true);
@mysql_select_db('mysql', $globals['conn']) or die( "Unable to select database 1");

// Update the ROOT Password
makequery("UPDATE `user` 
SET `password` = PASSWORD('".$data['dbpass']."')
WHERE `User` = 'root'");

// Drop the Database if its there!
makequery("DROP DATABASE IF EXISTS `".$globals['db']."`");

// Create the Database
makequery("CREATE DATABASE `".$globals['db']."`");

shell_exec('/usr/local/emps/bin/mysqlctl restart');

// Import the Database
shell_exec('/usr/local/emps/bin/mysql -h localhost -u root -p'.$globals['dbpass'].' '.$globals['db'].' < '.$globals['path'].'/virtualizor.sql');

// Reconnect to test
$globals['conn'] = @mysql_connect($globals['dbhost'], $globals['dbuser'], $globals['dbpass'], true);
@mysql_select_db($globals['db'], $globals['conn']) or die( "Unable to select database 2");

// Insert the DB Version
makequery("REPLACE INTO `registry`
SET `name` = 'version',
`value` = '".$globals['version']."'");

//===========================
// 5) OS Templates List
//===========================
echo "		- Getting List of OS templates\n";
oslist(1);


//===========================
// 8) Installing the KERNEL
//===========================
echo "4) Installing the Virtualization Kernel - ".(!empty($args['master']) ? 'Master' : $args['kernel'])."\n";
shell_exec('echo "4) Installing the Virtualization Kernel - '.(!empty($args['master']) ? 'Master' : $args['kernel']).'" >> '.$args['log'].' 2>&1');

// We are installing Master
if(!empty($args['master'])){
	
	// Save you are master only server
	$data['is_master_only'] = 1;

	// Save it and reload as well
	saveglobals($data, 1);
	
	// Install the master
	if(!install_master()){
		exit(16);
	}
	
}else{
	
	$virts = explode(' ', $args['kernel']);
	
	foreach($virts as $_virt){
		
		// We are installing Linux KVM
		if($_virt == 'kvm'){
			if(!install_kvm()){
				exit(16);
			}
		}
	}
	// Install Comman Packages Needed for all Virt
	install_common_packages();
}
// Exit with a status of 8 - It was successful
exit(8);

?>
