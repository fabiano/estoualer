(ns build
  (:require [clojure.tools.build.api :as build-api]))

(def lib 'fabiano/estoualer)
(def version "latest")
(def uber-file (format "target/%s-%s.jar" (name lib) version))

;; delay to defer side effects (artifact downloads)
(def basis (delay (build-api/create-basis {:project "deps.edn"})))

(defn clean [_]
  (build-api/delete {:path "target"}))

(defn uber [_]
  (clean nil)

  (build-api/copy-dir
   {:src-dirs ["src" "resources"]
    :target-dir "target/classes"})

  (build-api/compile-clj
   {:basis @basis
    :ns-compile '[estoualer.main]
    :class-dir "target/classes"})

  (build-api/uber
   {:class-dir "target/classes"
    :uber-file uber-file
    :basis @basis
    :main 'estoualer.main}))
