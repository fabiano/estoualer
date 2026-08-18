(ns estoualer.db
  (:require [next.jdbc :as jdbc]
            [next.jdbc.result-set :as rs]
            [clojure.string :as str]))

(def ds-spec {:dbname "estoualer.db" :dbtype "sqlite"})
(def ds-opts {:builder-fn rs/as-unqualified-lower-maps})
(def ds (jdbc/with-options (jdbc/get-datasource ds-spec) ds-opts))

(defn quote-fts-terms [value]
  (->> (str/split value #" ")
       (map #(str/replace % "\"" "\"\""))
       (map #(str "\"" % "\""))
       (str/join " ")))

(defn execute! [sql-params]
  (jdbc/execute! ds sql-params))
