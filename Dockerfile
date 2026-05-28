FROM php:8.2-apache

# Habilitar el módulo de reescritura de Apache
RUN a2enmod rewrite

# Decirle a Apache que index.php es el archivo principal por defecto
RUN echo "DirectoryIndex index.php index.html" > /var/www/html/.htaccess

# Copiar los archivos del repositorio al servidor
COPY . /var/www/html/

# Forzar la creación de la carpeta uploads
RUN mkdir -p /var/www/html/uploads

# Asegurar los permisos correctos
RUN chmod -R 777 /var/www/html/uploads

EXPOSE 80
