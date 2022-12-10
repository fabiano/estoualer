# quadrinhos

[https://quadrinhos.dev](https://quadrinhos.dev) website code.

[![CI](https://github.com/fabiano/quadrinhos/actions/workflows/build-test.yml/badge.svg)](https://github.com/fabiano/quadrinhos/actions/workflows/build-test.yml.yml)

## Prerequisites

- Go
- golangci-lint
- WSL 2
- Docker
- Visual Studio Code
- [Visual Studio Code Remote - WSL extension](https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.remote-wsl)
- [EditorConfig for VS Code extension](https://marketplace.visualstudio.com/items?itemName=editorconfig.editorconfig)
- [Go extension](https://marketplace.visualstudio.com/items?itemName=golang.go)

## Running the project

- Clone the repository inside the WSL distribution
- Create the environment file (see the api/.env.template file)
- Run `docker compose up`
- Open http://localhost:4201

## Working on the project

- Install Go & golangci-lint in the WSL distribution (run the setup-wsl-env.sh script)
- Open Visual Studio Code
- Run the `Remote-WSL: Open Folder in WSL...` command and select the repository folder
