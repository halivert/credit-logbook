#!/bin/sh
set -e

# Configuration comes from the environment at runtime, so caches are built
# when the container starts rather than in the image.
php artisan config:cache
# No route:cache yet: Breeze and Fortify register routes with the same
# names, which can't be cached.
php artisan view:cache

exec "$@"
