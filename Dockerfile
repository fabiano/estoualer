# stage: build
FROM clojure:temurin-25-tools-deps-1.12.5.1654 AS build
ENV DEBIAN_FRONTEND=noninteractive
WORKDIR /src

# install sqlite3 used by create-database.sh
RUN apt-get update \
  && apt-get install -y sqlite3 \
  && apt-get autoremove -y \
  && rm -rf /var/lib/apt/lists/*

# restore the dependencies
COPY deps.edn .
COPY build.clj .
RUN clojure -X:deps prep
RUN clojure -P -T:build

# build the uberjar
COPY src/ ./src/
COPY resources/ ./resources/
RUN clojure -T:build uber

# recreate the database with the latest changes
COPY books.txt .
COPY comicbooks.txt .
COPY create-database.sh .
RUN chmod +x create-database.sh
RUN ./create-database.sh ./target/estoualer.db

# stage: run
FROM eclipse-temurin:25
WORKDIR /app
COPY --from=build /src/target/estoualer-latest.jar .
COPY --from=build /src/target/estoualer.db .

# run the application
# --enable-native-access silences the sqlite-jdbc native library warning
CMD ["java", "--enable-native-access=ALL-UNNAMED", "-jar", "estoualer-latest.jar"]
