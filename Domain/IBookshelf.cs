namespace EstouALer.Domain;

/// <summary>
/// Represents a bookshelf.
/// </summary>
public interface IBookshelf
{
    /// <summary>
    /// Returns the books read in the provided year.
    /// </summary>
    /// <param name="year">The year.</param>
    /// <returns>List of books</returns>
    List<Book> GetBooks(int year);

    /// <summary>
    /// Returns the comic books read in the provided year.
    /// </summary>
    /// <param name="year">The year.</param>
    /// <returns>List of comic books.</returns>
    List<ComicBook> GetComicBooks(int year);
}
