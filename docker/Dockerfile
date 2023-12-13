ARG php

FROM moodlehq/moodle-php-apache:$php

ARG version
ARG upstream

ADD https://download.moodle.org/download.php/direct/stable${upstream}/moodle-${version}.tgz /var/www/html/

WORKDIR /var/www/html

RUN cd /var/www/html && \
    tar xfz moodle-${version}.tgz --strip-components=1 && \
    rm moodle-${version}.tgz && \
    chown -R www-data /var/www/html

VOLUME /var/www/html
