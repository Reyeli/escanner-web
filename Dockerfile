FROM php:8.2-apache

# Habilitar el módulo de reescritura de Apache por si acaso
RUN a2enmod rewrite

# Copiar todos los archivos del repositorio dentro de la carpeta del servidor web
COPY . /var/www/html/

# Asegurar los permisos correctos para que PHP pueda escribir en "uploads"
RUN chmod -R 777 /var/www/html/uploads

EXPOSE 80
