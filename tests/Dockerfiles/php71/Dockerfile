FROM php:7.1-cli

RUN apt-get update && \
          apt-get install curl zip
RUN curl -o /tmp/composer-setup.php https://getcomposer.org/installer \
      && curl -o /tmp/composer-setup.sig https://composer.github.io/installer.sig \
      # Make sure we're installing what we think we're installing!
      && php -r "if (hash('SHA384', file_get_contents('/tmp/composer-setup.php')) !== trim(file_get_contents('/tmp/composer-setup.sig'))) { unlink('/tmp/composer-setup.php'); echo 'Invalid installer' . PHP_EOL; exit(1); }" \
      && php /tmp/composer-setup.php --no-ansi --install-dir=/usr/local/bin --filename=composer --snapshot \
      && rm -f /tmp/composer-setup.*
RUN composer --version

WORKDIR /bt/braintreehttp-php
COPY ./composer.json .

RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction
RUN COMPOSER_ALLOW_SUPERUSER=1 composer update

COPY . . 
