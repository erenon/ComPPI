#!/bin/bash

# "Profiles" database load performance
# Internal use ony

# Usage: 
# $ time ./prof_build.sh

dir="$( cd "$( dirname "$0" )" && pwd )"
console=$dir"/../app/console --env=prod"

echo "Drop database schema"
php $console doctrine:schema:drop --force

echo "Create new schema"
php $console doctrine:schema:update --force

echo "Load SacCe maps"
php $console comppi:build:map sc

echo "Load SacCe interactions"
php $console comppi:build:interactions sc

#echo "Load SacCe localizations"
#php $console comppi:build:localizations sc

