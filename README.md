# Voice Notes App

## Get started

1. Obtain the callback URL. It should be entered in place of YOURCALLBACKPREFIX below. If you are operating locally, set up ngrok (`ngrok http 80`) and use the ngrok callback URL.
2. Obtain a Rev AI access token. It should be entered in place of YOURTOKEN below.
3. If using an external MongoDB database, replace the default MongoDB credentials below.
4. Clone this repository and run commands below depending on how you would like to use the application.

### for prod/test (all source code and dependencies copied into image)
```
cp config/settings.php.dist config/settings.php
sed -i "s/<TOKEN>/YOURTOKEN/g" config/settings.php
sed -i "s,<CALLBACK-PREFIX>,YOURCALLBACKPREFIX,g" config/settings.php
sed -i "s,<MONGODB-URI>,mongodb://myuser:mypassword@db/,g" config/settings.php
docker-compose up -d
```

### for dev (local source code mounted as container volume)
```
cp config/settings.php.dist config/settings.php
sed -i "s/<TOKEN>/YOURTOKEN/g" config/settings.php
sed -i "s,<CALLBACK-PREFIX>,YOURCALLBACKPREFIX,g" config/settings.php
sed -i "s,<MONGODB-URI>,mongodb://myuser:mypassword@db/,g" config/settings.php
docker-compose -f docker-compose.dev.yml up -d
docker exec voicenotes_app_1 composer install
```

Browse to http://YOURDOCKERHOST/index to use the application.
