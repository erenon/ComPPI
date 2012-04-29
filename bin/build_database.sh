#!/bin/bash

#Builds database

dir="$( cd "$( dirname "$0" )" && pwd )"
console=$dir"/../app/console --env=test"

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

echo "Load Human maps"
php $console comppi:build:map hs

echo "Load Human interactions"
php $console comppi:build:interactions hs

echo "Load Human localizations"
php $console comppi:build:localizations hs

echo "Load Drosi maps"
php $console comppi:build:map dm

echo "Load Drosi interactions"
php $console comppi:build:interactions dm

echo "Load Drosi localizations"
php $console comppi:build:localizations dm

echo "Load C'Elegans maps"
php $console comppi:build:map ce

echo "Load C'Elegans interactions"
php $console comppi:build:interactions ce

echo "Load C'Elegans localizations"
php $console comppi:build:localizations ce

