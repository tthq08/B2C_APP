#!/bin/sh

unset GIT_DIR
DeployPath="/var/www/DistributionB2C/"
cd $DeployPath
git stash
git pull
#git fetch --all
git reset --hard origin/master
chown -R nginx:nginx /var/www/DistributionB2C/


