#!/bin/sh
set -e

# check for local configuration file
# create if not already present
echo "Checking for configuration..."
#if [ ! -f /var/www/config/settings.php ]; then
#	echo "No configuration file found."
	echo "Creating configuration file..."
	cp /var/www/config/settings.php.dist /var/www/config/settings.php
	sed -i "s,<REVAI_ACCESS_TOKEN>,${REVAI_ACCESS_TOKEN},g" /var/www/config/settings.php
	sed -i "s,<CALLBACK_PREFIX>,$CALLBACK_PREFIX,g" /var/www/config/settings.php
	sed -i "s,<MONGODB_URI>,$MONGODB_URI,g" /var/www/config/settings.php
	echo "Configuration file created."
#else
#	echo "Configuration file found, skipping."
#fi

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
	set -- php "$@"
fi

exec "$@"