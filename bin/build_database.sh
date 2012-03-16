#!/bin/bash

#Builds database

dir="$( cd "$( dirname "$0" )" && pwd )"
console=$dir"/../app/console --env=test"

#echo "Generatre entities"
#php $console comppi:load:entities

#echo "Add set/get methods..."
#php $console doctrine:generate:entities LoaderBundle --no-backup

#echo "Drop database schema"
#php $console doctrine:schema:drop --force

#echo "Create new schema"
#php $console doctrine:schema:update --force

#echo "Load plaintext databases"
#php $console comppi:load:database

echo "Drop database schema"
php $console doctrine:schema:drop --force

echo "Create new schema"
php $console doctrine:schema:update --force

echo "Load SacCe maps"
php $console comppi:build:map sc

echo "Load SacCe interactions"
php $console comppi:build:interactions sc

echo "Load SacCe localizations"
php $console comppi:build:localizations sc
