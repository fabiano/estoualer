package main

import (
	"encoding/json"
	"strconv"
	"time"
)

// NullableTime represents a Time that may be null.
type NullableTime struct {
	HasValue bool
	Value    time.Time
}

// MarshalJSON returns the JSON encoding of the NullableTime.
func (nt NullableTime) MarshalJSON() ([]byte, error) {
	if nt.HasValue {
		return json.Marshal(nt.Value)
	}

	return []byte("null"), nil
}

// ToNullableTimeOrDefault converts the value to NullableTime or returns an empty NullableTime.
func ToNullableTimeOrDefault(i interface{}) NullableTime {
	s, ok := i.(string)

	if !ok {
		return NullableTime{}
	}

	value, err := time.Parse("01-02-06", s)

	if err != nil {
		return NullableTime{}
	}

	return NullableTime{HasValue: true, Value: value}
}

// ToStringOrDefault converts the value to string or returns an empty string.
func ToStringOrDefault(i interface{}) string {
	s, ok := i.(string)

	if !ok {
		return ""
	}

	return s
}

// ToIntOrDefault converts the value to int or returns zero.
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
