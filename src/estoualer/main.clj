(ns estoualer.main
  (:gen-class)
  (:require [estoualer.routes :as routes]
            [ring.adapter.jetty :refer [run-jetty]]
            [ring.middleware.content-type :refer [wrap-content-type]]
            [ring.middleware.not-modified :refer [wrap-not-modified]]
            [ring.middleware.params :refer [wrap-params]]
            [ring.middleware.resource :refer [wrap-resource]]))

(def uri->route
  {"/" routes/root})

(defn match-route [req]
  (let [uri (:uri req)
        handler (get uri->route uri routes/not-found)]
    (handler req)))

(def app
  (-> match-route
      wrap-params
      (wrap-resource "public")
      wrap-content-type
      wrap-not-modified))

(defn -main [& args]
  (let [port (Integer/parseInt (or (first args) "80"))]
    (run-jetty app {:port port})))
