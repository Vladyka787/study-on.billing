#!/bin/bash
cd ~/sf-lessons/study-on.billing
docker-compose exec php bin/console payment:report
#docker-compose down
echo 'Success'
