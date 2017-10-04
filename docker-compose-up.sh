#!/bin/bash
set -e
source ./.env

if [ ! -d "Packages" ]; then
  composer install
fi

docker-compose up -d --remove-orphans
echo "Copying files…"
docker cp . "${BEACH_DEV_PROJECT_NAME}_php_1:/application/"
echo "Changing permissions…"
docker exec "${BEACH_DEV_PROJECT_NAME}_php_1" /bin/bash -c "chown -R beach:beach /application"
echo "Cleaning up Data/Temporary…"
docker exec "${BEACH_DEV_PROJECT_NAME}_php_1" /bin/bash -c "rm -rf /application/Data/Temporary"
echo "Migrating database…"
docker exec "${BEACH_DEV_PROJECT_NAME}_sidecar_1" /bin/bash -c "cd /application; ./flow doctrine:migrate"

echo "You can now open http://localhost:4080 in a browser. Have a neos day!"
