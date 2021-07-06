package main

import (
	"context"
	"encoding/json"
	"errors"
	"fmt"
	"log"
	"net/http"
	"os"
	"regexp"
	"strconv"
	"time"

	"google.golang.org/api/option"
	"google.golang.org/api/sheets/v4"

	_ "github.com/joho/godotenv/autoload"
)

type NullableTime struct {
	HasValue bool
	Value    time.Time
}

func (nt NullableTime) MarshalJSON() ([]byte, error) {
	if nt.HasValue {
		return json.Marshal(nt.Value)
	}

	return []byte("null"), nil
}

func ToNullableTimeOrDefault(i interface{}) NullableTime {
	s, ok := i.(string)

	if !ok {
		return NullableTime{}
	}

	value, err := time.Parse("02/01/2006", s)

	if err != nil {
		return NullableTime{}
	}

	return NullableTime{HasValue: true, Value: value}
}

func ToStringOrDefault(i interface{}) string {
	s, ok := i.(string)

	if !ok {
		return ""
	}

	return s
}

func ToIntOrDefault(i interface{}) int {
	s, ok := i.(string)

	if !ok {
		return 0
	}

	n, err := strconv.Atoi(s)

	if err != nil {
		return 0
	}

	return n
}

type ComicBook struct {
	Date      NullableTime `json:"date"`
	Publisher string       `json:"publisher"`
	Title     string       `json:"title"`
	Pages     int          `json:"pages"`
	Issues    int          `json:"issues"`
	Format    string       `json:"format"`
	Link      string       `json:"link"`
}

func NewComicBook(i []interface{}) ComicBook {
	return ComicBook{
		Date:      ToNullableTimeOrDefault(i[0]),
		Publisher: ToStringOrDefault(i[1]),
		Title:     ToStringOrDefault(i[2]),
		Pages:     ToIntOrDefault(i[3]),
		Issues:    ToIntOrDefault(i[4]),
		Format:    ToStringOrDefault(i[5]),
		Link:      ToStringOrDefault(i[6]),
	}
}

type ABookshelf interface {
	Get(year int) ([]ComicBook, error)
}

type Bookshelf struct {
	APIKey        string
	SpreadsheetId string
}

func (b Bookshelf) Get(year int) ([]ComicBook, error) {
	svc, err := sheets.NewService(context.Background(), option.WithAPIKey(b.APIKey))

	if err != nil {
		return nil, errors.New(fmt.Sprintf("Unable to create sheets service: %v", err))
	}

	values, err := svc.Spreadsheets.Values.Get(b.SpreadsheetId, fmt.Sprintf("%d!A2:G", year)).Do()

	if err != nil {
		return nil, errors.New(fmt.Sprintf("Unable to get data from sheet: %v", err))
	}

	arr := make([]ComicBook, len(values.Values))

	for i, v := range values.Values {
		arr[i] = NewComicBook(v)
	}

	return arr, nil
}

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
