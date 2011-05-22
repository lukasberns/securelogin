#!/bin/bash

username=$1
password=$2

if [ -z $2 ]; then
	echo "Usage:"
	echo "$0 <username> <password>"
	exit 1
fi

alias md5='openssl md5 | head -c 32'

salt1=$(echo $RANDOM | md5)
salt2=$(echo $RANDOM | md5)
salt3=$(echo $RANDOM | md5)

hash1=$(printf $salt1$username$password | md5)
hash2=$(printf $salt2$username$password | md5)
hash23=$(printf $salt3$hash2 | md5)

echo 'Hashes for'
echo 'Username: '$username
echo 'Password: '$password
echo
echo 'Salt1:  '$salt1
echo 'Salt2:  '$salt2
echo 'Salt3:  '$salt3
echo
echo 'Hash1:  '$hash1
echo 'Hash23: '$hash23
