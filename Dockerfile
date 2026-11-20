FROM php:8.2-apache
RUN apt-get update && apt-get install -y curl && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-install pdo pdo_mysql
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html
USER www-data
EXPOSE 80
ENV INSTANCE_ID=unknown
