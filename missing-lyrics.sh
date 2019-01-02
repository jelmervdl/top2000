#!/bin/sh

# Wrong based on the response status
songs=$(grep -l '"error":' lyrics/*.json | sed -E 's/lyrics\/([[:digit:]]+)\.json/\1/')

# Wrong based on the contents of the txt file
# songs=$(grep -l 'null' lyrics/*.txt | sed -E 's/lyrics\/([[:digit:]]+)\.txt/\1/')

for song in $songs; do
	# Skip songs we already found manually (assumes we removed all [null] lyrics)
	# if [ -e "lyrics/$song.txt" ]; then
	# 	continue
	# fi

	error=$(jq ".error" < "lyrics/$song.json")
	summary=$(jq ".[] | select(.pos == $song) | \"Artist: \(.a); Song: \(.s)\"" < ./2018.json)
	echo "$song: $summary; Error: $error"
	# jq ".[] | select(.pos == $song) | \"http://lyrics.wikia.com/api.php?fmt=json&action=lyrics&artist=\(.a|@uri)&song=\(.s|@uri)\"" < ./2018.json
done