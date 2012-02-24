#!/bin/bash

MANAGER_CLASS=cn.hjmao.msgswitch.service.ServiceManager
PROPERTY_FILE=etc/msgswitchServiceManagerProperty.txt

curDir=`pwd`
rootDir=$curDir/../java
binDir=$rootDir/bin
libDir=$rootDir/lib

export PATH=$PATH:/opt/thrift/bin
export CLASSPATH=$libDir/*:$CLASSPATH
pid=`jps |grep AvatarService |egrep "[0-9]+" -o`
if [ "" != "$pid" ]
then
        kill $pid
        echo waiting for 5 seconds
        sleep 5
fi
cd $binDir
if [ $# -lt 1 ]
then
        java $MANAGER_CLASS $rootDir/$PROPERTY_FILE
else
        echo background daemon
        java $MANAGER_CLASS $rootDir/$PROPERTY_FILE&
fi
cd $curDir

