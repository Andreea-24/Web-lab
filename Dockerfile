FROM php:8.2-apache

# Instalează extensiile PHP necesare pentru MySQL
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Activează mod_rewrite (necesar pentru URL-uri frumoase prin .htaccess)
RUN a2enmod rewrite

# Permite ca .htaccess să suprascrie configurarea Apache (AllowOverride All)
RUN sed -i 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf

# Copiază tot proiectul în folderul Apache (include style/, image/, script.js)
COPY . /var/www/html/

# Permisiuni
RUN chown -R www-data:www-data /var/www/html
