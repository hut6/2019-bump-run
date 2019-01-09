FROM php:7.2-apache

RUN echo KeepAlive Off   >> /etc/apache2/conf-enabled/keep-alive.conf && \
    echo StartServers 1    >> /etc/apache2/conf-enabled/mpm.conf && \
    echo MinSpareServers 1 >> /etc/apache2/conf-enabled/mpm.conf && \
    echo MaxSpareServers 2 >> /etc/apache2/conf-enabled/mpm.conf

VOLUME tmpfs:/tmp

COPY . /var/www

RUN /var/www/bin/console cache:warmup --no-debug --env=prod --quiet
