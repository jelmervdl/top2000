#!/bin/bash
set -e

if [ $# -gt 0 ]; then
	where="false"
	for song in "$@"; do 
		where="$where or .pos==$song"
	done
	filter=" | select($where)"
else
	filter=""
fi

commands=`jq -r ".[] $filter | \"php-cgi -f lyrics.php artist=\(.a|@uri|@sh) song=\(.s|@uri|@sh) > lyrics/\(.pos|@text).json\"" < ./2018.json`
IFS=$'\n'
for command in $commands;
do 
	echo "$command";
	sh -c "$command";
done