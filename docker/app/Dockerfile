# NOTE: no `# syntax=` directive on purpose. The bundled BuildKit frontend
# (Engine 23+) supports the heredoc below, and a directive would make every
# build resolve docker/dockerfile:1 from the registry — which fails behind
# a Windows-only credential helper when built from inside the AI-assistant
# container (docker-credential-desktop.exe cannot execute there).  

# App image for the legacy Elgg 2.3.17 baseline (Story S1.1):
# upstream PHP webserver + Composer 2, so composer.json /
# composer.lock work can run inside the orchestration.
#
# Parameterized (Story S1.2): the PHP runtime version enters as the
# PHP_VERSION build arg — Compose supplies the pinned value (compose.yml
# build.args); the default records the version pinned for the current
# journey stage in docs/baseline/baseline.md.
#
# Persistent mount targets (Story S1.3 Step 2): the base image
# pre-creates /var/www/{dataroot,cacheroot,assetroot} owned by
# www-data (33:33) — the web SAPI user — so first-use named-volume
# initialization (Docker copies the image mount point's ownership
# into an empty named volume) inherits the correct ownership. Do not
# add mkdir/chown here; a base-image change must preserve this.
#
# TODO: pin base-image digests before relying on this in CI — resolve with:
#   docker buildx imagetools inspect composer:2
#   docker buildx imagetools inspect coldtrick/elgg_webserver:7.4
# then change both FROM lines to <tag>@sha256:<digest>.

ARG PHP_VERSION=7.4

FROM composer:2 AS composer

FROM coldtrick/elgg_webserver:${PHP_VERSION}

# Layer the Composer 2 phar onto the PHP 7.4 webserver image, plus a
# `composer` wrapper: the base image's php.ini restricts open_basedir to
# the web root, dataroot, /tmp and the cache/asset roots, so the phar at
# /usr/local/bin cannot run directly. The wrapper runs PHP with the
# restriction disabled for the composer CLI only; the web SAPI's
# restriction stays untouched.
COPY --from=composer /usr/bin/composer /usr/local/bin/composer.phar

RUN <<EOF
printf '%s\n' \
    '#!/bin/sh' \
    'exec php -d open_basedir= /usr/local/bin/composer.phar "$@"' \
    > /usr/local/bin/composer
chmod 0755 /usr/local/bin/composer
composer --version
EOF
