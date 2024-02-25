using System.Text.Json;
using System.Text.Json.Serialization;
using System.Text.RegularExpressions;
using DocumentFormat.OpenXml.Packaging;
using DocumentFormat.OpenXml.Spreadsheet;
using Google.Cloud.Diagnostics.Common;

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

builder.Services.ConfigureHttpJsonOptions(options => options.SerializerOptions.Converters.Add(new ILengthConverter()));
builder.Services.AddSingleton(Bookshelf.Create("EstouALer.xlsx"));

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
class Bookshelf(List<ComicBook> comicBooks, List<Book> books)
{
    private readonly Dictionary<int, List<BookshelfItem>> cache = new List<BookshelfItem>()
        .Concat(comicBooks.Select(BookshelfItem.Create))
        .Concat(books.Select(BookshelfItem.Create))
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
    /// <param name="path">The path and file name of the spreadsheet.</param>
    /// <returns>A <see cref="Bookshelf"/> instance.</returns>
    public static Bookshelf Create(string path)
    {
        var document = SpreadsheetDocument.Open(path, false);
        var workbookPart = document.WorkbookPart!;

        var sharedStringTable = workbookPart
            .SharedStringTablePart!
            .SharedStringTable;

        var comicBooks = GetComicBooks(workbookPart, sharedStringTable);
        var books = GetBooks(workbookPart, sharedStringTable);

        return new(comicBooks, books);
    }

    private static List<ComicBook> GetComicBooks(
        WorkbookPart workbookPart,
        SharedStringTable sharedStringTable)
    {
        var sheetId = workbookPart
            .Workbook
            .Descendants<Sheet>()
            .Single(sheet => sheet.Name == "Quadrinhos")
            .Id;

        var worksheetPart = (WorksheetPart)workbookPart.GetPartById(sheetId!);

        var rows = worksheetPart
            .Worksheet
            .Descendants<Row>()
            .Skip(1);

        var comicBooks = new List<ComicBook>();

        foreach (var row in rows)
        {
            var comicBook = new ComicBook();

            foreach (var cell in row.Elements<Cell>())
            {
                var cellReference = cell.CellReference!.Value!;
                var cellInnerText = cell.GetInnerText(sharedStringTable);

                if (cellReference.StartsWith('A'))
                {
                    comicBook = comicBook with { Date = DateOnly.FromDateTime(DateTime.FromOADate(double.Parse(cellInnerText))) };
                }

                if (cellReference.StartsWith('B'))
                {
                    comicBook = comicBook with { Publisher = cellInnerText };
                }

                if (cellReference.StartsWith('C'))
                {
                    comicBook = comicBook with { Title = cellInnerText };
                }

                if (cellReference.StartsWith('D'))
                {
                    comicBook = comicBook with { Format = cellInnerText };
                }

                if (cellReference.StartsWith('E'))
                {
                    comicBook = comicBook with { Pages = int.Parse(cellInnerText) };
                }

                if (cellReference.StartsWith('F'))
                {
                    comicBook = comicBook with { Issues = int.Parse(cellInnerText) };
                }
            }

            comicBooks.Add(comicBook);
        }

        return comicBooks;
    }

    private static List<Book> GetBooks(
        WorkbookPart workbookPart,
        SharedStringTable sharedStringTable)
    {
        var sheetId = workbookPart
            .Workbook
            .Descendants<Sheet>()
            .Single(sheet => sheet.Name == "Livros")
            .Id;

        var worksheetPart = (WorksheetPart)workbookPart.GetPartById(sheetId!);

        var rows = worksheetPart
            .Worksheet
            .Descendants<Row>()
            .Skip(1);

        var books = new List<Book>();

        foreach (var row in rows)
        {
            var book = new Book();

            foreach (var cell in row.Elements<Cell>())
            {
                var cellReference = cell.CellReference!.Value!;
                var cellInnerText = cell.GetInnerText(sharedStringTable);

                if (cellReference.StartsWith('A'))
                {
                    book = book with { Date = DateOnly.FromDateTime(DateTime.FromOADate(double.Parse(cellInnerText))) };
                }

                if (cellReference.StartsWith('B'))
                {
                    book = book with { Publisher = cellInnerText };
                }

                if (cellReference.StartsWith('C'))
                {
                    book = book with { Title = cellInnerText };
                }

                if (cellReference.StartsWith('D'))
                {
                    book = book with { Author = cellInnerText };
                }

                if (cellReference.StartsWith('E'))
                {
                    book = book with { Format = cellInnerText };
                }

                if (cellReference.StartsWith('F'))
                {
                    book = book with { Length = cellInnerText };
                }
            }

            books.Add(book);
        }

        return books;
    }
}

/// <summary>
/// Represents a comic book.
/// </summary>
/// <param name="Date">Date the comic book was read.</param>
/// <param name="Publisher">Publisher of the comic book.</param>
/// <param name="Title">Title of the comic book.</param>
/// <param name="Pages">Number of pages of the comic book.</param>
/// <param name="Issues">Number of issues of the comic book.</param>
/// <param name="Format">Format of the comic book.</param>
readonly record struct ComicBook(
    DateOnly Date,
    string Publisher,
    string Title,
    int Pages,
    int Issues,
    string Format);

/// <summary>
/// Represents a book.
/// </summary>
/// <param name="Date">Date the book was read.</param>
/// <param name="Publisher">Publisher of the book.</param>
/// <param name="Title">Title of the book.</param>
/// <param name="Author">Author of the book.</param>
/// <param name="Length">Length of the book.</param>
/// <param name="Format">Format of the book.</param>
readonly record struct Book(
    DateOnly Date,
    string Publisher,
    string Title,
    string Author,
    string Length,
    string Format);

/// <summary>
/// Represents a bookshelf item.
/// </summary>
/// <param name="Type">Type of the item.</param>
/// <param name="Date">Date the item was read.</param>
/// <param name="Publisher">Publisher of the item.</param>
/// <param name="Title">Title of the item.</param>
/// <param name="Author">Author of the item.</param>
/// <param name="Length">Length of the item.</param>
/// <param name="Format">Format of the item.</param>
readonly record struct BookshelfItem(
    string Type,
    DateOnly Date,
    string Publisher,
    string Title,
    string Author,
    ILength Length,
    string Format)
{
    public static BookshelfItem Create(ComicBook comicBook) => new(
        "ComicBook",
        comicBook.Date,
        comicBook.Publisher,
        comicBook.Title,
        string.Empty,
        new ComicBookLength(comicBook.Pages, comicBook.Issues),
        comicBook.Format);

    public static BookshelfItem Create(Book book) => new(
        "Book",
        book.Date,
        book.Publisher,
        book.Title,
        book.Author,
        ParseLength(book.Length),
        book.Format);

    static ILength ParseLength(string value)
    {
        var pattern = @"^(?<Hours>\d+)h (?<Minutes>\d+)m|(?<Hours>\d+)h|(?<Minutes>\d+)m|(?<Pages>\d+)$";
        var re = new Regex(pattern, RegexOptions.Compiled);
        var match = re.Match(value);

        if (!match.Success)
        {
            throw new InvalidOperationException($"{value} is not in a recognizable format.");
        }

        var hours = match.Groups["Hours"]?.Value;
        var minutes = match.Groups["Minutes"]?.Value;
        var pages = match.Groups["Pages"]?.Value;

        if (!string.IsNullOrEmpty(hours) || !string.IsNullOrEmpty(minutes))
        {
            return new AudioBookLength(hours ?? string.Empty, minutes ?? string.Empty);
        }

        return new BookLength(pages ?? string.Empty);
    }
}

/// <summary>
/// Represents a bookshelf item length.
/// </summary>
partial interface ILength;

/// <summary>
/// Represents a book length.
/// </summary>
/// <param name="Pages">Number of pages.</param>
readonly record struct BookLength(string Pages) : ILength;

/// <summary>
/// Represents an audiobook length.
/// </summary>
/// <param name="Hours">Number of hours.</param>
/// <param name="Minutes">Number of minutes.</param>
readonly record struct AudioBookLength(string Hours, string Minutes) : ILength;

/// <summary>
/// Represents a comicbook length.
/// </summary>
/// <param name="Pages">Number of pages.</param>
/// <param name="Issues">Number of issues.</param>
readonly record struct ComicBookLength(int Pages, int Issues) : ILength;

/// <summary>
/// Length JSON converter.
/// </summary>
class ILengthConverter : JsonConverter<ILength>
{
    public override ILength? Read(ref Utf8JsonReader reader, Type typeToConvert, JsonSerializerOptions options)
    {
        throw new NotImplementedException();
    }

    public override void Write(Utf8JsonWriter writer, ILength value, JsonSerializerOptions options)
    {
        var type = value.GetType();

        JsonSerializer.Serialize(writer, value, type, options);
    }
}

static class Extensions
{
    public static string GetInnerText(this Cell cell, SharedStringTable sharedStringTable)
    {
        var innerText = cell.InnerText;

        if (cell.DataType == null)
        {
            return innerText;
        }

        if (cell.DataType == CellValues.SharedString)
        {
            var sharedString = sharedStringTable.ElementAt(int.Parse(innerText));

            return sharedString.InnerText;
        }

        return innerText;
    }
}
