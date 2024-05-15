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

    private static List<BookshelfItem> GetComicBooks(
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

        var comicBooks = new List<BookshelfItem>();

        foreach (var row in rows)
        {
            var comicBook = new BookshelfItem
            {
                Type = "ComicBook",
            };

            foreach (var cell in row.Elements<Cell>())
            {
                var cellReference = cell.CellReference!.Value!;
                var cellInnerText = cell.GetInnerText(sharedStringTable);

                if (cellReference.StartsWith('A') && !string.IsNullOrEmpty(cellInnerText))
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
                    comicBook = comicBook with { Length = comicBook.Length with { Pages = int.Parse(cellInnerText) } };
                }

                if (cellReference.StartsWith('F'))
                {
                    comicBook = comicBook with { Length = comicBook.Length with { Issues = int.Parse(cellInnerText) } };
                }
            }

            comicBooks.Add(comicBook);
        }

        return comicBooks;
    }

    private static List<BookshelfItem> GetBooks(
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

        var books = new List<BookshelfItem>();

        foreach (var row in rows)
        {
            var book = new BookshelfItem
            {
                Type = "Book",
            };

            foreach (var cell in row.Elements<Cell>())
            {
                var cellReference = cell.CellReference!.Value!;
                var cellInnerText = cell.GetInnerText(sharedStringTable);

                if (cellReference.StartsWith('A') && !string.IsNullOrEmpty(cellInnerText))
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
                    book = book with { Length = Length.Parse(cellInnerText) };
                }
            }

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
/// <param name="Author">Author of the item.</param>
/// <param name="Length">Length of the item.</param>
/// <param name="Format">Format of the item.</param>
readonly record struct BookshelfItem(
    string Type,
    DateOnly Date,
    string Publisher,
    string Title,
    string Author,
    Length Length,
    string Format);

/// <summary>
/// Represents a bookshelf item length.
/// </summary>
/// <param name="Pages">Number of pages.</param>
/// <param name="Issues">Number of issues.</param>
/// <param name="Hours">Number of hours.</param>
/// <param name="Minutes">Number of minutes.</param>
readonly record struct Length(int Pages, int Issues, int Hours, int Minutes)
{
    /// <summary>
    /// Parses the value to a <see cref="Length"/>.
    /// </summary>
    /// <param name="value">The value to parse.</param>
    /// <returns>An instance of <see cref="Length"/>.</returns>
    /// <exception cref="InvalidOperationException"></exception>
    public static Length Parse(string value)
    {
        var pattern = @"^(?<Pages>\d+)$|^((?<Hours>\d+?)h)?\s{0,1}((?<Minutes>\d+?)m)?$";
        var re = new Regex(pattern, RegexOptions.Compiled);
        var match = re.Match(value);

        if (!match.Success)
        {
            throw new InvalidOperationException($"{value} is not in a recognizable format.");
        }

        var length = new Length();
        var pages = match.Groups["Pages"]?.Value;
        var hours = match.Groups["Hours"]?.Value;
        var minutes = match.Groups["Minutes"]?.Value;

        if (!string.IsNullOrEmpty(pages))
        {
            length = length with { Pages = int.Parse(pages) };
        }

        if (!string.IsNullOrEmpty(hours))
        {
            length = length with { Hours = int.Parse(hours) };
        }

        if (!string.IsNullOrEmpty(minutes))
        {
            length = length with { Minutes = int.Parse(minutes) };
        }

        return length;
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
