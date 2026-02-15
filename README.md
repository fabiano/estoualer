# estoualer

[https://estoualer.dev](https://estoualer.dev) website code.

## Prerequisites

- [PHP 8.5.2](https://www.php.net/downloads.php)

## Working on the project

- Create the database: `./create-database.sh src/Bookshelf.db`
- Start the project: `php -S 0.0.0.0:4201 -t src -c php.ini-development` or `podman compose -f compose.development.yaml up`
- Open http://localhost:4201
