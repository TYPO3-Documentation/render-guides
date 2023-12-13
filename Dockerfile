FROM composer:2 as Builder

WORKDIR /opt/guides
COPY . /opt/guides

RUN composer install --no-dev --no-interaction --no-progress  \
    --no-suggest --optimize-autoloader --classmap-authoritative

FROM php:8.1-cli-alpine
COPY . /opt/guides
WORKDIR /opt/guides

COPY --from=Builder /opt/guides/vendor /opt/guides/vendor
RUN echo "memory_limit=1G" >> /usr/local/etc/php/conf.d/typo3.ini

ARG TYPO3AZUREEDGEURIVERSION
ENV TYPO3AZUREEDGEURIVERSION=$TYPO3AZUREEDGEURIVERSION

WORKDIR /project
ENTRYPOINT [ "/opt/guides/entrypoint.sh" ]
CMD ["-h"]
