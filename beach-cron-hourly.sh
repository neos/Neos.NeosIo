#!/bin/bash

CURRENT_DATE_ALT=`date +"%y-%m-%d"`
CURRENT_TIME=`date +"%H:%M:%S"`
CURRENT_HOUR=`date +"%H"`

if [ "${CURRENT_HOUR}" == "02" ] ; then
    echo "${CURRENT_DATE_ALT} ${CURRENT_TIME}          INFO        Beach Cron Job       Syncing marketplace data, see MarketPlaceSync.log" >> /application/Data/Logs/System.log
    /application/flow marketplace:sync > /application/Data/Logs/MarketPlaceSync.log
fi
