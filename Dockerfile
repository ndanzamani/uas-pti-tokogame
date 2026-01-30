# Menggunakan PHP 8.2 dengan Apache (Server Web)
FROM php:8.2-apache

# Install ekstensi yang dibutuhkan Laravel (Database & Gambar)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql

# Mengaktifkan Mod Rewrite (Agar URL Laravel cantik/tidak error)
RUN a2enmod rewrite

# Mengubah folder root Apache ke folder 'public' Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# Install Composer (Alat manajemen library PHP)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy semua file proyek ke dalam server
WORKDIR /var/www/html
COPY . .

# Install Library Laravel
RUN composer install --no-dev --optimize-autoloader

# Atur Izin Folder (PENTING: Agar Laravel bisa menulis log & cache)
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache