(ns estoualer.routes
  (:require [clojure.string :as str]
            [estoualer.books :as books]
            [estoualer.comic-books :as comic-books]
            [estoualer.search-term :as search-term]
            [replicant.string :as replicant]
            [ring.util.codec :as codec]
            [ring.util.response :as response]))

(defn format-date [date]
  (str/join "-" (reverse (str/split date #"-"))))

(def month-names
  ["Janeiro" "Fevereiro" "Março" "Abril" "Maio" "Junho"
   "Julho" "Agosto" "Setembro" "Outubro" "Novembro" "Dezembro"])

(defn format-month [date]
  (let [[year month] (str/split date #"-")]
    (str (nth month-names (dec (Integer/parseInt month))) " " year)))

(defn year-month [{:keys [date]}]
  (subs date 0 7))

(defn format-number [n]
  (str/replace (str n) #"(?<=\d)(?=(\d{3})+$)" "."))

(defn pluralize [n singular plural]
  (when (and (some? n) (pos? n))
    (str (format-number n) " " (if (= n 1) singular plural))))

(defn format-length [{:keys [pages issues hours minutes]}]
  (let [parts [(pluralize pages "página" "páginas")
               (pluralize issues "edição" "edições")
               (pluralize hours "hora" "horas")
               (pluralize minutes "minuto" "minutos")]]
    (->> parts
         (remove nil?)
         (str/join " e "))))

(defn is-book? [{:keys [kind]}]
  (= kind :book))

(defn is-comic-book? [{:keys [kind]}]
  (= kind :comic-book))

(defn merge-results [books-results comic-books-results]
  (->> (concat books-results comic-books-results)
       (sort-by (juxt :date :id) #(compare %2 %1))
       (vec)))

(defn render-search [q]
  [:form {:method "get" :class "search"}
   [:input
    {:id "q"
     :name "q"
     :value q
     :placeholder "ano: 2026 ou autor: carla madeira ou titulo: a natureza da mordida"
     :aria-label "Pesquisar"}]])

(defn generate-query-string [field value]
  (str "?q=" (codec/url-encode (search-term/generate field value))))

(defn render-entry [{:keys [date publisher title author format pages issues hours minutes]}]
  [:li.entry
   [:h3.sr-only title]
   [:dl
    [:dt.sr-only "Lido em"]
    [:dd.date (format-date date)]
    [:dt.sr-only "Título"]
    [:dd.title title]
    (when author
      (list [:dt.sr-only "Autor"]
            [:dd.author [:a {:href (generate-query-string :author author)} author]]))
    [:dt.sr-only "Editora"]
    [:dd.publisher [:a {:href (generate-query-string :publisher publisher)} publisher]]
    [:dt.sr-only "Formato"]
    [:dd.format format]
    [:dt.sr-only "Número de páginas, edições ou duração"]
    [:dd.length (format-length {:pages pages :issues issues :hours hours :minutes minutes})]]])

(defn total-minutes [results]
  (reduce + (map #(+ (* 60 (:hours % 0)) (:minutes % 0)) results)))

(defn format-stats [results]
  (let [parts [(pluralize (count results) "leitura" "leituras")
               (pluralize (count (filter is-book? results)) "livro" "livros")
               (pluralize (count (filter is-comic-book? results)) "gibi" "gibis")
               (pluralize (reduce + (map #(:pages % 0) results)) "página" "páginas")
               (pluralize (quot (total-minutes results) 60) "hora" "horas")]]
    (->> parts
         (remove nil?)
         (str/join " · "))))

(defn render-stats [results]
  (when (not-empty results)
    [:section.stats
     [:h2.sr-only "Resumo das leituras"]
     [:p.stats-summary (format-stats results)]]))

(defn render-month [results]
  [:section.month
   [:header.month-header
    [:h2 (format-month (:date (first results)))]
    [:p.month-stats (format-stats results)]]
   [:ul.entries (map render-entry results)]])

(defn render-months [results]
  (when (not-empty results)
    (map render-month (partition-by year-month results))))

(defn render-logo []
  [:svg.logo {:viewBox "12 12 40 40" :aria-hidden "true" :focusable "false"}
   [:rect {:x 12 :y 12 :width 40 :height 8}]
   [:rect {:x 12 :y 28 :width 40 :height 8}]
   [:rect {:x 12 :y 44 :width 24 :height 8}]])

(defn render-header [q]
  [:div.container
   [:header.header
    [:div.brand
     (render-logo)
     [:h1 "Estou a ler"]]
    (render-search q)]])

(defn render-body [results]
  [:div.container
   [:main.body
    (render-stats results)
    (render-months results)]])

(defn render-history-option [year selected-value]
  (let [value (search-term/generate :year year)]
    [:option {:value value :selected (= value selected-value)} (str year)]))

(defn render-history [q]
  (let [years (reverse (cons 1970 (range 2013 2027)))
        options (map #(render-history-option % q) years)]
    [:nav.history {:aria-label "Histórico"}
     [:form.history-form {:method "get"}
      [:label {:for "history-year"} "o que li em"]
      [:select {:id "history-year" :name "q"} options]
      [:button {:type "submit"} "ir"]]]))

(defn render-footer [q]
  [:div.container
    [:footer.footer
      (render-history q)]])

(defn render-page [q]
  (let [term (search-term/parse q)
        results (merge-results (books/search! term)
                               (comic-books/search! term))]
    (str
     "<!DOCTYPE html>"
     (replicant/render
      [:html {:lang "pt-BR"}
       [:head
        [:meta {:charset "utf-8"}]
        [:meta {:name "viewport" :content "width=device-width"}]
        [:meta {:name "description" :content "Os livros e gibis que li."}]
        [:meta {:name "theme-color" :content "#383838"}]
        [:title "Estou a ler"]
        [:link {:rel "shortcut icon" :href "icon.ico"}]
        [:link {:rel "icon" :type "image/svg+xml" :href "icon.svg"}]
        [:link {:rel "apple-touch-icon" :sizes "180x180" :href "apple-touch-icon.png"}]
        [:link {:rel "icon" :sizes "16x16" :href "icon-16.png"}]
        [:link {:rel "icon" :sizes "20x20" :href "icon-20.png"}]
        [:link {:rel "icon" :sizes "24x24" :href "icon-24.png"}]
        [:link {:rel "icon" :sizes "32x32" :href "icon-32.png"}]
        [:link {:rel "icon" :sizes "48x48" :href "icon-48.png"}]
        [:link {:rel "icon" :sizes "64x64" :href "icon-64.png"}]
        [:link {:rel "icon" :sizes "128x128" :href "icon-128.png"}]
        [:link {:rel "icon" :sizes "256x256" :href "icon-256.png"}]
        [:link {:rel "icon" :sizes "512x512" :href "icon-512.png"}]
        [:link {:rel "icon" :sizes "1024x1024" :href "icon-1024.png"}]
        [:link {:rel "icon" :sizes "2048x2048" :href "icon-2048.png"}]
        [:link {:rel "icon" :sizes "4096x4096" :href "icon-4096.png"}]
        [:link {:rel "manifest" :href "site.webmanifest"}]
        [:link {:rel "preconnect" :href "https://fonts.googleapis.com" }]
        [:link {:rel "preconnect" :href "https://fonts.gstatic.com" :crossorigin true}]
        [:link {:rel "stylesheet" :href "https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,200..800;1,6..72,200..800&display=swap"}]
        [:link {:rel "stylesheet" :href "site.css"}]
        [:script {:src "site.js" :defer true}]]
       [:body
        (render-header q)
        (render-body results)
        (render-footer q)]]))))

(defn get-or-default [map key default]
  (let [value (get map key)]
    (if (str/blank? value)
      default
      value)))

(defn root [{:keys [params]}]
  (-> (get-or-default params "q" "ano: 2026")
      (render-page)
      (response/response)
      (response/content-type "text/html; charset=UTF-8")))

(defn not-found [_]
  (response/not-found nil))
