FROM php:8.2-apache

# Cambiar el DocumentRoot de Apache explícitamente a /var/www/html
RUN sed -ri -e 's!/var/www/html!/var/www/html!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!/var/www/!g' /etc/apache2/apache2.conf

# Asegurar que Apache busque index.php primero
RUN echo "DirectoryIndex index.php index.html" > /var/www/html/.htaccess

# Copiar todos los archivos de tu repositorio al directorio raíz
COPY . /var/www/html/

# Forzar la creación de la carpeta uploads con permisos globales
RUN mkdir -p /var/www/html/uploads && chmod -R 777 /var/www/html/uploads

# Asegurar que Apache sea el dueño de los archivos para que no haya bloqueos
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
