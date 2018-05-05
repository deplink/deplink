FROM php:7.1

COPY . /opt/deplink
WORKDIR /opt/deplink

RUN apt-get update
RUN apt-get install zip unzip -y
RUN apt-get install gcc-multilib g++-multilib -y
RUN apt-get install zlib1g-dev -y && docker-php-ext-install zip

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php -r "if (hash_file('SHA384', 'composer-setup.php') === '544e09ee996cdf60ece3804abc52599c22b1f40f4323403c44d44fdfdd586475ca9813a858088ffbc1f233e9b180f061') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"

RUN echo "phar.readonly = Off" > /usr/local/etc/php/php.ini
RUN sed -i "s/'version' => 'dev-build'/'version' => '(Docker)'/g" config/console.php
RUN php composer.phar run-script build

RUN echo 'php /opt/deplink/bin/deplink.phar $@' > /usr/local/bin/deplink
RUN chmod +x /opt/deplink/bin/deplink.phar
RUN chmod +x /usr/local/bin/deplink

RUN mkdir /root/workspace
WORKDIR /root/workspace
