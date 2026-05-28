FROM php:8.2-apache

# Habilitar el módulo de reescritura de Apache
RUN a2enmod rewrite

# Instalar y habilitar la extensión mysqli
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# NUEVO: Aumentar los límites de tamaño de POST y Archivos en PHP a 100MB
RUN echo "upload_max_filesize = 100M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini

# Configurar Apache para que acepte index.php como archivo principal
RUN sed -i 's/DirectoryIndex index.html index.cgi index.pl index.php index.xhtml index.htm/DirectoryIndex index.php index.html/g' /etc/apache2/mods-enabled/dir.conf

# Copiar los archivos del repositorio al directorio del servidor web
COPY . /var/www/html/

# Forzar la creación de la carpeta uploads con permisos globales
RUN mkdir -p /var/www/html/uploads && chmod -R 777 /var/www/html/uploads

EXPOSE 80
