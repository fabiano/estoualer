FROM alpine:latest AS build
WORKDIR /app

# install dependencies
RUN apk update \
 && apk add sqlite

# recreate the database with the latest changes
COPY books.txt .
COPY comicbooks.txt .
COPY create-database.sh .
RUN chmod +x create-database.sh
RUN ./create-database.sh estoualer.db

FROM php:8.5.12-apache
WORKDIR /var/www/html

# use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# copy the application code
COPY src .

# copy the database
COPY --from=build /app/estoualer.db .
