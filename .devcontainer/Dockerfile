ARG VARIANT="1.17-buster"

FROM mcr.microsoft.com/vscode/devcontainers/go:0-${VARIANT}

# install golangci-lint binary
RUN curl -sSfL https://raw.githubusercontent.com/golangci/golangci-lint/master/install.sh | sh -s -- -b $(go env GOPATH)/bin v1.43.0

# install air binary
RUN curl -sSfL https://raw.githubusercontent.com/cosmtrek/air/master/install.sh | sh -s -- -b $(go env GOPATH)/bin
