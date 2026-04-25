FROM php:8.2-apache

# Instalează extensiile PHP necesare pentru MySQL
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Activează mod_rewrite (util pentru URL-uri frumoase)
RUN a2enmod rewrite

# Copiază fișierele proiectului în folderul Apache
COPY src/ /var/www/html/

# Permisiuni
RUN chown -R www-data:www-data /var/www/html
