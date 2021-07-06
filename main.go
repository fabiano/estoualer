package main

import (
	"encoding/json"
	"log"
	"net/http"
	"os"
	"regexp"

	_ "github.com/joho/godotenv/autoload"
)

type App struct {
	Logger    *log.Logger
	Bookshelf ABookshelf
}

func (h App) ServeHTTP(w http.ResponseWriter, r *http.Request) {
	matches := regexp.MustCompile(`^\/(\d*)$`).FindStringSubmatch(r.URL.Path)

	if len(matches) == 0 {
		w.WriteHeader(http.StatusNotFound)

		return
	}

	arr, err := h.Bookshelf.Get(ToIntOrDefault(matches[1]))

	if err != nil {
		h.Logger.Printf("Unable to get the spreadsheet rows: %v", err)

		w.WriteHeader(http.StatusInternalServerError)

		return
	}

	json, err := json.Marshal(arr)

	if err != nil {
		h.Logger.Printf("Unable to marshal: %v", err)

		w.WriteHeader(http.StatusInternalServerError)

		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	w.Write(json)
}

func main() {
	logger := log.New(os.Stdout, "quadrinhos: ", log.LstdFlags)

	b := Bookshelf{
		APIKey:        os.Getenv("QUADRINHOS_API_KEY"),
		SpreadsheetId: os.Getenv("QUADRINHOS_SPREADSHEET_ID"),
	}

	http.Handle("/", App{Logger: logger, Bookshelf: b})

	err := http.ListenAndServe(":34567", nil)

	if err != nil {
		logger.Fatalf("Could not start server: %s", err)
	}
}
