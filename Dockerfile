FROM php:8.2-apache

# Instala as dependências do sistema e a extensão PDO do Postgres
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Copia todos os arquivos da raiz (financeiro/) para a pasta do servidor
COPY . /var/www/html/

RUN a2enmod rewrite
EXPOSE 80