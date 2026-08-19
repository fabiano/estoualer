(ns estoualer.main
  (:gen-class)
  (:require [estoualer.books :as books]
            [estoualer.comic-books :as comic-books]
            [estoualer.search-term :as search-term]
            [clojure.string :as str]
            [hiccup2.core :as hiccup]
            [ring.adapter.jetty :refer [run-jetty]]
            [ring.util.response :refer [content-type response]]
            [ring.middleware.content-type :refer [wrap-content-type]]
            [ring.middleware.not-modified :refer [wrap-not-modified]]
            [ring.middleware.params :refer [wrap-params]]
            [ring.middleware.resource :refer [wrap-resource]]))

(defn format-date [date]
  (str/join "-" (reverse (str/split date #"-"))))

(defn pluralize [n singular plural]
  (when (pos? n)
    (str n " " (if (= n 1) singular plural))))

(defn format-length [{:keys [pages issues hours minutes]
                      :or {pages 0 issues 0 hours 0 minutes 0}}]
  (let [parts [(pluralize pages "página" "páginas")
               (pluralize issues "edição" "edições")
               (pluralize hours "hora" "horas")
               (pluralize minutes "minuto" "minutos")]]
    (->> parts
         (remove nil?)
         (str/join " e "))))

(defn is-paper? [{:keys [format]}]
  (or (= format "Capa dura")
      (= format "Capa comum")))

(defn is-audio-book? [{:keys [format]}]
  (= format "Audiolivro"))

(defn is-ebook? [{:keys [format]}]
  (= format "eBook"))

(defn render-stats [books-results comic-books-results]
  (let [results (concat books-results comic-books-results)]
    [:div.stats
     [:div.item.total
      [:p.heading "Total"]
      [:p.value (count results)]]
     [:div.item.books
      [:p.heading "Livros"]
      [:p.value (count books-results)]]
     [:div.item.comicbooks
      [:p.heading "Gibis"]
      [:p.value (count comic-books-results)]]
     [:hr.separator]
     [:div.item.paper
      [:p.heading "Em papel"]
      [:p.value (count (filter is-paper? results))]]
     [:div.item.audio
      [:p.heading "Em áudio"]
      [:p.value (count (filter is-audio-book? results))]]
     [:div.item.ebook
      [:p.heading "eBook"]
      [:p.value (count (filter is-ebook? results))]]]))

(defn render-search [q]
  [:form {:method "get" :class "search"}
   [:input
    {:id "q"
     :name "q"
     :value q
     :placeholder "ano: 2026 ou autor: carla madeira ou titulo: a natureza da mordida"
     :aria-label "Pesquisar"}]])

(defn render-book [{:keys [number date title author format pages hours minutes]}]
  [:article.book.card
   [:header
    [:div.number {:aria-hidden "true"} (str number)]
    [:time.date {:datetime date} (format-date date)]]
   [:div
    [:h3.title title]
    [:p.publisher-and-format (str author " / " format)]]
   [:footer
    [:p.length (format-length {:pages pages :hours hours :minutes minutes})]]])

(defn render-books [books-results]
  (when (not-empty books-results)
    [:section.books
     [:h2 "Livros"]
     [:div.cards (map render-book books-results)]]))

(defn render-comic-book [{:keys [number date title publisher format pages issues]}]
  [:article.comic-book.card
   [:header
    [:div.number {:aria-hidden "true"} (str number)]
    [:time.date {:datetime date} (format-date date)]]
   [:div
    [:h3.title title]
    [:p.publisher-and-format (str publisher " / " format)]]
   [:footer
    [:p.length (format-length {:pages pages :issues issues})]]])

(defn render-comic-books [comic-books-results]
  (when (not-empty comic-books-results)
    [:section.comic-books
     [:h2 "Gibis"]
     [:div.cards (map render-comic-book comic-books-results)]]))

(defn render-header [books-results comic-books-results]
  [:div.container
   [:header.header
    [:h1 "Estou a ler"]
    (render-stats books-results comic-books-results)]])

(defn render-body [q books-results comic-books-results]
  [:div.container
   [:main.body
    (render-search q)
    (render-books books-results)
    (render-comic-books comic-books-results)]])

(defn render-history-option [year selected-value]
  (let [value (str "ano: " year)]
    [:option {:value value :selected (= value selected-value)} (str year)]))

(defn render-history [q]
  (let [years (cons 1970 (range 2013 2027))
        options (map #(render-history-option % q) years)]
    [:nav.history {:aria-label "Histórico"}
     [:form.history-form {:method "get"}
      [:label {:for "history-year"} "o que li em"]
      [:select {:id "history-year" :name "q" :onchange "this.form.submit()"} options]
      [:noscript
       [:button {:type "submit"} "ir"]]]]))

(defn render-credits []
  [:p.credits "ícone por "
   [:a {:href "https://www.iconfinder.com/sudheepb"} "sudheep b"]" em "
   [:a {:href "https://www.iconfinder.com/icons/4879874/book_education_learning_study_icon"
        :title "Iconfinder"}
    "Iconfinder"]])

(defn render-footer [q]
  [:footer.footer
   (render-history q)
   (render-credits)])

(defn render-page [q]
  (let [term (search-term/parse q)
        books-results (books/search! term)
        comic-books-results (comic-books/search! term)]
    [:html {:lang "pt-BR"}
     [:head
      [:meta {:charset "utf-8"}]
      [:meta {:name "viewport" :content "width=device-width, initial-scale=1"}]
      [:meta {:name "description" :content "Os livros e gibis que li."}]
      [:title "Estou a ler"]
      [:link {:rel "shortcut icon" :href "icon.ico"}]
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
      [:link {:rel "stylesheet" :href "site.css"}]]
     [:body]
     (render-header books-results comic-books-results)
     [:hr.separator]
     (render-body q books-results comic-books-results)
     (render-footer q)]))

(defn get-or-default [map key default]
  (let [value (get map key)]
    (if (str/blank? value)
      default
      value)))

(defn handler [{:keys [params]}]
  (-> (get-or-default params "q" "ano: 2026")
      (render-page)
      (hiccup/html)
      (str)
      (response)
      (content-type "text/html; charset=UTF-8")))

(def app
  (-> handler
      (wrap-params)
      (wrap-resource "public")
      (wrap-content-type)
      (wrap-not-modified)))

(defn -main [& args]
  (let [port (if-let [p (first args)] (Integer/parseInt p) 80)]
    (run-jetty app {:port port})))
