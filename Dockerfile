FROM php:8-apache-buster

# Installiere benötigte Pakete
RUN apt-get update && apt-get install -y cron nano git unzip

# PHP-Erweiterungen installieren
RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable mysqli

# Apache Rewrite Modul aktivieren
RUN a2enmod rewrite

# Installiere Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Installiere SendGrid PHP Bibliothek
WORKDIR /var/www/html
RUN composer require sendgrid/sendgrid

# Cronjob-Dateien kopieren
COPY /cron/cronjob /etc/cron.d/cronjob
COPY /cron/start.sh /home/start.sh

# Setze korrekte Rechte für den Cronjob
RUN chmod 0644 /etc/cron.d/cronjob
RUN chmod +x /home/start.sh

# Füge den Cronjob zur Crontab hinzu
RUN crontab /etc/cron.d/cronjob

# Erstelle das Log-Verzeichnis für den Cronjob
RUN touch /var/log/cron.log

# Web-Dateien kopieren
COPY . /var/www/html

# Apache und Cron mit dem Shell-Skript starten
CMD ["/home/start.sh"]
