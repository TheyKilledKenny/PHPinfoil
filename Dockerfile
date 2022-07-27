FROM php:7.2-apache
COPY --chown=www-data PHPinfoil.php /var/www/html/index.php
