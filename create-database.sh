#!/bin/sh

database=$1

# recreate the tables
sqlite3 $database <<EOF

DROP TABLE IF EXISTS Book;
DROP TABLE IF EXISTS ComicBook;

CREATE TABLE Book (
  Id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  Date TEXT NOT NULL,
  Publisher TEXT NOT NULL,
  Title TEXT NOT NULL,
  Author TEXT NOT NULL,
  Format TEXT NOT NULL,
  Pages INTEGER NOT NULL DEFAULT 0,
  Duration TEXT NOT NULL DEFAULT '0m',
  Rating INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE ComicBook (
  Id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  Date TEXT NOT NULL,
  Publisher TEXT NOT NULL,
  Title TEXT NOT NULL,
  Format TEXT NOT NULL,
  Pages INTEGER NOT NULL DEFAULT 0,
  Issues INTEGER NOT NULL DEFAULT 0
);

EOF

# import books into table
input=books.txt
output=$(mktemp)

awk '
  BEGIN { OFS=";"; i=1 }
  NF==0 { print_row(); delete row; next; }
  END { print_row(); }

  function print_row() {
   if (row["date"]) {
      print i++,
            row["date"],
            row["publisher"],
            row["title"],
            row["author"],
            row["format"],
            row["pages"],
            row["duration"],
            row["rating"];
    }
  }

  /^[[:space:]]*([A-Za-z_]+)[[:space:]]*=[[:space:]]*(.*)/ {
    field=tolower($1);
    row[field]=substr($0, index($0, "=") + 1);

    gsub(/^[[:space:]]+|[[:space:]]+$/, "", row[field]);
  }
' $input > $output

sqlite3 $database <<EOF

.mode csv
.separator ";"
.import $output Book

EOF

# import comic books into table
input=comicbooks.txt
output=$(mktemp)

awk '
  BEGIN { OFS=";"; i=1; }
  NF==0 { print_row(); delete row; next; }
  END { print_row(); }

  function print_row() {
    if (row["date"]) {
      print i++,
            row["date"],
            row["publisher"],
            row["title"],
            row["format"],
            row["pages"],
            row["issues"];
    }
  }

  /^[[:space:]]*([A-Za-z_]+)[[:space:]]*=[[:space:]]*(.*)/ {
    field=tolower($1);
    row[field]=substr($0, index($0, "=") + 1);

    gsub(/^[[:space:]]+|[[:space:]]+$/, "", row[field]);
  }
' $input > $output

sqlite3 $database <<EOF

.mode csv
.separator ";"
.import $output ComicBook

EOF

