FROM php:8.2-apache
COPY ./frontend/ /var/www/html/
EXPOSE 80