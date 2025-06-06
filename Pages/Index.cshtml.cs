namespace EstouALer.Pages;

public class IndexModel(Bookshelf bookshelf) : PageModel
{
    public int Year { get; set; }

    public Statistics Stats { get; set; } = new();

    public List<Book> Books { get; set; } = [];

    public List<ComicBook> ComicBooks { get; set; } = [];

    public void OnGet(int? year)
    {
        var theYear = year ?? DateTime.UtcNow.Year;
        var books = bookshelf.GetBooks(theYear);
        var comicBooks = bookshelf.GetComicBooks(theYear);

        Year = theYear;
        Stats = GenerateStatistics(books, comicBooks);
        Books = books;
        ComicBooks = comicBooks;
    }

    private static Statistics GenerateStatistics(List<Book> books, List<ComicBook> comicBooks)
    {
        bool isPaper(string format) => format == "Capa comum" || format == "Capa dura";
        bool isAudio(string format) => format == "Audiolivro";
        bool isEBook(string format) => format == "eBook";

        return new(
            Total: books.Count + comicBooks.Count,
            Books: books.Count,
            ComicBooks: comicBooks.Count,
            Paper: books.Count(book => isPaper(book.Format)) + comicBooks.Count(comicBook => isPaper(comicBook.Format)),
            Audio: books.Count(book => isAudio(book.Format)) + comicBooks.Count(comicBook => isAudio(comicBook.Format)),
            eBook: books.Count(book => isEBook(book.Format)) + comicBooks.Count(comicBook => isEBook(comicBook.Format))
        );
    }

    public record Statistics(
        int Total = 0,
        int Books = 0,
        int ComicBooks = 0,
        int Paper = 0,
        int Audio = 0,
        int eBook = 0);
}
