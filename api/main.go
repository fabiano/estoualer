package main

import (
	"context"
	"encoding/json"
	"log"
	"net/http"
	"os"
	"regexp"

	_ "github.com/joho/godotenv/autoload"
)

// DefaultHandler handles all requests
type DefaultHandler struct {
	Logger    *log.Logger
	Bookshelf ABookshelf
}

func (h DefaultHandler) ServeHTTP(w http.ResponseWriter, r *http.Request) {
	header := w.Header()

	// Enable CORS everywhere
	header.Set("Access-Control-Allow-Origin", "*")
	header.Set("Vary", "Origin")

	// Check the route pattern
	matches := regexp.MustCompile(`^\/(\d{4})$`).FindStringSubmatch(r.URL.Path)

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

func main() {
	logger := log.New(os.Stdout, "quadrinhos: ", log.LstdFlags)

	b := Bookshelf{
		APIKey:        os.Getenv("QUADRINHOS_API_KEY"),
		SpreadsheetID: os.Getenv("QUADRINHOS_SPREADSHEET_ID"),
		Context:       context.Background(),
	}

	http.Handle("/", DefaultHandler{Logger: logger, Bookshelf: b})

	port := os.Getenv("PORT")

	if port == "" {
		port = "80"
	}

	logger.Printf("listening on port %s", port)

	err := http.ListenAndServe(":"+port, nil)

	if err != nil {
		logger.Fatalf("could not start server: %s", err)
	}
}
