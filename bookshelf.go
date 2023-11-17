package main

import (
	"fmt"

	"github.com/xuri/excelize/v2"
)

// ComicBook represents a comic book.
type ComicBook struct {
	Date      NullableTime `json:"date"`      // Date the comic book was read.
	Publisher string       `json:"publisher"` // Publisher of the comic book.
	Title     string       `json:"title"`     // Title of the comic book.
	Pages     int          `json:"pages"`     // Number of pages of the comic book.
	Issues    int          `json:"issues"`    // Number of issues of the comic book.
	Format    string       `json:"format"`    // Format of the comic book.
}

// NewComicBook creates a new comic book from the spreadsheet row data.
func NewComicBook(i []string) ComicBook {
	return ComicBook{
		Date:      ToNullableTimeOrDefault(i[0]),
		Publisher: ToStringOrDefault(i[1]),
		Title:     ToStringOrDefault(i[2]),
		Pages:     ToIntOrDefault(i[3]),
		Issues:    ToIntOrDefault(i[4]),
		Format:    ToStringOrDefault(i[5]),
	}
}

// ABookshelf represents a bookshelf.
type ABookshelf interface {
	Get(year int) ([]ComicBook, error)
}

// Bookshelf is the default bookshelf implementation. Reads the comics from the spreadsheet.
type Bookshelf struct {
	FileName string // Spreadsheet file name.
}

// Get returns all the comic books for the requested year.
func (b Bookshelf) Get(year int) ([]ComicBook, error) {
	f, err := excelize.OpenFile(b.FileName)

	if err != nil {
		return nil, fmt.Errorf("unable to open the spreadsheet: %w", err)
	}

	rows, err := f.GetRows("Quadrinhos")

	if err != nil {
		return nil, fmt.Errorf("unable to read the comics sheet: %w", err)
	}

	arr := make([]ComicBook, 0, 128)

	for i, row := range rows {
		if i == 0 {
			continue
		}

		comicBook := NewComicBook(row)

		if comicBook.Date.HasValue && comicBook.Date.Value.Year() == year {
			arr = append(arr, NewComicBook(row))
		}
	}

	return arr, nil
}
