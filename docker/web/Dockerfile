# This docker image will be created from the official Docker PHP 7 image with Apache
FROM php:7-apache

# Copy PHP configuration file to comply with Symfony requirements
COPY ./config/php.ini /usr/local/etc/php/

# Copy Apache virtualhost config file
COPY ./config/000-default.conf /etc/apache2/sites-available/

# Download, compile and install ICU library required by intl PHP extension
RUN curl -sS -o /tmp/icu.tar.gz -L http://download.icu-project.org/files/icu4c/58.2/icu4c-58_2-src.tgz && \
    tar -zxf /tmp/icu.tar.gz -C /tmp && \
    cd /tmp/icu/source && \
    ./configure --prefix=/usr/local && \
    make && \
    make install

# Delete ICU source files from /tmp directory
RUN rm /tmp/icu.tar.gz
RUN rm -r /tmp/icu

# Install PHP extensions required or recommended by Symfony (intl and opcache)
RUN docker-php-ext-configure intl --with-icu-dir=/usr/local && \
    docker-php-ext-install intl opcache

# Install PHP MySQL PDO driver
RUN docker-php-ext-install pdo_mysql

# Enable mod_rewrite module on Apache to make .htaccess files work
RUN a2enmod rewrite

# Update apt-get and install tools required by Composer
RUN apt-get -y update
RUN apt-get -y install git
RUN apt-get -y install zip

# Install Composer globally
WORKDIR /tmp
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php -r "if (hash_file('SHA384', 'composer-setup.php') === '669656bab3166a7aff8a7506b8cb2d1c292f042046c5a994c43155c0be6190fa0355160742ab2e1c88d40d5be660b410') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"
RUN echo "Moving composer to /usr/local/bin/composer" && mv composer.phar /usr/local/bin/composer
WORKDIR /var/www/html