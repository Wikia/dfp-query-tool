FROM php:7.3-alpine
COPY --from=composer:1.8.6 /usr/bin/composer /usr/bin/composer

RUN apk add libxml2-dev
RUN docker-php-ext-install soap
RUN docker-php-ext-enable soap

RUN mkdir -p /home/maka/dfp-query-tool
WORKDIR /home/maka/dfp-query-tool
RUN mkdir line-item-presets log config
RUN touch config/queries.yml
COPY composer.json composer.json
COPY composer.lock composer.lock
COPY app app
COPY config/db.sample.yml /home/maka/dfp-query-tool/config/db.yml
COPY k8s-cron-jobs/approve.sh approve.sh
COPY src src
RUN chown -R 65534:65534 /home/maka
RUN chmod +x approve.sh
USER 65534
RUN composer install
