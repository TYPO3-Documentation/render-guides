FROM php:8.2-cli
COPY . /opt/guides
WORKDIR /opt/guides

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install
WORKDIR /project
ENTRYPOINT ["/opt/guides/.Build/bin/guides"]
CMD ["-h"]
