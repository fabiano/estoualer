(ns estoualer.routes
  (:require [clojure.string :as str]
            [estoualer.books :as books]
            [estoualer.comic-books :as comic-books]
            [estoualer.search-term :as search-term]
            [replicant.string :as replicant]
            [ring.util.response :as response]))

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
    [:section.stats
     [:h2.sr-only "Resumo da busca"]
     [:dl
      [:div
       [:dt "Total"]
       [:dd (count results)]]
      [:div
       [:dt "Livros"]
       [:dd (count books-results)]]
      [:div
       [:dt "Gibis"]
       [:dd (count comic-books-results)]]
      [:div
       [:dt "Em papel"]
       [:dd (count (filter is-paper? results))]]
      [:div
       [:dt "Em áudio"]
       [:dd (count (filter is-audio-book? results))]]
      [:div
       [:dt "eBook"]
       [:dd (count (filter is-ebook? results))]]]]))

(defn render-search [q]
  [:form {:method "get" :class "search"}
   [:input
    {:id "q"
     :name "q"
     :value q
     :placeholder "ano: 2026 ou autor: carla madeira ou titulo: a natureza da mordida"
     :aria-label "Pesquisar"}]])

(defn render-book [{:keys [date title author format pages hours minutes]}]
  [:li.entry
   [:h3.sr-only title]
   [:dl
    [:div
     [:dt.sr-only "Lido em"]
     [:dd.date (format-date date)]]
    [:div
     [:dt.sr-only "Título"]
     [:dd.title title]
     [:dt.sr-only "Autor e formato"]
     [:dd.publisher-and-format (str author " / " format)]]
    [:div
     [:dt.sr-only "Número de páginas ou duração"]
     [:dd.length (format-length {:pages pages :hours hours :minutes minutes})]]]])

(defn render-books [books-results]
  (when (not-empty books-results)
    [:section
     [:h2 "Livros"]
     [:ul.entries (map render-book books-results)]]))

(defn render-comic-book [{:keys [date title publisher format pages issues]}]
  [:li.entry
   [:h3.sr-only title]
   [:dl
    [:div
     [:dt.sr-only "Lido em"]
     [:dd.date (format-date date)]]
    [:div
     [:dt.sr-only "Título"]
     [:dd.title title]
     [:dt.sr-only "Editora e formato"]
     [:dd.publisher-and-format (str publisher " / " format)]]
    [:div
     [:dt.sr-only "Número de páginas e edições"]
     [:dd.length (format-length {:pages pages :issues issues})]]]])

(defn render-comic-books [comic-books-results]
  (when (not-empty comic-books-results)
    [:section
     [:h2 "Gibis"]
     [:ul.entries (map render-comic-book comic-books-results)]]))

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
      [:select {:id "history-year" :name "q"} options]
      [:button {:type "submit"} "ir"]]]))

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
    (str
     "<!DOCTYPE html>"
     (replicant/render
      [:html {:lang "pt-BR"}
       [:head
        [:meta {:charset "utf-8"}]
        [:meta {:name "viewport" :content "width=device-width"}]
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
       [:body
        (render-header books-results comic-books-results)
        [:hr.separator]
        (render-body q books-results comic-books-results)
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
