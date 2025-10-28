namespace EstouALer.Pages;

public class IndexModel(Bookshelf bookshelf) : PageModel
{
    [BindProperty(SupportsGet = true)]
    public string? Q { get; set; }

    public Statistics Stats { get; set; } = new();

    public List<Book> Books { get; set; } = [];

    public List<ComicBook> ComicBooks { get; set; } = [];

    public void OnGet()
    {
        if (string.IsNullOrWhiteSpace(Q))
        {
            Q = $"ano: {DateTime.Now.Year}";
        }
        else
        {
            Q = Q
                .Trim()
                .ToLowerInvariant();
        }

        var (booksPredicate, comicBooksPredicate) = GetPredicates();
        var books = bookshelf.GetBooks(booksPredicate);
        var comicBooks = bookshelf.GetComicBooks(comicBooksPredicate);

        Stats = GenerateStatistics(books, comicBooks);
        Books = books;
        ComicBooks = comicBooks;
    }

    private (Func<Book, bool>, Func<ComicBook, bool>) GetPredicates()
    {
        Func<Book, bool> booksPredicate;
        Func<ComicBook, bool> comicBooksPredicate;

        switch (Q)
        {
            case { } s when s.StartsWith("ano:") && s.Length > 4 && int.TryParse(s[4..], out var year):
                booksPredicate = book => book.Date.Year == year;
                comicBooksPredicate = comicBook => comicBook.Date.Year == year;

                break;

            case { } s when s.StartsWith("editora:") && s.Length > 8:
                var publisher = s[8..].Trim();

                booksPredicate = book => book.Publisher.Contains(publisher, StringComparison.OrdinalIgnoreCase);
                comicBooksPredicate = comicBook => comicBook.Publisher.Contains(publisher, StringComparison.OrdinalIgnoreCase);

                break;

            case { } s when s.StartsWith("titulo:") && s.Length > 7:
                var title = s[7..].Trim();

                booksPredicate = book => book.Title.Contains(title, StringComparison.OrdinalIgnoreCase);
                comicBooksPredicate = comicBook => comicBook.Title.Contains(title, StringComparison.OrdinalIgnoreCase);

                break;

            case { } s when s.StartsWith("autor:") && s.Length > 6:
                var author = s[6..].Trim();

                booksPredicate = book => book.Author.Contains(author, StringComparison.OrdinalIgnoreCase);
                comicBooksPredicate = comicBook => false;

                break;

            default:
                booksPredicate = book =>
                    book.Title.Contains(Q!, StringComparison.OrdinalIgnoreCase) ||
                    book.Publisher.Contains(Q!, StringComparison.OrdinalIgnoreCase);

                comicBooksPredicate = comicBook =>
                    comicBook.Title.Contains(Q!, StringComparison.OrdinalIgnoreCase) ||
                    comicBook.Publisher.Contains(Q!, StringComparison.OrdinalIgnoreCase);

                break;
        }

        return (booksPredicate, comicBooksPredicate);
    }

    private static Statistics GenerateStatistics(List<Book> books, List<ComicBook> comicBooks)
    {
        return new Statistics(
            Total: books.Count + comicBooks.Count,
            Books: books.Count,
            ComicBooks: comicBooks.Count,
            Paper: books.Count(book => IsPaper(book.Format)) + comicBooks.Count(comicBook => IsPaper(comicBook.Format)),
            Audio: books.Count(book => IsAudio(book.Format)) + comicBooks.Count(comicBook => IsAudio(comicBook.Format)),
            eBook: books.Count(book => IsEBook(book.Format)) + comicBooks.Count(comicBook => IsEBook(comicBook.Format))
        );

        bool IsEBook(string format) => format is "eBook";
        bool IsAudio(string format) => format is "Audiolivro";
        bool IsPaper(string format) => format is "Capa comum" or "Capa dura";
    }

    public record Statistics(
        int Total = 0,
        int Books = 0,
        int ComicBooks = 0,
        int Paper = 0,
        int Audio = 0,
        int eBook = 0);
}
