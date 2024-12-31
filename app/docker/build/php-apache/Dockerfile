FROM php:8.2-apache AS baseimage

SHELL ["/bin/bash", "-c"]

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN <<EOF
# ERROR HANDLING
set -o pipefail # trace ERR through pipes
set -o errtrace # trace ERR through 'time command' and other functions
set -o nounset  ## set -u : exit the script if you try to use an uninitialised variable
set -o errexit  # stop on non zero return code
trap "exit" SIGHUP SIGINT SIGQUIT SIGABRT SIGTERM

mkdir -p /var/www/juvem/var/cache
mkdir -p /var/www/juvem/var/log
chown -R www-data:www-data /var/www/juvem
chmod -R 777 /var/www/juvem/var

apt-get clean
apt update
apt dist-upgrade -y

# libfcgi-bin: needed for healthcheck with cgi-fcgi
apt install -y \
	curl \
	unzip \
	wget \
	zip \
	libfcgi-bin \
	default-mysql-client

apt autoremove -y
rm -rf /var/lib/apt/lists/
chmod +x /usr/local/bin/install-php-extensions

IPE_GD_WITHOUTAVIF=1 install-php-extensions \
	bcmath \
	curl \
	gd \
	gmp \
	iconv \
	intl \
	mbstring \
	opcache \
	pdo \
	pdo_mysql \
	pcntl \
	xml \
	xmlwriter \
	simplexml \
	xsl \
	zip \
	apcu \
    exif \
    fileinfo \
    openssl \
    imap

EOF

RUN a2enmod ssl rewrite headers

COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

COPY 000-default.conf /etc/apache2/sites-available/000-default.conf

ENV APACHE_DOCUMENT_ROOT /var/www/juvem/app/web

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

FROM baseimage AS development
RUN <<EOF
# ERROR HANDLING
set -o pipefail # trace ERR through pipes
set -o errtrace # trace ERR through 'time command' and other functions
set -o nounset  ## set -u : exit the script if you try to use an uninitialised variable
set -o errexit  # stop on non zero return code
trap "exit" SIGHUP SIGINT SIGQUIT SIGABRT SIGTERM

install-php-extensions xdebug
composer global config --no-plugins allow-plugins.pyrech/composer-changelogs true
composer global require --no-progress pyrech/composer-changelogs
mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
EOF

COPY php_prod.ini $PHP_INI_DIR/conf.d/98-app.ini
COPY php_dev.ini $PHP_INI_DIR/conf.d/99-dev.ini

WORKDIR /var/www/juvem

FROM baseimage AS production

FROM development AS testing
RUN <<EOF
# ERROR HANDLING
set -o pipefail # trace ERR through pipes
set -o errtrace # trace ERR through 'time command' and other functions
set -o nounset  ## set -u : exit the script if you try to use an uninitialised variable
set -o errexit  # stop on non zero return code
trap "exit" SIGHUP SIGINT SIGQUIT SIGABRT SIGTERM


# libfcgi-bin: needed for healthcheck with cgi-fcgi
apt install -y openjdk-8-jre openjdk-8-jre-headless libreoffice-core libreoffice-writer libreoffice-java-common --no-install-recommends

EOF
