FROM php:8.2-apache

# Instala a extensão para PostgreSQL em vez de MySQL
RUN apt-get update && apt-get install -y libpq-dev && docker-php-ext-install pdo pdo_pgsql

# O restante do seu Dockerfile (copiando os arquivos e ajustando a raiz)
COPY . /var/www/html/
ENV APACHE_DOCUMENT_ROOT /var/www/html/frontend
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN a2enmod rewrite
EXPOSE 80