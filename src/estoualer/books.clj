(ns estoualer.books
  (:require [estoualer.db :as db]))

(def by-year
  "SELECT *
   FROM Book
   WHERE Date LIKE ?
   ORDER BY Id DESC")

(def by-publisher
  "SELECT *
   FROM BookFts
   WHERE Publisher MATCH ?
   ORDER BY Rank")

(def by-author
  "SELECT *
   FROM BookFts
   WHERE Author MATCH ?
   ORDER BY Rank")

(def by-title
  "SELECT *
   FROM BookFts
   WHERE Title MATCH ?
   ORDER BY Rank")

(def by-everything
  "SELECT *
   FROM BookFts
   WHERE BookFts MATCH ?
   ORDER BY Rank")

(defn sql-params-for [field value]
  (case field
    :year      [by-year       (str value "%")]
    :publisher [by-publisher  (db/quote-fts-terms value)]
    :author    [by-author     (db/quote-fts-terms value)]
    :title     [by-title      (db/quote-fts-terms value)]
               [by-everything (db/quote-fts-terms value)]))

(defn parse-duration [duration]
  (let [re #"^((?<Hours>\d+?)h)?\s{0,1}((?<Minutes>\d+?)m)?$"
        [_ _ hours _ minutes] (re-matches re duration)
        hours (or (some-> hours Integer/parseInt) 0)
        minutes (or (some-> minutes Integer/parseInt) 0)]
    [hours minutes]))

(defn extract-duration [book]
  (let [[hours minutes] (parse-duration (:duration book))]
    (-> book
        (dissoc :duration)
        (assoc :hours hours)
        (assoc :minutes minutes))))

(defn search! [{:keys [field value]}]
  (let [rows (db/execute! (sql-params-for field value))]
    (map extract-duration rows)))
