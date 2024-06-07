FROM mcr.microsoft.com/dotnet/sdk:8.0-bookworm-slim AS build
WORKDIR /src
COPY *.sln .
COPY *.csproj .
RUN dotnet restore EstouALer.csproj
COPY . .
RUN dotnet publish EstouALer.csproj --configuration Release --output /app --no-restore

FROM mcr.microsoft.com/dotnet/aspnet:8.0-bookworm-slim
WORKDIR /app
COPY --from=build /app .
COPY Bookshelf.db .
ENTRYPOINT ["dotnet", "EstouALer.dll"]
