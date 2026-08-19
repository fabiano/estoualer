# estoualer

[https://estoualer.1983.run](https://estoualer.1983.run) website code.

## Prerequisites

Java, SQLite, rlwrap and podman (Fedora):

```sh
sudo dnf install java-25-openjdk-devel sqlite rlwrap podman
```

[Clojure CLI](https://clojure.org/guides/install_clojure):

```sh
curl -L -O https://github.com/clojure/brew-install/releases/latest/download/linux-install.sh
chmod +x linux-install.sh
sudo ./linux-install.sh
```

[clojure-lsp](https://clojure-lsp.io/) (bundles clj-kondo):

```sh
curl -sLO https://raw.githubusercontent.com/clojure-lsp/clojure-lsp/master/install
chmod +x install
sudo ./install
```

## Working on the project

Run the commands from the repository root - the database is opened relative to the working
directory.

- Create the database: `./create-database.sh estoualer.db`
- Start the project: `clojure -M:run`
- Start with hot reload: `clojure -M:hotreload`
- Open http://localhost:4201

## Building

- Build the uberjar: `clojure -T:build uber`
- Run it: `java --enable-native-access=ALL-UNNAMED -jar target/estoualer-latest.jar 4201`

## Container

- Build: `podman build -t estoualer .`
- Run: `podman run --rm -p 4201:80 estoualer`
- Open http://localhost:4201
