FROM php:8.2-cli
COPY . /opt/guides
WORKDIR /opt/guides

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install \
    && cp guides.xml .Build/guides.xml

WORKDIR /project
ENTRYPOINT ["/opt/guides/.Build/bin/guides"]
CMD ["-h"]
