#!/usr/bin/env bash

pushd `dirname $0` > /dev/null
SCRIPTPATH=`pwd -P`
popd > /dev/null

CERT_PATH=${SCRIPTPATH}

mkcert \
    -cert-file ${CERT_PATH}/app.crt \
    -key-file ${CERT_PATH}/app.key \
    juvem.test \
    *.juvem.test \
    localhost \
    127.0.0.1 \
    ::1