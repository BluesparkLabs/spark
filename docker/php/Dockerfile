FROM wodby/drupal-php:7.1-4.4.1

USER root

RUN mkdir -p /opt/drupalextension
ADD composer.json /opt/drupalextension
RUN composer install -d /opt/drupalextension

USER wodby
