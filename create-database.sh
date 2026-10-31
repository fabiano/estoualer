#!/bin/sh

if [ $# -eq 0 ]; then
  echo "Usage: create-database.sh [File]"
  exit 1
fi

database=$1

# recreate the tables
sqlite3 $database <<EOF

DROP TABLE IF EXISTS book;
DROP TABLE IF EXISTS bookfts;
DROP TABLE IF EXISTS comicbook;
DROP TABLE IF EXISTS comicbookfts;

CREATE TABLE book (
  id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  date TEXT NOT NULL,
  publisher TEXT NOT NULL,
  title TEXT NOT NULL,
  author TEXT NOT NULL,
  format TEXT NOT NULL,
  pages INTEGER NOT NULL DEFAULT 0,
  duration TEXT NOT NULL DEFAULT '0m',
  rating INTEGER NOT NULL DEFAULT 0
);

CREATE VIRTUAL TABLE bookfts USING fts5 (
  id UNINDEXED,
  date UNINDEXED,
  publisher,
  title,
  author,
  format,
  pages UNINDEXED,
  duration UNINDEXED,
  rating UNINDEXED,
  content='book',
  content_rowid='id'
);

CREATE TABLE comicbook (
  id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  date TEXT NOT NULL,
  publisher TEXT NOT NULL,
  title TEXT NOT NULL,
  format TEXT NOT NULL,
  pages INTEGER NOT NULL DEFAULT 0,
  issues INTEGER NOT NULL DEFAULT 0
);

CREATE VIRTUAL TABLE comicbookfts USING fts5 (
  id UNINDEXED,
  date UNINDEXED,
  publisher,
  title,
  format,
  pages UNINDEXED,
  issues UNINDEXED,
  content='comicbook',
  content_rowid='id'
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
.import $output book
.import $output bookfts

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
.import $output comicbook
.import $output comicbookfts

EOF
