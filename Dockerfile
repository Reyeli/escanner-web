FROM php:8.2-apache

# Habilitar el módulo de reescritura de Apache por si acaso
RUN a2enmod rewrite

# Instalar y habilitar la extensión mysqli
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Configurar las directivas de subida pesada para las ráfagas de fotos de alta calidad
RUN echo "upload_max_filesize = 100M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini

# Cambiar la prioridad global de Apache para que busque primero index.php antes que index.html
RUN sed -i 's/DirectoryIndex index.html index.cgi index.pl index.php index.xhtml index.htm/DirectoryIndex index.php index.html/g' /etc/apache2/mods-enabled/dir.conf

# Copiar todos los archivos de tu repositorio directo a la carpeta del servidor
COPY . /var/www/html/

# Crear la carpeta obligatoria para los PDFs temporales con permisos totales de escritura
RUN mkdir -p /var/www/html/uploads && chmod -R 777 /var/www/html/uploads

EXPOSE 80
