# Use the devcontainer image for development and add the required tools
FROM mcr.microsoft.com/vscode/devcontainers/go:1.19-buster AS development
RUN curl -sSfL https://raw.githubusercontent.com/golangci/golangci-lint/master/install.sh | sh -s -- -b $(go env GOPATH)/bin v1.50.1
RUN curl -sSfL https://raw.githubusercontent.com/cosmtrek/air/master/install.sh | sh -s -- -b $(go env GOPATH)/bin

# Restore the dependencies
FROM golang:1.19-buster AS restore
WORKDIR /app
COPY go.mod ./
COPY go.sum ./
RUN go mod download

# Build the application
FROM restore AS build
WORKDIR /app
COPY *.go ./
RUN go build -buildvcs=false -v -o quadrinhos

# Copy the binary and set it as entrypoint
FROM gcr.io/distroless/base-debian10
COPY --from=build /app/quadrinhos /app/quadrinhos
EXPOSE 80
USER nonroot:nonroot
ENTRYPOINT ["/app/quadrinhos"]
