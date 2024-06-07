using System.Text.RegularExpressions;
using Google.Cloud.Diagnostics.Common;
using Microsoft.Data.Sqlite;

var builder = WebApplication.CreateBuilder(args);

builder.Logging.ClearProviders();

if (builder.Environment.IsProduction())
{
    builder.Logging.AddGoogle();
}
else
{
    builder.Logging.AddConsole();
}

builder.Services.AddSingleton(Bookshelf.Create("Bookshelf.db"));

var app = builder.Build();

app.MapGet("/Bookshelf/{year:int}", (int year, Bookshelf bookshelf) =>
{
    var items = bookshelf.Get(year);

    return TypedResults.Ok(items);
});

app.UseDefaultFiles();
app.UseStaticFiles();

var port = Environment.GetEnvironmentVariable("PORT");

app.Run(port is not null
    ? $"http://0.0.0.0:{port}"
    : null);

/// <summary>
/// Represents a bookshelf.
/// </summary>
/// <param name="comicBooks">The comic books.</param>
/// <param name="books">The books.</param>
class Bookshelf(List<BookshelfItem> comicBooks, List<BookshelfItem> books)
{
    private readonly Dictionary<int, List<BookshelfItem>> cache = new List<BookshelfItem>()
        .Concat(comicBooks)
        .Concat(books)
        .OrderBy(item => item.Date)
        .GroupBy(
            key => key.Date.Year,
            element => element, (key, element) => new { Year = key, Items = element.ToList() })
        .ToDictionary(key => key.Year, element => element.Items);

    /// <summary>
    /// Returns all the comic books and books read in the requested year.
    /// </summary>
    /// <param name="year">The year.</param>
    /// <returns>The comic books and books read in the requested year.</returns>
    public List<BookshelfItem> Get(int year) => cache.GetValueOrDefault(year, []);

    /// <summary>
    /// Creates a bookshelf from the spreadsheet.
    /// </summary>
    /// <param name="path">The path and file name of the database.</param>
    /// <returns>A <see cref="Bookshelf"/> instance.</returns>
    public static Bookshelf Create(string path)
    {
        using var connection = new SqliteConnection($"Data Source={path};Mode=ReadOnly");

        connection.Open();

        var comicBooks = GetComicBooks(connection);
        var books = GetBooks(connection);

        return new(comicBooks, books);
    }

    private static List<BookshelfItem> GetComicBooks(SqliteConnection connection)
    {
        var command = connection.CreateCommand();

        command.CommandText =
        @"
            SELECT Date, Publisher, Title, Format, Pages, Issues
            FROM ComicBook
            ORDER BY Date, Title
        ";

        using var reader = command.ExecuteReader();

        var comicBooks = new List<BookshelfItem>();

        while (reader.Read())
        {
            var comicBook = new BookshelfItem
            {
                Type = "ComicBook",
                Date =  DateOnly.ParseExact(reader.GetString(0), "yyyy-MM-dd"),
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

    private static List<BookshelfItem> GetBooks(SqliteConnection connection)
    {
        var command = connection.CreateCommand();

        command.CommandText =
        @"
            SELECT Date, Publisher, Title, Format, Pages, Duration
            FROM Book
            ORDER BY Date, Title
        ";

        using var reader = command.ExecuteReader();

        var books = new List<BookshelfItem>();

        while (reader.Read())
        {
            var book = new BookshelfItem
            {
                Type = "Book",
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
}

/// <summary>
/// Represents a bookshelf item.
/// </summary>
/// <param name="Type">Type of the item.</param>
/// <param name="Date">Date the item was read.</param>
/// <param name="Publisher">Publisher of the item.</param>
/// <param name="Title">Title of the item.</param>
/// <param name="Format">Format of the item.</param>
/// <param name="Pages">Number of pages of the item.</param>
/// <param name="Issues">Number of issues of the item.</param>
/// <param name="Duration">Duration of the item.</param>
readonly record struct BookshelfItem(
    string Type,
    DateOnly Date,
    string Publisher,
    string Title,
    string Format,
    int Pages,
    int Issues,
    Duration Duration);

/// <summary>
/// Represents an audio book duration.
/// </summary>
/// <param name="Hours">Number of hours.</param>
/// <param name="Minutes">Number of minutes.</param>
readonly record struct Duration(int Hours, int Minutes)
{
    /// <summary>
    /// Parses the value to a <see cref="Duration"/>.
    /// </summary>
    /// <param name="value">The value to parse.</param>
    /// <returns>An instance of <see cref="Duration"/>.</returns>
    /// <exception cref="InvalidOperationException"></exception>
    public static Duration Parse(string value)
    {
        var pattern = @"^((?<Hours>\d+?)h)?\s{0,1}((?<Minutes>\d+?)m)?$";
        var re = new Regex(pattern, RegexOptions.Compiled);
        var match = re.Match(value);

        if (!match.Success)
        {
            throw new InvalidOperationException($"{value} is not in a recognizable format.");
        }

        var duration = new Duration();
        var hours = match.Groups["Hours"]?.Value;
        var minutes = match.Groups["Minutes"]?.Value;

        if (!string.IsNullOrEmpty(hours))
        {
            duration = duration with { Hours = int.Parse(hours) };
        }

        if (!string.IsNullOrEmpty(minutes))
        {
            duration = duration with { Minutes = int.Parse(minutes) };
        }

        return duration;
    }
}
