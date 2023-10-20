FROM composer:2 as Builder

WORKDIR /opt/guides
COPY . /opt/guides

RUN composer install --no-dev --no-interaction --no-progress  \
    --no-suggest --optimize-autoloader --classmap-authoritative

FROM php:8.2-cli-alpine
COPY . /opt/guides
WORKDIR /opt/guides

COPY --from=Builder /opt/guides/.Build /opt/guides/.Build
RUN cp guides.xml .Build/guides.xml

WORKDIR /project
ENTRYPOINT ["/opt/guides/.Build/bin/guides"]
CMD ["-h"]
