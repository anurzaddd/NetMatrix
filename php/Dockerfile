FROM php:8.2-apache

# نصب افزونه‌های PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN docker-php-ext-enable mysqli

# فعال‌سازی mod_rewrite
RUN a2enmod rewrite

# کپی فایل‌ها
COPY . /var/www/html/

# تغییر دسترسی‌ها
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# نصب Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

EXPOSE 80
