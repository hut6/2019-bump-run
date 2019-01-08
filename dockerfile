FROM php:7.2-apache

VOLUME tmpfs:/tmp

COPY . /var/www

