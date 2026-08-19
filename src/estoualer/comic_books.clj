(ns estoualer.comic-books
  (:require [estoualer.db :as db]))

(def by-year
  "SELECT *
   FROM ComicBook
   WHERE Date LIKE ?
   ORDER BY Id DESC")

(def by-publisher
  "SELECT *
   FROM ComicBookFts
   WHERE Publisher MATCH ?
   ORDER BY Rank")

(def by-title
  "SELECT *
   FROM ComicBookFts
   WHERE Title MATCH ?
   ORDER BY Rank")

(def by-everything
  "SELECT *
   FROM ComicBookFts
   WHERE ComicBookFts MATCH ?
   ORDER BY Rank")

(defn sql-params-for [field value]
  (case field
    :year      [by-year       (str value "%")]
    :publisher [by-publisher  (db/quote-fts-terms value)]
    :title     [by-title      (db/quote-fts-terms value)]
               [by-everything (db/quote-fts-terms value)]))

(defn append-number [row number]
  (assoc row :number number))

(defn new-comic-book [total index row]
  (append-number row (- total index)))

(defn search! [{:keys [field value]}]
  (let [rows (db/execute! (sql-params-for field value))
        total (count rows)]
    (into [] (map-indexed #(new-comic-book total %1 %2)) rows)))
