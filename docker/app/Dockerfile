# syntax=docker/dockerfile:1

# App image for the legacy Elgg 2.3.17 baseline (Story S1.1):
# upstream PHP 7.4 webserver + Composer 2, so composer.json /
# composer.lock work can run inside the orchestration.
#
# TODO: pin base-image digests before relying on this in CI — resolve with:
#   docker buildx imagetools inspect composer:2
#   docker buildx imagetools inspect coldtrick/elgg_webserver:7.4
# then change both FROM lines to <tag>@sha256:<digest>.

FROM composer:2 AS composer

FROM coldtrick/elgg_webserver:7.4

# Layer the Composer 2 binary onto the PHP 7.4 webserver image.
COPY --from=composer /usr/bin/composer /usr/local/bin/composer

# Build-time smoke check: binary landed and runs on this PHP runtime.
RUN composer --version
