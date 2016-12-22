#!/usr/bin/env bash

set -e

CONF_DIR="/etc/cjdns"

if [ ! -f "$CONF_DIR/cjdroute.conf" ]; then
  echo "generate $CONF_DIR/cjdroute.conf"
  conf=$(cjdroute --genconf | cjdroute --cleanconf)
  echo $conf > "$CONF_DIR/cjdroute.conf"
fi

if [ "x$PUBLIC_PEERS" == "xENABLED" ]; then
  echo "latest public peers enabled"
  conf=$(cjdroute --genconf | cjdroute --cleanconf)

fi

cjdroute --nobg < "$CONF_DIR/cjdroute.conf"
exit $?
