# Voice Notes

## Requirements

1. Rev AI access token, available at [https://rev.ai/auth/signup](https://rev.ai/auth/signup).
2. ngrok (for local development only)

## Setup

1. Clone this repository.
2. Add an `.env` file with the following contents:

    ```
    REVAI_ACCESS_TOKEN=
    CALLBACK_PREFIX=
    MONGODB_URI=mongodb://myuser:mypassword@db/
    ```

3. Add the Rev AI access token as the value for the env var `REVAI_ACCESS_TOKEN`.
4. Add the callback URL as the value for the env var `CALLBACK_PREFIX`. If you are working locally, configure an ngrok tunnel (`ngrok http 80`) and use the ngrok callback URL.
5. If using an external MongoDB database, modify the `MONGODB_URI` env var.
6. Run commands below depending on how you would like to use the application.

    ```
    ### for prod/test (all source code and dependencies copied into image)
    docker-compose up -d

    ### for local dev (local source code mounted as container volume)
    docker-compose -f docker-compose.dev.yml up -d
    docker exec voicenotes_app composer install
    ```

7. Browse to `http://YOURDOCKERHOST/index` to use the application.
