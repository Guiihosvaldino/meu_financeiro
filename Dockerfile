FROM php:8.2-apache
# Copia tudo para o servidor
COPY . /var/www/html/
# Habilita o módulo de reescrita do Apache (útil para APIs)
RUN a2enmod rewrite
EXPOSE 80
RUN docker-php-ext-install pdo pdo_mysql