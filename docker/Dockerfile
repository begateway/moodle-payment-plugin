ARG php

FROM moodlehq/moodle-php-apache:$php

ARG version
ARG upstream

ADD https://download.moodle.org/download.php/direct/stable${upstream}/moodle-${version}.tgz /var/www/html/

ADD https://getcomposer.org/installer /var/www/html/composer-setup.php

WORKDIR /var/www/html

RUN cd /var/www/html && \
    tar xfz moodle-${version}.tgz --strip-components=1 && \
    rm moodle-${version}.tgz && \
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    rm composer-setup.php \
    chown -R www-data /var/www/html

VOLUME ["/var/www/html"]
