(ns estoualer.comic-books
  (:require [estoualer.db :as db]))

(def by-year
  "SELECT *
   FROM ComicBook
   WHERE Date LIKE ?
   ORDER BY Date DESC, Id DESC")

(def by-publisher
  "SELECT *
   FROM ComicBookFts
   WHERE Publisher MATCH ?
   ORDER BY Date DESC, Id DESC")

(def by-title
  "SELECT *
   FROM ComicBookFts
   WHERE Title MATCH ?
   ORDER BY Date DESC, Id DESC")

(def by-everything
  "SELECT *
   FROM ComicBookFts
   WHERE ComicBookFts MATCH ?
   ORDER BY Date DESC, Id DESC")

(defn sql-params-for [field value]
  (case field
    :year      [by-year       (str value "%")]
    :publisher [by-publisher  (db/quote-fts-terms value)]
    :title     [by-title      (db/quote-fts-terms value)]
               [by-everything (db/quote-fts-terms value)]))

(defn new-comic-book [row]
  (-> row
      (assoc :kind :comic-book)))

(defn search! [{:keys [field value]}]
  (let [rows (db/execute! (sql-params-for field value))]
    (into [] (map new-comic-book) rows)))
