FROM php:8.5-cli-alpine AS builder

COPY --from=ghcr.io/php/pie:bin /pie /usr/bin/pie
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apk add --update $PHPIZE_DEPS patch git
RUN pie install arnaud-lb/inotify && docker-php-ext-install pcntl

WORKDIR /opt/guides
COPY . /opt/guides

RUN composer install --no-dev --no-interaction --no-progress \
    --no-suggest --optimize-autoloader --classmap-authoritative

# Apply performance patches for guides-restructured-text
# Use --forward to skip already-applied hunks, --reject-file=/dev/null to suppress reject files
RUN cd /opt/guides/vendor/phpdocumentor/guides-restructured-text && \
    patch -p1 --forward --reject-file=/dev/null < /opt/guides/patches/inline-parser-lexer-reuse.patch || true && \
    patch -p1 --forward --reject-file=/dev/null < /opt/guides/patches/line-checker-cache.patch || true && \
    patch -p1 --forward --reject-file=/dev/null < /opt/guides/patches/buffer-unindent-cache.patch || true && \
    patch -p1 --forward --reject-file=/dev/null < /opt/guides/patches/inline-lexer-regex-cache.patch || true && \
    patch -p1 --forward --reject-file=/dev/null < /opt/guides/patches/field-list-regex-cache.patch || true && \
    patch -p1 --forward --reject-file=/dev/null < /opt/guides/patches/directive-rule-regex-cache.patch || true && \
    patch -p1 --forward --reject-file=/dev/null < /opt/guides/patches/enumerated-list-regex-cache.patch || true && \
    patch -p1 --forward --reject-file=/dev/null < /opt/guides/patches/link-rule-regex-cache.patch || true && \
    patch -p1 --forward --reject-file=/dev/null < /opt/guides/patches/grid-table-rule-regex-cache.patch || true && \
    patch -p1 --forward --reject-file=/dev/null < /opt/guides/patches/simple-table-rule-regex-cache.patch || true

# Apply performance patches for guides
RUN cd /opt/guides/vendor/phpdocumentor/guides && \
    patch -p1 --forward --reject-file=/dev/null < /opt/guides/patches/external-reference-resolver-cache.patch || true

FROM php:8.5-cli-alpine

COPY --from=ghcr.io/php/pie:bin /pie /usr/bin/pie
RUN apk add --update $PHPIZE_DEPS
RUN pie install arnaud-lb/inotify && docker-php-ext-install pcntl

RUN apk del $PHPIZE_DEPS && rm -rf /var/cache/apk/* /tmp/* /usr/share/php/* /usr/local/lib/php/doc/* /usr/local/lib/php/test/*

COPY . /opt/guides
WORKDIR /opt/guides

COPY --from=builder /opt/guides/vendor /opt/guides/vendor
RUN echo "memory_limit=4G" >> /usr/local/etc/php/conf.d/typo3.ini
RUN echo "error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT" >> /usr/local/etc/php/conf.d/typo3.ini
RUN echo "opcache.enable_cli=1" >> /usr/local/etc/php/conf.d/typo3.ini
RUN echo "opcache.interned_strings_buffer=16" >> /usr/local/etc/php/conf.d/typo3.ini

ARG TYPO3AZUREEDGEURIVERSION
ENV TYPO3AZUREEDGEURIVERSION=$TYPO3AZUREEDGEURIVERSION

WORKDIR /project
ENTRYPOINT [ "/opt/guides/entrypoint.sh" ]
CMD ["-h"]

RUN apk add --no-cache \
    git
