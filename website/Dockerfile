# Use the devcontainer image for development and add the required tools
FROM mcr.microsoft.com/vscode/devcontainers/javascript-node AS development
RUN npm install --global http-server

# Copy the files
FROM nginx:alpine
COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY . /usr/share/nginx/html
