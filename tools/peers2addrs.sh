#!/usr/bin/env bash

for x in $(./peerStats | cut -d. -f6 ); do

  p=$(../publictoip6 ${x}.k )

  echo ${x}.k = ${p}
  echo -e "\t"$(./sessionStats | grep ${x}.k | sed "s/\.${x}\.k//1")
  echo -e "\t"$(2>/dev/null ./ping ${p} | head -n1 | sed "s/\.${x}\.k//1" | awk '{print $2,$3}')"\n"

done


