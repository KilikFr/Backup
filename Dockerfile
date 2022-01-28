FROM php:8.0-cli

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        openssh-client \
        rsync \
        curl \
    && apt-get clean \
    && rm -Rf /var/lib/apt/lists/* /usr/share/man/* /usr/share/doc/*

COPY build/backup.phar /usr/local/bin/backup.phar
WORKDIR /usr/local/bin

COPY run.sh /run.sh
RUN chmod +x /run.sh

ENTRYPOINT ["/run.sh"]

