#!/bin/bash
cp /home/site/wwwroot/default /etc/nginx/sites-available/default

cd /home/site/wwwroot
mkdir -p storage/framework/{cache/data,sessions,testing,views} storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache

php artisan migrate --force --no-interaction || echo "WARNING: migration step failed, check logs above. Continuing app startup."

php artisan storage:link

service nginx reload

nohup php /home/site/wwwroot/artisan queue:work > /home/site/wwwroot/storage/logs/queue.log 2>&1 &

nohup php /home/site/wwwroot/artisan schedule:work > /home/site/wwwroot/storage/logs/schedule.log 2>&1 &

nohup php /home/site/wwwroot/artisan reverb:start --host=127.0.0.1 --port=8123 > /home/site/wwwroot/storage/logs/reverb.log 2>&1 &
