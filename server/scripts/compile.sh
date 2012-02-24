#!/bin/bash

curDir=`pwd`
rootDir="$curDir/../java"
binDir="$rootDir/bin"
libDir="$rootDir/lib"

mkdir -p $binDir
mkdir -p $libDir

export PATH=$PATH:/opt/thrift/bin
export CLASSPATH=$libDir/*:$CLASSPATH

cd $rootDir
javaFiles=`find $rootDir/src $rootDir/gen-java -iname "*.java"`
echo "Compiling ..."
javac -d $binDir $javaFiles
cd $curDir

