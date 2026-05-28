FROM php:8.2-apache

# Copiar todo el contenido de tu GitHub al servidor
COPY . /var/www/html/

# Forzar la creación de uploads por si acaso
RUN mkdir -p /var/www/html/uploads

# --- ESTO VA A MOSTRAR EN LOS LOGS QUÉ ARCHIVOS HAY REALMENTE ---
RUN echo "=== ARCHIVOS EN LA RAÍZ ===" && ls -la /var/www/html/

EXPOSE 80
