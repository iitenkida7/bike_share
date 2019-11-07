#!/bin/bash

SCRIPT_DIR=$(cd $(dirname $0); pwd)
    php  ${SCRIPT_DIR}/migrate.php
while :
do
    php  ${SCRIPT_DIR}/checkPortStatu.php
    sleep 5m
done
