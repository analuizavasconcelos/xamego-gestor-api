FROM richarvey/nginx-php-fpm:3.1.6

COPY . /var/www/html

# Configuração da imagem base (nginx + php-fpm já vêm prontos)
ENV SKIP_COMPOSER=1
ENV WEBROOT=/var/www/html/public
ENV PHP_ERRORS_STDERR=1
ENV RUN_SCRIPTS=1
ENV REAL_IP_HEADER=1
ENV COMPOSER_ALLOW_SUPERUSER=1

# Configuração do Laravel em produção
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr

RUN chmod +x /var/www/html/scripts/00-laravel-deploy.sh

CMD ["/start.sh"]