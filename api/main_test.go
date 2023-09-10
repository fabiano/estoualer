package main

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io"
	"log"
	"net/http"
	"net/http/httptest"
	"os"
	"testing"
)

var comicBooks = []ComicBook{
	{Publisher: "Marvel", Title: "Annihilation Omnibus", Pages: 880, Issues: 30, Format: "Capa dura"},
	{Publisher: "Marvel", Title: "Annihilation: Conquest Omnibus", Pages: 872, Issues: 33, Format: "Capa dura"},
}

type FakeBookshelf struct {
}

func (b FakeBookshelf) Get(year int) ([]ComicBook, error) { //revive:disable:unused-parameter
	return comicBooks, nil
}

func TestDefaultHandler(t *testing.T) {
	server := httptest.NewServer(DefaultHandler{
		Logger:    log.New(os.Stdout, "test: ", log.LstdFlags),
		Bookshelf: FakeBookshelf{},
	})

	defer server.Close()

	for _, method := range []string{http.MethodHead, http.MethodPost, http.MethodPut, http.MethodPatch, http.MethodDelete, http.MethodConnect, http.MethodTrace} {
		t.Run(fmt.Sprintf("%s method is not allowed", method), func(t *testing.T) {
			req, err := http.NewRequest(method, server.URL+"/2021", &bytes.Buffer{})

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

	t.Run("Preflight request returns the correct status and headers", func(t *testing.T) {
		req, err := http.NewRequest(http.MethodOptions, server.URL+"/2021", &bytes.Buffer{})

		req.Header.Add("Access-Control-Request-Method", "GET")
		req.Header.Add("Access-Control-Request-Headers", "Content-Type")

		if err != nil {
			t.Fatalf("unexpected error: %s", err)
		}

		resp, err := http.DefaultClient.Do(req)

		if err != nil {
			t.Fatalf("unexpected error: %s", err)
		}

		if resp.StatusCode != http.StatusNoContent {
			t.Errorf("expected 204; got %d", resp.StatusCode)
		}

		if v := resp.Header.Get("Access-Control-Allow-Origin"); v != "*" {
			t.Errorf("expected access-control-allow-origin header equal to *; got %s", v)
		}

		if v := resp.Header.Get("Access-Control-Allow-Methods"); v != "GET" {
			t.Errorf("expected access-control-allow-methods header equal to GET; got %s", v)
		}

		if v := resp.Header.Get("Access-Control-Allow-Headers"); v != "Content-Type" {
			t.Errorf("expected access-control-allow-headers header equal to Content-Type ; got %s", v)
		}

		if v := resp.Header.Get("Vary"); v != "Origin" {
			t.Errorf("expected vary header equal to Origin; got %s", v)
		}
	})

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

	t.Run("Path with a year returns a successful response", func(t *testing.T) {
		resp, err := http.Get(server.URL + "/2021")

		if err != nil {
			t.Fatalf("unexpected error: %s", err)
		}

		if resp.StatusCode != http.StatusOK {
			t.Errorf("expected 200; got %d", resp.StatusCode)
		}

		actual, err := io.ReadAll(resp.Body)

		if err != nil {
			t.Fatalf("unexpected error: %s", err)
		}

		expected, _ := json.Marshal(comicBooks)

		if string(actual) != string(expected) {
			t.Errorf("expected %s; got %s", expected, actual)
		}

		if v := resp.Header.Get("Content-Type"); v != "application/json" {
			t.Errorf("expected Content-Type header equal to application/json; got %s", v)
		}
	})
}
