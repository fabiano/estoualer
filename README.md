# quadrinhos

[https://quadrinhos.dev](https://quadrinhos.dev) website code.

[![CI](https://github.com/fabiano/quadrinhos/actions/workflows/build-test.yml/badge.svg)](https://github.com/fabiano/quadrinhos/actions/workflows/build-test.yml.yml)

## Prerequisites

- WSL 2
- Docker
- Visual Studio Code
- [Visual Studio Code Remote - WSL extension](https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.remote-wsl)
- [Visual Studio Code Remote - Containers extension](https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.remote-containers)

## Running the website locally

- Clone the repository inside the WSL distribution
- Open Visual Studio Code, run the `Remote-WSL: Open Folder in WSL...` command and select the repository folder
- Create the environment file (see api/.env.template)
- Run `docker compose up`
- Open https://localhost:4201

### Working on the api

- Open Visual Studio Code
- Run the `Remote-WSL: Open Folder in WSL...` command and select the repository folder
- Run the `Remote-Containers: Open Folder in Container...` command and select the api folder

### Working on the website

- Open Visual Studio Code
- Run the `Remote-WSL: Open Folder in WSL...` command and select the repository folder
- Run the `Remote-Containers: Open Folder in Container...` command and select the website folder
