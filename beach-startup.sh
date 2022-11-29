#!/bin/bash

# Flush the content cache on each deployment
/application/flow flow:cache:flushone Neos_Fusion_Content
