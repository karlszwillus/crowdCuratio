FROM ubuntu:24.04

LABEL maintainer="Taylor Otwell"

ARG WWWGROUP

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND noninteractive
ENV TZ=UTC

# F-DOCK-002: Composer aus dem offiziellen Composer-Image übernehmen statt
# `curl http://getcomposer.org/installer | php` zur Buildzeit auszuführen.
# Vorteile: deterministische Composer-Version, signierte Distribution,
# keine HTTP-Download-Pipe als MITM-Angriffsfläche im Build.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# F-DOCK-011: `apt-key` ist seit Ubuntu 22.04 deprecated und in 24.04 entfernt.
# Wir legen die GPG-Keys explizit als signed-by-Keyrings unter
# /etc/apt/keyrings ab und referenzieren sie in den .list-Dateien.
# Das macht jeden Repo-Eintrag an genau einen Schlüssel gebunden — kein
# globaler Trust-Store für alle Repos mehr.
RUN apt-get update \
    && apt-get install -y gnupg gosu curl ca-certificates zip unzip git supervisor sqlite3 libcap2-bin libpng-dev \
    && mkdir -p /etc/apt/keyrings ~/.gnupg \
    && chmod 600 ~/.gnupg \
    && echo "disable-ipv6" >> ~/.gnupg/dirmngr.conf \
    && gpg --no-default-keyring --keyring /etc/apt/keyrings/ondrej-php.gpg \
        --keyserver hkp://keyserver.ubuntu.com:80 \
        --recv-keys E5267A6C C300EE8C \
    && echo "deb [signed-by=/etc/apt/keyrings/ondrej-php.gpg] http://ppa.launchpad.net/ondrej/php/ubuntu noble main" \
        > /etc/apt/sources.list.d/ppa_ondrej_php.list \
    && apt-get update \
    && apt-get install -y php8.3-cli php8.3-dev \
       php8.3-pgsql php8.3-sqlite3 php8.3-gd \
       php8.3-curl php8.3-memcached \
       php8.3-imap php8.3-mysql php8.3-mbstring \
       php8.3-xml php8.3-zip php8.3-bcmath php8.3-soap \
       php8.3-intl php8.3-readline \
       php8.3-msgpack php8.3-igbinary php8.3-ldap \
       php8.3-redis \
       php8.3-pcov \
    && curl -sL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg \
        | gpg --dearmor -o /etc/apt/keyrings/yarn.gpg \
    && echo "deb [signed-by=/etc/apt/keyrings/yarn.gpg] https://dl.yarnpkg.com/debian/ stable main" \
        > /etc/apt/sources.list.d/yarn.list \
    && apt-get update \
    && apt-get install -y yarn \
    && apt-get install -y mysql-client \
    && apt-get install -y postgresql-client \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN setcap "cap_net_bind_service=+ep" /usr/bin/php8.3

# Ubuntu 24.04 noble bringt einen vorinstallierten `ubuntu`-User mit
# UID 1000 mit. Wenn WWWGROUP=1000 ist (Default), kollidiert die
# `groupadd`-Zeile unten ohne diesen Cleanup. `userdel`/`groupdel`
# mit `|| true` ist idempotent — wenn der User nicht existiert (z.B.
# auf älteren Ubuntu-Versionen), läuft der Schritt sauber durch.
RUN userdel -r ubuntu 2>/dev/null || true \
    && groupdel ubuntu 2>/dev/null || true

RUN groupadd --force -g $WWWGROUP sail
RUN useradd -ms /bin/bash --no-user-group -g $WWWGROUP -u 1337 sail

COPY start-container /usr/local/bin/start-container
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY php.ini /etc/php/8.3/cli/conf.d/99-sail.ini
RUN chmod +x /usr/local/bin/start-container \
    && chmod 0644 /etc/php/8.3/cli/conf.d/99-sail.ini

# F-DOCK-013: docker-compose mappt den Host-Port auf 80, nicht 8000.
# Das EXPOSE diente bisher nur als Doku-Hinweis und war irreführend.
EXPOSE 80

ENTRYPOINT ["start-container"]
