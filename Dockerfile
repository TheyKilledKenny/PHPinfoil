FROM php:8.4-apache
COPY --chown=www-data PHPinfoil.php /var/www/html/index.php
