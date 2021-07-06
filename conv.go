package main

import (
	"encoding/json"
	"strconv"
	"time"
)

// Represents a Time that may be null.
type NullableTime struct {
	HasValue bool
	Value    time.Time
}

// Returns the JSON encoding of the NullableTime.
func (nt NullableTime) MarshalJSON() ([]byte, error) {
	if nt.HasValue {
		return json.Marshal(nt.Value)
	}

	return []byte("null"), nil
}

// Converts the value to NullableTime or returns an empty NullableTime.
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

// Converts the value to string or returns an empty string.
func ToStringOrDefault(i interface{}) string {
	s, ok := i.(string)

	if !ok {
		return ""
	}

	return s
}

// Converts the value to int or returns zero.
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
