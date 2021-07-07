package main

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io/ioutil"
	"log"
	"net/http"
	"net/http/httptest"
	"os"
	"testing"
)

type FakeBookshelf struct {
}

func (b FakeBookshelf) Get(year int) ([]ComicBook, error) {
	arr := []ComicBook{
		{Publisher: "Marvel", Title: "Annihilation Omnibus", Pages: 880, Issues: 30, Format: "Capa dura"},
		{Publisher: "Marvel", Title: "Annihilation: Conquest Omnibus", Pages: 872, Issues: 33, Format: "Capa dura"},
	}

	return arr, nil
}

func TestDefaultHandler(t *testing.T) {
	server := httptest.NewServer(DefaultHandler{
		Logger:    log.New(os.Stdout, "test: ", log.LstdFlags),
		Bookshelf: FakeBookshelf{},
	})

	defer server.Close()

	for _, method := range []string{http.MethodHead, http.MethodPost, http.MethodPut, http.MethodPatch, http.MethodDelete, http.MethodConnect, http.MethodOptions, http.MethodTrace} {
		t.Run(fmt.Sprintf("%s method is not allowed", method), func(t *testing.T) {
			req, err := http.NewRequest(method, server.URL, &bytes.Buffer{})

			if err != nil {
				t.Fatalf("unexpected error: %s", err)
			}

			resp, err := http.DefaultClient.Do(req)

			if err != nil {
				t.Fatalf("unexpected error: %s", err)
			}

			if resp.StatusCode != http.StatusMethodNotAllowed {
				t.Errorf("expected 405; got %d", resp.StatusCode)
			}
		})
	}

	for _, path := range []string{"/", "/foo", "/foo/bar", "/1", "/10", "/100"} {
		t.Run(fmt.Sprintf("Path without a year (%s) returns a not found response", path), func(t *testing.T) {
			resp, err := http.Get(server.URL + path)

			if err != nil {
				t.Fatalf("unexpected error: %s", err)
			}

			if resp.StatusCode != http.StatusNotFound {
				t.Errorf("expected 404; got %d", resp.StatusCode)
			}
		})
	}

	t.Run("Path with a year (2021) returns a successful response", func(t *testing.T) {
		resp, err := http.Get(server.URL + "/2021")

		if err != nil {
			t.Fatalf("unexpected error: %s", err)
		}

		if resp.StatusCode != http.StatusOK {
			t.Errorf("expected 200; got %d", resp.StatusCode)
		}

		arr, err := FakeBookshelf{}.Get(2021)

		if err != nil {
			t.Fatalf("unexpected error: %s", err)
		}

		expected, err := json.Marshal(arr)

		if err != nil {
			t.Fatalf("unexpected error: %s", err)
		}

		actual, err := ioutil.ReadAll(resp.Body)

		if err != nil {
			t.Fatalf("unexpected error: %s", err)
		}

		if string(actual) != string(expected) {
			t.Errorf("expected %s; got %s", expected, actual)
		}
	})
}
