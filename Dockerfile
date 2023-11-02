FROM composer:2 as Builder

WORKDIR /opt/guides
COPY . /opt/guides

RUN composer install --no-dev --no-interaction --no-progress  \
    --no-suggest --optimize-autoloader --classmap-authoritative

FROM php:8.2-cli-alpine
COPY . /opt/guides
WORKDIR /opt/guides

COPY --from=Builder /opt/guides/vendor /opt/guides/vendor

WORKDIR /project
ENTRYPOINT [ "/opt/guides/entrypoint.sh", "/opt/guides/vendor/bin/guides"]
CMD ["-h"]
