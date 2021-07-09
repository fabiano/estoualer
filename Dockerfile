## Build

FROM golang:1.16-buster AS build

WORKDIR /app

COPY go.mod ./
COPY go.sum ./

RUN go mod download

COPY *.go ./

RUN go build -v -o quadrinhos

## Deploy

FROM gcr.io/distroless/base-debian10

COPY --from=build /app/quadrinhos /app/quadrinhos

EXPOSE 34567

USER nonroot:nonroot

ENTRYPOINT ["/app/quadrinhos"]
