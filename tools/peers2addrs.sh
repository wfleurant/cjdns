#!/usr/bin/env bash

for x in $(./peerStats | cut -d. -f6); do

  p=$(../publictoip6 ${x}.k)
  echo "------------------------------------------------------ - ----  ----  ----  ----  ----  ----  ----  ----"
  echo ${x}.k = ${p} | column -t -s ":"
  echo "------------------------------------------------------ - ----  ----  ----  ----  ----  ----  ----  ----"
  echo -en "\n\trtHops "
  cjdcmd-ng traceroute -r ${p} 2>&1 | grep -v ^Failed

  echo -e "\n"
  echo -e "\trtStat "$(./sessionStats | grep ${x}.k | sed "s/\.${x}\.k//1")
  echo -e "\t"$(./sessionStats | grep ${x}.k | sed "s/\.${x}\.k//1")
  echo -e "\n"
  echo -e "\trtPing "$(2>/dev/null ./ping ${p} | head -n1 | sed "s/\.${x}\.k//1" | awk '{print $2,$3}')"\n"
  echo -e "\n"

done


