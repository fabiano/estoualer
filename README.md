# estoualer

[https://estoualer.dev](https://estoualer.dev) website code.

[![CI](https://github.com/fabiano/estoualer/actions/workflows/build-test.yml/badge.svg)](https://github.com/fabiano/estoualer/actions/workflows/build-test.yml)

## Prerequisites

- Docker/Podman
- Visual Studio Code
- [Dev Containers extension](https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.remote-containers)

## Working on the project

- Build the services: `docker compose -f .\.devcontainer.compose.yml build`
- Start the services: `docker compose -f .\.devcontainer.compose.yml up`
- Open Visual Studio Code
- Run the `Dev Containers: Open Folder in Container...` command and select the repository folder
- (Optional) Start the project: `docker compose -f .\.devcontainer.compose.yml exec devcontainer bash -c "dotnet run"`
- (Optional) Open http://localhost:4201

## Troubleshooting

- Stop and remove the containers: `docker compose -f .\.devcontainer.compose.yml down`
- Remove the volume added by the Dev Containers extension: `docker volume remove vscode`
