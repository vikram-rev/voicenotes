# Voice Notes App

## Requirements

1. Rev AI access token, available at [https://rev.ai/auth/signup](https://rev.ai/auth/signup).
2. ngrok (for local development only)

## Setup

1. Add the Rev AI access token as the value for the env var REVAI_ACCESS_TOKEN in the `docker-compose.yml` file.
2. Add the callback URL as the value for the env var CALLBACK_PREFIX in the `docker-compose.yml` file. If you are working locally, configure an ngrok tunnel (`ngrok http 80`) and use the ngrok callback URL.
3. If using an external MongoDB database, set the MONGODB_URI env var in the `docker-compose.yml` file.
4. Clone this repository and run commands below depending on how you would like to use the application.

### for prod/test (all source code and dependencies copied into image)
```
docker-compose up -d
```

### for local dev (local source code mounted as container volume)
```
docker-compose -f docker-compose.dev.yml up -d
docker exec voicenotes_app composer install
```

Browse to http://YOURDOCKERHOST/index to use the application.
