FROM revenuewire/docker-php-alpine:1.0.3 

RUN apk update
RUN apk add php83-dom php83-tokenizer php83-fileinfo php83-mbstring php83-pecl-xdebug php83-xmlwriter

COPY ./ /var/src

