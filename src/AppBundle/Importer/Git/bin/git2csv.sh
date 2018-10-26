#!/bin/bash

PROJECT_PATH=$1
SINCE=$2
BRANCH=$3

if ! test "$PROJECT_PATH"; then
    echo "git2csv.sh PROJECT_PATH [DATE_SINCE] [BRANCH]"
    exit;
fi

if ! test "$SINCE"; then
	SINCE="1990-01-01"
fi

if test "$BRANCH"; then
    BRANCH="$BRANCH"
fi

if ! test "$BRANCH"; then
	BRANCH="--branches"
fi

cd $PROJECT_PATH

git log $BRANCH --stat --source --date=iso --since="$SINCE" | tr -d ";" | sed -r 's/(commit [0-9a-z]+)\t(.+)/\1;\2branch/' | tr -d "\t" | tr "\n" "\t" | sed 's/$/\n/' | sed -r 's/commit ([0-9a-z]+)([^\t]+)branch\t/\n\1\2;/g' | sed 's/Author: /;/' | sed 's/Date: /;/' | sed 's/\t\t$//g' | sed 's/\t\t/;/g' | sed 's/\t;/;/g' | sed -r 's/[ ]+/ /g' | sed "s/\t/\\\n/g" | sed 's/; /;/g' | sed 's/\\n$//' | grep -a -v "^$" | sort -t ";" -k 1,1 -u
