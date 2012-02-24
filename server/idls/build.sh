#!/bin/bash

curDir=`pwd`
javaDir=$curDir/../java
phpDir=$curDir/../php

rm -rf $curDir/gen-java $curDir/gen-php
rm -rf $javaDir/gen-java
rm -rf $phpDir/thrift/packages/inc

thrift -gen java inc.thrift
thrift -gen php -r inc.thrift

mkdir -p $phpDir/thrift/packages
cp -r $curDir/gen-php/inc $phpDir/thrift/packages/
cp -r $curDir/gen-java $javaDir

git add $curDir/gen-java $curDir/gen-php $phpDir/thrift/packages $javaDir/gen-java
