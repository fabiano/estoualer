namespace EstouALer.Data;

/// <summary>
/// Represents a bookshelf.
/// </summary>
/// <param name="books">The books.</param>
/// <param name="comicBooks">The comic books.</param>
public class Bookshelf(List<Book> books, List<ComicBook> comicBooks)
{
    /// <summary>
    /// Returns the books that match the provided predicate.
    /// </summary>
    /// <param name="predicate">The search predicate.</param>
    /// <returns>List of books.</returns>
    public List<Book> GetBooks(Func<Book, bool> predicate) => [.. books.Where(predicate)];

    /// <summary>
    /// Returns the comic books that match the provided predicate.
    /// </summary>
    /// <param name="predicate">The search predicate.</param>
    /// <returns>List of comic books.</returns>
    public List<ComicBook> GetComicBooks(Func<ComicBook, bool> predicate) => [.. comicBooks.Where(predicate)];

    /// <summary>
    /// Creates a bookshelf from the Sqlite database.
    /// </summary>
    /// <param name="path">The path and file name of the database.</param>
    /// <returns>A <see cref="Bookshelf"/> instance.</returns>
    public static Bookshelf Create(string path)
    {
        using var connection = new SqliteConnection($"Data Source={path};Mode=ReadOnly");

        connection.Open();

        var books = GetBooks(connection);
        var comicBooks = GetComicBooks(connection);

        return new Bookshelf(books, comicBooks);
    }

    private static List<Book> GetBooks(SqliteConnection connection)
    {
        var command = connection.CreateCommand();

        command.CommandText =
            """
            SELECT Date, Publisher, Title, Author, Format, Pages, Duration
            FROM Book
            ORDER BY Id DESC
            """;

        using var reader = command.ExecuteReader();

        var books = new List<Book>();

        while (reader.Read())
        {
            var book = new Book
            {
                Date = DateOnly.ParseExact(reader.GetString(0), "yyyy-MM-dd"),
                Publisher = reader.GetString(1),
                Title = reader.GetString(2),
                Author = reader.GetString(3),
                Format = reader.GetString(4),
                Pages = reader.GetInt32(5),
                Duration = Duration.Parse(reader.GetString(6)),
            };

            books.Add(book);
        }

        return books;
    }

    private static List<ComicBook> GetComicBooks(SqliteConnection connection)
    {
        var command = connection.CreateCommand();

        command.CommandText =
            """
            SELECT Date, Publisher, Title, Format, Pages, Issues
            FROM ComicBook
            ORDER BY Id DESC
            """;

        using var reader = command.ExecuteReader();

        var comicBooks = new List<ComicBook>();

        while (reader.Read())
        {
            var comicBook = new ComicBook
            {
                Date = DateOnly.ParseExact(reader.GetString(0), "yyyy-MM-dd"),
                Publisher = reader.GetString(1),
                Title = reader.GetString(2),
                Format = reader.GetString(3),
                Pages = reader.GetInt32(4),
                Issues = reader.GetInt32(5),
            };

            comicBooks.Add(comicBook);
        }

        return comicBooks;
    }
}
