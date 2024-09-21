#!/bin/sh
printenv | grep -v "no_proxy" >> /etc/environment

# Starte den Cron-Dienst
service cron start

# Starte den Apache-Server im Vordergrund
#service apache2 start
apache2-foreground
