using DocumentFormat.OpenXml.Packaging;
using DocumentFormat.OpenXml.Spreadsheet;

var builder = WebApplication.CreateBuilder(args);

builder.Services.AddSingleton(Bookshelf.Create("EstouALer.xlsx"));

var app = builder.Build();

app.MapGet("/Bookshelf/{year:int}", (int year, Bookshelf bookshelf) =>
{
    var comicBooks = bookshelf.Get(year);

    return TypedResults.Ok(comicBooks);
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
class Bookshelf(List<ComicBook> comicBooks)
{
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
        var number = 1;

        foreach (var row in rows)
        {
            var date = DateOnly.MinValue;
            var publisher = string.Empty;
            var title = string.Empty;
            var pages = 0;
            var issues = 0;
            var format = string.Empty;

            foreach (var cell in row.Elements<Cell>())
            {
                var cellReference = cell.CellReference!.Value!;
                var cellInnerText = cell.InnerText;

                if (cellReference.StartsWith('A'))
                {
                    date = DateOnly.FromDateTime(DateTime.FromOADate(double.Parse(cellInnerText)));
                }

                if (cellReference.StartsWith('B'))
                {
                    var sharedString = sharedStringTable.ElementAt(int.Parse(cellInnerText));

                    publisher = sharedString.InnerText;
                }

                if (cellReference.StartsWith('C'))
                {
                    var sharedString = sharedStringTable.ElementAt(int.Parse(cellInnerText));

                    title = sharedString.InnerText;
                }

                if (cellReference.StartsWith('D'))
                {
                    pages = int.Parse(cellInnerText);
                }

                if (cellReference.StartsWith('E'))
                {
                    issues = int.Parse(cellInnerText);
                }

                if (cellReference.StartsWith('F'))
                {
                    var sharedString = sharedStringTable.ElementAt(int.Parse(cellInnerText));

                    format = sharedString.InnerText;
                }
            }

            comicBooks.Add(new(number, date, publisher, title, pages, issues, format));

            number++;
        }

        return new Bookshelf(comicBooks);
    }

    /// <summary>
    /// Returns all the comic books for the requested year.
    /// </summary>
    /// <param name="year">the year.</param>
    /// <returns>The comic books for the requested year.</returns>
    public IEnumerable<ComicBook> Get(int year) => comicBooks.Where(comicBook => comicBook.Date.Year == year);
}

/// <summary>
/// Represents a comic book.
/// </summary>
/// <param name="Number">Date the comic book was read.</param>
/// <param name="Date">Date the comic book was read.</param>
/// <param name="Publisher">Publisher of the comic book.</param>
/// <param name="Title">Title of the comic book.</param>
/// <param name="Pages">Number of pages of the comic book.</param>
/// <param name="Issues">Number of issues of the comic book.</param>
/// <param name="Format">Format of the comic book.</param>
record ComicBook(
    int Number,
    DateOnly Date,
    string Publisher,
    string Title,
    int Pages,
    int Issues,
    string Format);
