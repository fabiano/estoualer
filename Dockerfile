# restore the dependencies
FROM golang:1.21.1-bookworm AS restore
WORKDIR /app
COPY go.mod ./
COPY go.sum ./
RUN go mod download

# build the application
FROM restore AS build
WORKDIR /app
COPY *.go ./
RUN go build -buildvcs=false -v -o estoualer

# run the application
FROM gcr.io/distroless/base-debian12
COPY --from=build /app/estoualer /app/estoualer
COPY assets /app/assets
COPY estoualer.xlsx /app/estoualer.xlsx
EXPOSE 80
USER nonroot:nonroot
ENTRYPOINT ["/app/estoualer", "/app/estoualer.xlsx"]
