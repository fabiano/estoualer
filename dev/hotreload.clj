(ns hotreload
  (:gen-class)
  (:require [estoualer.main :as main]
            [ring.adapter.jetty :refer [run-jetty]]
            [ring.middleware.reload :refer [wrap-reload]]))

(def app
  (wrap-reload #'main/app))

(defn -main [& args]
  (let [port (Integer/parseInt (or (first args) "80"))]
    (run-jetty app {:port port})))
