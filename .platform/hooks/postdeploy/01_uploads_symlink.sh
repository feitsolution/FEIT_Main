#!/bin/bash
set -e

SHARED="/var/app/shared/uploads"

mkdir -p $SHARED
chown -R webapp:webapp $SHARED
chmod -R 775 $SHARED

for APP in lily_collection order_management quick_start
do
  DIST="/var/app/current/$APP/dist/uploads"

  rm -rf $DIST
  ln -s $SHARED $DIST
done
