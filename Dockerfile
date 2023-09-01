FROM php:8.2-cli
COPY . /var/www/html
WORKDIR /var/www/html

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install
ENTRYPOINT [".Build/bin/guides"]
CMD ["-h"]
