# Restore the dependencies
FROM golang:1.17-buster AS restore
WORKDIR /app
COPY go.mod ./
COPY go.sum ./
RUN go mod download

# Add development dependencies (used by docker compose)
FROM restore AS development
RUN curl -sSfL https://raw.githubusercontent.com/golangci/golangci-lint/master/install.sh | sh -s -- -b $(go env GOPATH)/bin v1.43.0
RUN curl -sSfL https://raw.githubusercontent.com/cosmtrek/air/master/install.sh | sh -s -- -b $(go env GOPATH)/bin

# Build the application
FROM restore AS build
WORKDIR /app
COPY *.go ./
RUN go build -v -o quadrinhos

# Copy the binary and run the application
FROM gcr.io/distroless/base-debian10
COPY --from=build /app/quadrinhos /app/quadrinhos
EXPOSE 34567
USER nonroot:nonroot
ENTRYPOINT ["/app/quadrinhos"]
