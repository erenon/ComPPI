#!/bin/bash

#Builds database

console="../app/console"

echo "Generatre entities"
php $console comppi:load:entities

echo "Add set/get methods..."
php $console doctrine:generate:entities LoaderBundle
echo "..remove backup entities"
find ../src/Comppi/LoaderBundle/Entity -name "~*" | xargs rm

echo "Drop database schema"
php $console doctrine:schema:drop --force

echo "Create new schema"
php $console doctrine:schema:update --force

echo "Load plaintext databases"
php $console comppi:load:database
