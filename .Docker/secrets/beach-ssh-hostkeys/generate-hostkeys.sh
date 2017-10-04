#!/bin/bash

TARGET_DIR=$(dirname $0)

if [ ! -e ${TARGET_DIR}/ssh-host-rsa-key ]
then
	echo "Generating host keys ..."

	ssh-keygen -f ${TARGET_DIR}/ssh-host-rsa-key -N '' -t rsa
	ssh-keygen -f ${TARGET_DIR}/ssh-host-dsa-key -N '' -t dsa
	ssh-keygen -f ${TARGET_DIR}/ssh-host-ecdsa-key -N '' -t ecdsa
	ssh-keygen -f ${TARGET_DIR}/ssh-host-ed25519-key -N '' -t ed25519

	mv ${TARGET_DIR}/ssh-host-dsa-key.pub ${TARGET_DIR}/ssh-host-dsa-key-pub
	mv ${TARGET_DIR}/ssh-host-ecdsa-key.pub ${TARGET_DIR}/ssh-host-ecdsa-key-pub
	mv ${TARGET_DIR}/ssh-host-ed25519-key.pub ${TARGET_DIR}/ssh-host-ed25519-key-pub
	mv ${TARGET_DIR}/ssh-host-rsa-key.pub ${TARGET_DIR}/ssh-host-rsa-key-pub

	echo "Done."
fi
