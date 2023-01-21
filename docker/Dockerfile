ARG version
ARG php

FROM moodlehq/moodle-php-apache:$php

ADD https://download.moodle.org/download.php/direct/stable30/moodle-3.0.10.tgz /var/www/html/

WORKDIR ["/var/www/html"]

RUN cd /var/www/html && \
    tar xfz moodle-3.0.10.tgz --strip-components=1 && \
    rm moodle-3.0.10.tgz && \
    chown -R www-data /var/www/html

VOLUME ["/var/www/html"]
