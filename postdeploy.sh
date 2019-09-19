#!/bin/bash

updatedFiles=$(git log --format="%H" -n 2 | xargs git diff --name-only)
for file in $updatedFiles
do
  if [ -f "./$file" ]
  then
    rsync -avhR $file $DEPLOYPATH/$file
  fi
done