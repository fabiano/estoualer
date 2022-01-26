# Use the devcontainer image for development
FROM mcr.microsoft.com/vscode/devcontainers/javascript-node AS development

# Copy the files to the root folder
FROM nginx:alpine
COPY . /usr/share/nginx/html
