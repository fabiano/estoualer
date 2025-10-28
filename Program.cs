var builder = WebApplication.CreateBuilder(args);

builder.Logging.ClearProviders();
builder.Logging.AddConsole();

builder.Services.AddRazorPages();

const string databasePath = "Bookshelf.db";

if (builder.Environment.IsProduction())
{
    builder.Services.AddSingleton(Bookshelf.Create(databasePath));
}
else
{
    builder.Services.AddScoped(_ => Bookshelf.Create(databasePath));
}

var app = builder.Build();

app.UseStaticFiles();
app.MapRazorPages();

var port = Environment.GetEnvironmentVariable("PORT");

app.Run(port is not null
    ? $"http://0.0.0.0:{port}"
    : null);
