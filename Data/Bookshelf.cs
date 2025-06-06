namespace EstouALer.Data;

/// <summary>
/// Represents a bookshelf.
/// </summary>
/// <param name="books">The books.</param>
/// <param name="comicBooks">The comic books.</param>
public class Bookshelf(List<Book> books, List<ComicBook> comicBooks)
{
    /// <summary>
    /// Returns the books read in the provided year.
    /// </summary>
    /// <param name="year">The year.</param>
    /// <returns>List of books</returns>
    public List<Book> GetBooks(int year) => [.. books.Where(book => book.Date.Year == year)];

    /// <summary>
    /// Returns the comic books read in the provided year.
    /// </summary>
    /// <param name="year">The year.</param>
    /// <returns>List of comic books.</returns>
    public List<ComicBook> GetComicBooks(int year) => [.. comicBooks.Where(comicBook => comicBook.Date.Year == year)];

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

        return new(books, comicBooks);
    }

    private static List<Book> GetBooks(SqliteConnection connection)
    {
        var command = connection.CreateCommand();

        command.CommandText =
        @"
            SELECT Date, Publisher, Title, Format, Pages, Duration
            FROM Book
            ORDER BY Id DESC
        ";

        using var reader = command.ExecuteReader();

        var books = new List<Book>();

        while (reader.Read())
        {
            var book = new Book
            {
                Date = DateOnly.ParseExact(reader.GetString(0), "yyyy-MM-dd"),
                Publisher = reader.GetString(1),
                Title = reader.GetString(2),
                Format = reader.GetString(3),
                Pages = reader.GetInt32(4),
                Duration = Duration.Parse(reader.GetString(5)),
            };

            books.Add(book);
        }

        return books;
    }

    private static List<ComicBook> GetComicBooks(SqliteConnection connection)
    {
        var command = connection.CreateCommand();

        command.CommandText =
        @"
            SELECT Date, Publisher, Title, Format, Pages, Issues
            FROM ComicBook
            ORDER BY Id DESC
        ";

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
