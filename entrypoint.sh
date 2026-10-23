#!/bin/bash
# Fallback to port 80 if PORT is not provided by Hostless
PORT="${PORT:-80}"

# Dynamically update Apache configuration to listen on the Hostless PORT
sed -i "s/Listen [0-9]*/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/:[0-9]*>/:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

# Execute the main container command (apache2-foreground)
exec "$@"