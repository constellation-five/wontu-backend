#!/bin/bash
cp /home/site/wwwroot/default /etc/nginx/sites-available/default

nohup php /home/site/wwwroot/artisan queue:work > /home/site/wwwroot/storage/logs/queue.log 2>&1 &

nohup php /home/site/wwwroot/artisan reverb:start --host=127.0.0.1 --port=8123 > /home/site/wwwroot/storage/logs/reverb.log 2>&1 &
