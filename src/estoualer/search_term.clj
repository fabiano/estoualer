(ns estoualer.search-term
  (:require [clojure.string :as str]
            [clojure.set :as set]))

(def field->keyword
  {"ano"     :year
   "editora" :publisher
   "autor"   :author
   "titulo"  :title})

(def keyword->field
  (set/map-invert field->keyword))

(defn parse [s]
  (let [[_ field value] (re-matches #"^([a-z]+):\s*(\S.*)$" s)]
    (if-let [field-as-keyword (field->keyword field nil)]
      {:field field-as-keyword :value (str/trim value)}
      {:field nil :value (str/trim s)})))

(defn generate [field-as-keyword value]
  (str (keyword->field field-as-keyword) ": " value))

