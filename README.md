# estoualer

A self-hosted application that lets me browse the books and comic books that I have read over the years.

## Prerequisites

PHP:

```sh
sudo dnf install php
```

## Working on the project

Run the commands from the repository root.

- Create the database: `./create-database.sh estoualer.db`
- Start the project: `php -S 0.0.0.0:4201 -t . -c php.ini-development`
- Open http://localhost:4201

## Container

- Build: `podman build -t estoualer .`
- Run: `podman run --rm -p 4201:80 estoualer`
- Open http://localhost:4201
