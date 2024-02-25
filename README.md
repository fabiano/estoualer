# estoualer

[https://estoualer.dev](https://estoualer.dev) website code.

[![CI](https://github.com/fabiano/estoualer/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/fabiano/estoualer/actions/workflowscontinuous-integration.yml)

## Prerequisites

- [.NET SDK 8](https://dotnet.microsoft.com/en-us/download/dotnet/8.0)
- [Visual Studio Code](https://code.visualstudio.com/)
- [EditorConfig for VS Code](https://marketplace.visualstudio.com/items?itemName=editorconfig.editorconfig)
- [C# Dev Kit](https://marketplace.visualstudio.com/items?itemName=ms-dotnettools.csdevkit)

## Working on the project

- Install .NET SDK 8: `winget install --exact --id Microsoft.DotNet.SDK.8`
- Install EditorConfig extension: `code --install-extension editorconfig.editorconfig`
- Install C# Dev Kit extension: `code --install-extension ms-dotnettools.csdevkit`
- Restore the dependencies: `dotnet restore`
- Build the project: `dotnet build`
- (Optional) Start the project: `dotnet run`
- (Optional) Open http://localhost:4201
