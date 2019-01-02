#!/bin/bash

for filename in ./lyrics/*.json; do 
	if [ -e "lyrics/$(basename "$filename" .json).txt" ]; then
		continue
	fi

	lyrics=$(jq -r '.lyrics' "$filename" \
		| sed $'s/<br \/>/\\\n/g')
	if [ $? -eq 0 ]; then
		echo "$lyrics" > "lyrics/$(basename "$filename" .json).txt"
	else
		echo "Merde $filename"
	fi
done