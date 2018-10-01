FROM php:7.1

COPY . /opt/deplink
WORKDIR /opt/deplink

RUN apt-get update
RUN apt-get install zip unzip -y
RUN apt-get install gcc-multilib g++-multilib -y
RUN apt-get install zlib1g-dev -y && docker-php-ext-install zip

# https://getcomposer.org/doc/faqs/how-to-install-composer-programmatically.md
RUN curl https://raw.githubusercontent.com/composer/getcomposer.org/d3a6ed2ed96ff423fb1991f22e4bcabd3db662f8/web/installer | php -- --quiet

RUN echo "phar.readonly = Off" > /usr/local/etc/php/php.ini
RUN sed -i "s/'version' => 'dev-build'/'version' => '(Docker)'/g" config/console.php
RUN php composer.phar run-script build

RUN echo 'php /opt/deplink/bin/deplink.phar $@' > /usr/local/bin/deplink
RUN chmod +x /opt/deplink/bin/deplink.phar
RUN chmod +x /usr/local/bin/deplink

RUN mkdir /root/workspace
WORKDIR /root/workspace
