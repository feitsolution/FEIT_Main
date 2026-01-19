#!/bin/bash
set -e

SHARED="/var/app/shared/uploads"
BUCKET="your-company-branding-uploads"
S3_PATH="uploads"

# Create shared uploads directory
mkdir -p "$SHARED"
chown -R webapp:webapp "$SHARED"
chmod -R 775 "$SHARED"

# Sync from S3 to local shared folder
/usr/bin/aws s3 sync "s3://$BUCKET/$S3_PATH" "$SHARED"

# Symlink uploads directory for each app
for APP in lily_collection order_management quick_start
do
  DIST="/var/app/current/$APP/dist/uploads"
  rm -rf "$DIST"
  ln -s "$SHARED" "$DIST"
done
