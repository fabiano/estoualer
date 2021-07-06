package main

import (
	"bytes"
	"encoding/json"
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

func TestMethodNotAllowed(t *testing.T) {
	server := httptest.NewServer(App{
		Logger:    log.New(os.Stdout, "test: ", log.LstdFlags),
		Bookshelf: FakeBookshelf{},
	})

	defer server.Close()

	for _, method := range []string{"OPTIONS", "HEAD", "POST", "PUT", "DELETE", "TRACE", "CONNECT"} {
		req, err := http.NewRequest(method, server.URL, &bytes.Buffer{})

		if err != nil {
			t.Fatalf("Unexpected error: %s", err)
		}

		resp, err := http.DefaultClient.Do(req)

		if err != nil {
			t.Fatalf("Unexpected error: %s", err)
		}

		if resp.StatusCode != http.StatusMethodNotAllowed {
			t.Errorf("Expected 405; got %d", resp.StatusCode)
		}
	}
}

func TestBadRequest(t *testing.T) {
	server := httptest.NewServer(App{
		Logger:    log.New(os.Stdout, "test: ", log.LstdFlags),
		Bookshelf: FakeBookshelf{},
	})

	defer server.Close()

	for _, path := range []string{"/", "/foo", "/foo/bar"} {
		resp, err := http.Get(server.URL + path)

		if err != nil {
			t.Fatalf("Unexpected error: %s", err)
		}

		if resp.StatusCode != http.StatusBadRequest {
			t.Errorf("Expected 400; got %d", resp.StatusCode)
		}
	}
}

func TestSuccess(t *testing.T) {
	server := httptest.NewServer(App{
		Logger:    log.New(os.Stdout, "test: ", log.LstdFlags),
		Bookshelf: FakeBookshelf{},
	})

	defer server.Close()

	resp, err := http.Get(server.URL + "/2021")

	if err != nil {
		t.Fatalf("Unexpected error: %s", err)
	}

	if resp.StatusCode != http.StatusOK {
		t.Errorf("Expected 200; got %d", resp.StatusCode)
	}

	arr, err := FakeBookshelf{}.Get(2021)

	if err != nil {
		t.Fatalf("Unexpected error: %s", err)
	}

	expected, err := json.Marshal(arr)

	if err != nil {
		t.Fatalf("Unexpected error: %s", err)
	}

	actual, err := ioutil.ReadAll(resp.Body)

	if err != nil {
		t.Fatalf("Unexpected error: %s", err)
	}

	if string(actual) != string(expected) {
		t.Errorf("Expected %s; got %s", expected, actual)
	}
}
