package main

import (
	"context"
	"fmt"

	"google.golang.org/api/option"
	"google.golang.org/api/sheets/v4"
)

// ComicBook represents a shelved comic book.
type ComicBook struct {
	Date      NullableTime `json:"date"`      // Date the comic book was read.
	Publisher string       `json:"publisher"` // Publisher of the comic book.
	Title     string       `json:"title"`     // Title of the comic book.
	Pages     int          `json:"pages"`     // Number of pages of the comic book.
	Issues    int          `json:"issues"`    // Number of issues of the comic book.
	Format    string       `json:"format"`    // Format of the comic book.
	Link      string       `json:"link"`      // Link to the comic book.
}

// NewComicBook creates a new comic book from the spreadsheet row data.
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

// ABookshelf represents a bookshelf.
type ABookshelf interface {
	Get(year int) ([]ComicBook, error)
}

// Bookshelf is the default bookshelf implementation. Reads the comic books from the Google Sheets spreadsheet.
type Bookshelf struct {
	APIKey        string // Google Clould Platform API Key.
	SpreadsheetID string // Spreadsheet identifier.
}

// Get returns all the shelved comic books for the desired year.
func (b Bookshelf) Get(year int) ([]ComicBook, error) {
	svc, err := sheets.NewService(context.Background(), option.WithAPIKey(b.APIKey))

	if err != nil {
		return nil, fmt.Errorf("unable to create sheets service: %w", err)
	}

	values, err := svc.Spreadsheets.Values.Get(b.SpreadsheetID, fmt.Sprintf("%d!A2:G", year)).Do()

	if err != nil {
		return nil, fmt.Errorf("unable to get data from sheet: %w", err)
	}

	arr := make([]ComicBook, len(values.Values))

	for i, v := range values.Values {
		arr[i] = NewComicBook(v)
	}

	return arr, nil
}
