namespace EstouALer.Domain;

/// <summary>
/// Represents a book.
/// </summary>
/// <param name="Date">Date the book was read.</param>
/// <param name="Publisher">Publisher of the book.</param>
/// <param name="Title">Title of the book.</param>
/// <param name="Format">Format of the book.</param>
/// <param name="Pages">Number of pages of the book.</param>
/// <param name="Duration">Duration of the book.</param>
public readonly record struct Book(
    DateOnly Date,
    string Publisher,
    string Title,
    string Format,
    int Pages,
    Duration Duration);

/// <summary>
/// Represents an audio book duration.
/// </summary>
/// <param name="Hours">Number of hours.</param>
/// <param name="Minutes">Number of minutes.</param>
public readonly partial record struct Duration(int Hours, int Minutes)
{
    [GeneratedRegex(@"^((?<Hours>\d+?)h)?\s{0,1}((?<Minutes>\d+?)m)?$")]
    private static partial Regex CreateDurationRegex();

    /// <summary>
    /// Parses the value to a <see cref="Duration"/>.
    /// </summary>
    /// <param name="value">The value to parse.</param>
    /// <returns>An instance of <see cref="Duration"/>.</returns>
    public static Duration Parse(string value)
    {
        var match = CreateDurationRegex().Match(value);

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
