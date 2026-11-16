#!/bin/bash

# Flush the content cache once per deployment, not on every pod start.
# Flow's CLI fails on an empty option value, so the option is only passed if set.
if [ -n "${BEACH_DEPLOYMENT_JOB_IDENTIFIER}" ]; then
    /application/flow neos.neosio:deployment:flushcontentcache --deployment-identifier "${BEACH_DEPLOYMENT_JOB_IDENTIFIER}"
else
    /application/flow neos.neosio:deployment:flushcontentcache
fi
