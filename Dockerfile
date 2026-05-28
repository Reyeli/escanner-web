FROM php:8.2-apache

# Habilitar el módulo de reescritura de Apache
RUN a2enmod rewrite

# Configurar Apache para que acepte index.php como archivo principal y permita leer la raíz
RUN sed -i 's/DirectoryIndex index.html index.cgi index.pl index.php index.xhtml index.htm/DirectoryIndex index.php index.html/g' /etc/apache2/mods-enabled/dir.conf

# Copiar los archivos del repositorio al directorio del servidor web
COPY . /var/www/html/

# Forzar la creación de la carpeta uploads
RUN mkdir -p /var/www/html/uploads

# Asegurar los permisos correctos en todo el directorio
RUN chmod -R 777 /var/www/html/uploads

EXPOSE 80
