namespace EstouALer.Domain;

/// <summary>
/// Represents a comic book.
/// </summary>
/// <param name="Date">Date the comic book was read.</param>
/// <param name="Publisher">Publisher of the comic book.</param>
/// <param name="Title">Title of the comic book.</param>
/// <param name="Format">Format of the comic book.</param>
/// <param name="Pages">Number of pages of the comic book.</param>
/// <param name="Issues">Number of issues of the comic book.</param>
public readonly record struct ComicBook(
    DateOnly Date,
    string Publisher,
    string Title,
    string Format,
    int Pages,
    int Issues);
