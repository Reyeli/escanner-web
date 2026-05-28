FROM php:8.2-apache

# Habilitar el módulo de reescritura de Apache
RUN a2enmod rewrite

# Copiar los archivos del repositorio al servidor
COPY . /var/www/html/

# NUEVO: Forzar la creación de la carpeta uploads por si Git la ignoró
RUN mkdir -p /var/www/html/uploads

# Asegurar los permisos correctos para que PHP pueda escribir los PDFs
RUN chmod -R 777 /var/www/html/uploads

EXPOSE 80
