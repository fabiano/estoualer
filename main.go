// Main package
package main

import (
	"encoding/json"
	"log"
	"net/http"
	"os"
	"path/filepath"
	"regexp"
	"time"
)

func main() {
	l := log.New(os.Stdout, "estoualer: ", log.LstdFlags)

	if len(os.Args) <= 1 {
		l.Fatalf("usage: estoualer [spreadsheet file]")
	}

	abs, err := filepath.Abs(os.Args[1])

	if err != nil {
		l.Fatalf("could not find the spreadsheet file: %s", err)
	}

	b := Bookshelf{
		FileName: abs,
	}

	http.Handle("/", http.FileServer(http.Dir("./assets")))
	http.Handle("/bookshelf/", GetBookshelf{Logger: l, Bookshelf: b})

	port := os.Getenv("PORT")

	if port == "" {
		port = "8080"
	}

	s := http.Server{
		Addr:         ":" + port,
		ReadTimeout:  5 * time.Second,
		WriteTimeout: 5 * time.Second,
	}

	l.Printf("listening on port %s", port)

	err = s.ListenAndServe()

	if err != nil {
		l.Fatalf("could not start the server: %s", err)
	}
}

// GetBookshelf returns all the comic books for the requested year.
type GetBookshelf struct {
	Logger    *log.Logger
	Bookshelf ABookshelf
}

func (h GetBookshelf) ServeHTTP(w http.ResponseWriter, r *http.Request) {
	header := w.Header()

	// Enable CORS everywhere
	header.Set("Access-Control-Allow-Origin", "*")
	header.Set("Vary", "Origin")

	// Check the route pattern
	matches := regexp.MustCompile(`^\/bookshelf\/(\d{4})$`).FindStringSubmatch(r.URL.Path)

	if len(matches) == 0 {
		w.WriteHeader(http.StatusNotFound)

		return
	}

	// Preflight request
	if r.Method == http.MethodOptions {
		header.Set("Access-Control-Allow-Methods", "GET")

		if v := r.Header.Get("Access-Control-Request-Headers"); v != "" {
			header.Set("Access-Control-Allow-Headers", v)
		}

		w.WriteHeader(http.StatusNoContent)

		return
	}

	// Only get is supported
	if r.Method != http.MethodGet {
		w.WriteHeader(http.StatusMethodNotAllowed)

		return
	}

	// Get the comic books from the bookshelf
	arr, err := h.Bookshelf.Get(ToIntOrDefault(matches[1]))

	if err != nil {
		h.Logger.Printf("unable to get the spreadsheet rows: %v", err)

		w.WriteHeader(http.StatusInternalServerError)

		return
	}

	json, err := json.Marshal(arr)

	if err != nil {
		h.Logger.Printf("unable to marshal: %v", err)

		w.WriteHeader(http.StatusInternalServerError)

		return
	}

	header.Set("Content-Type", "application/json")

	w.WriteHeader(http.StatusOK)

	_, err = w.Write(json)

	if err != nil {
		h.Logger.Printf("unable to write the data to the connection: %v", err)
	}
}
