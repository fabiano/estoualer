# Restore the dependencies
FROM golang:1.21.1-bookworm AS restore
WORKDIR /app
COPY go.mod ./
COPY go.sum ./
RUN go mod download

# Build the application
FROM restore AS build
WORKDIR /app
COPY *.go ./
RUN go build -buildvcs=false -v -o estoualer

# Copy the binary and the spreadsheet and set it as entrypoint
FROM gcr.io/distroless/base-debian10
COPY --from=build /app/estoualer /app/estoualer
COPY ../estoualer.xlsx /app/estoualer.xlsx
EXPOSE 80
USER nonroot:nonroot
ENTRYPOINT ["/app/estoualer", "/app/estoualer.xlsx"]
