FROM wodby/drupal-php:7.1-4.4.1

USER root

# Add local certificate for cURL. See https://curl.haxx.se/docs/caextract.html.
RUN mkdir /usr/local/etc/curl;
ADD curl/cacert--2018-03-07.pem /usr/local/etc/curl/cacert.pem
ADD curl/docker-php-ext-curl.ini "${PHP_INI_DIR}/conf.d"
RUN chown wodby:wodby "${PHP_INI_DIR}/conf.d/docker-php-ext-curl.ini"

# Install Behat Drupal extension.
RUN mkdir -p /opt/drupalextension
ADD composer.json /opt/drupalextension
ADD composer.lock /opt/drupalextension
RUN composer install -d /opt/drupalextension

USER wodby
