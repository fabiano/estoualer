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

builder.Services.AddRazorPages();
builder.Services.AddSingleton<IBookshelf>(SqliteBookshelf.Create("Bookshelf.db"));

var app = builder.Build();

app.UseStaticFiles();
app.MapRazorPages();

var port = Environment.GetEnvironmentVariable("PORT");

app.Run(port is not null
    ? $"http://0.0.0.0:{port}"
    : null);
