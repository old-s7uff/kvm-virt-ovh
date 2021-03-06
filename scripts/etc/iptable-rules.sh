#!/bin/bash
iptables -F
iptables -A INPUT -m conntrack --ctstate ESTABLISHED,RELATED -j ACCEPT
iptables -P INPUT DROP
iptables -I INPUT 1 -i lo -j ACCEPT
iptables -A INPUT -p tcp --dport 22 -j ACCEPT
iptables -A INPUT -p tcp --dport 80 -j ACCEPT
iptables -A INPUT -p tcp --dport 21 -j ACCEPT
iptables -A INPUT -p tcp --dport 25 -j ACCEPT
iptables -A INPUT -p tcp --dport 53 -j ACCEPT
iptables -A INPUT -p tcp --dport 25 -j ACCEPT
iptables -A INPUT -p tcp --dport 465 -j ACCEPT
iptables -A INPUT -p tcp --dport 587 -j ACCEPT
iptables -A INPUT -p tcp --dport 4085 -j ACCEPT
iptables -A INPUT -p tcp --dport 4084 -j ACCEPT
iptables -A INPUT -p tcp --dport 4083 -j ACCEPT
iptables -A INPUT -p tcp --dport 4082 -j ACCEPT
iptables -A INPUT -p tcp --match multiport --dports 5899:8000 -j ACCEPT
iptables -A INPUT -p udp --match multiport --dports 5899:8000 -j ACCEPT
# iptables -A INPUT -p tcp --match multiport --dports 20:10000 -j ACCEPT
