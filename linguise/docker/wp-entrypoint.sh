#!/bin/sh
set -eu

# Let the official image initialize the persistent WordPress document root first.
/usr/local/bin/docker-entrypoint.sh true

plugin_dir=/var/www/html/wp-content/plugins/linguise
mkdir -p "$plugin_dir"
# Fingerprint of the composer.lock that was resolved while the image was built
# (see Dockerfile.dev); it changes whenever the dependency tree changes.
vendor_fingerprint=$(cat /usr/src/linguise/vendor.fingerprint)
installed_fingerprint=
if [ -f "$plugin_dir/.composer-lock-hash" ]; then
  installed_fingerprint=$(cat "$plugin_dir/.composer-lock-hash")
fi
if [ "$vendor_fingerprint" != "$installed_fingerprint" ]; then
  mkdir -p "$plugin_dir/vendor"
  cp -a /usr/src/linguise/vendor/. "$plugin_dir/vendor/"
  printf '%s\n' "$vendor_fingerprint" > "$plugin_dir/.composer-lock-hash"
fi

exec /usr/local/bin/docker-entrypoint.sh "$@"