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