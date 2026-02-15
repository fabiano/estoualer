<?php

$today = getdate();
$q = trim($_GET["q"] ?? "");

if (strlen($q) == 0) {
    $q = "ano: " . $today["year"];
}

$db = new SQLite3("Bookshelf.db");
$books = get_books($db, $q);
$comicBooks = get_comicbooks($db, $q);
$stats = generate_statistics($books, $comicBooks);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="Os livros e gibis que li.">
    <title>Estou a ler</title>
    <link rel="shortcut icon" href="icon.ico">
    <link rel="icon" sizes="16x16" href="icon-16.png">
    <link rel="icon" sizes="20x20" href="icon-20.png">
    <link rel="icon" sizes="24x24" href="icon-24.png">
    <link rel="icon" sizes="32x32" href="icon-32.png">
    <link rel="icon" sizes="48x48" href="icon-48.png">
    <link rel="icon" sizes="64x64" href="icon-64.png">
    <link rel="icon" sizes="128x128" href="icon-128.png">
    <link rel="icon" sizes="256x256" href="icon-256.png">
    <link rel="icon" sizes="512x512" href="icon-512.png">
    <link rel="icon" sizes="1024x1024" href="icon-1024.png">
    <link rel="icon" sizes="2048x2048" href="icon-2048.png">
    <link rel="icon" sizes="4096x4096" href="icon-4096.png">
    <link rel="manifest" href="site.webmanifest">
    <link rel="stylesheet" href="site.css">
</head>

<body>
    <div class="progress"></div>
    <div class="container">
        <div class="header">
            <h1>Estou a ler</h1>
            <div class="stats" id="stats">
                <div class="item total">
                    <p class="heading">Total</p>
                    <p class="value"><?php echo $stats["Total"] ?></p>
                </div>
                <div class="item books">
                    <p class="heading">Livros</p>
                    <p class="value"><?php echo $stats["Books"] ?></p>
                </div>
                <div class="item comicbooks">
                    <p class="heading">Gibis</p>
                    <p class="value"><?php echo $stats["ComicBooks"] ?></p>
                </div>
                <hr class="separator">
                <div class="item paper">
                    <p class="heading">Em papel</p>
                    <p class="value"><?php echo $stats["Paper"] ?></p>
                </div>
                <div class="item audio">
                    <p class="heading">Em áudio</p>
                    <p class="value"><?php echo $stats["Audio"] ?></p>
                </div>
                <div class="item ebook">
                    <p class="heading">eBook</p>
                    <p class="value"><?php echo $stats["eBook"] ?></p>
                </div>
            </div>
        </div>
    </div>
    <hr class="separator">
    <div class="container">
        <div class="body">
            <form method="get" class="search">
                <input id="q" name="q" value="<?php echo $q ?>" placeholder="ano: 2025 ou autor: carla madeira ou titulo: a natureza da mordida">
            </form>
            <?php if ($stats["Books"] > 0) { ?>
                <h2>Livros</h2>
                <div class="cards" id="books">
                    <?php while ($book = $books->fetchArray()) { ?>
                        <div class="card">
                            <div>
                                <div class="number"><?php echo $stats["Books"]-- ?></div>
                                <div class="date"><?php echo DateTime::createFromFormat("Y-m-d", $book["Date"])->format("d/m/Y") ?></div>
                            </div>
                            <div>
                                <div class="title"><?php echo $book["Title"] ?></div>
                                <div class="publisher-and-format"><?php echo $book["Author"] ?> / <?php echo $book["Format"] ?></div>
                            </div>
                            <div>
                                <div class="length">
                                    <?php

                                        $pages = $book["Pages"];
                                        $duration = $book["Duration"];
                                        $hours = 0;
                                        $minutes = 0;

                                        if (preg_match("/^((?<Hours>\d+?)h)?\s{0,1}((?<Minutes>\d+?)m)?$/", $duration, $matches) == 1) {
                                            $hours = $matches["Hours"] ?? 0;
                                            $minutes = $matches["Minutes"] ?? 0;
                                        }

                                        echo match (true) {
                                            $pages > 0 => $pages . " páginas",
                                            $hours > 1 and $minutes > 1 => $hours . " horas e " . $minutes . " minutos",
                                            $hours == 1 and $minutes > 1 => $hours . " hora e " . $minutes . " minutos",
                                            $hours > 1 => $hours . " horas",
                                            $hours == 1 => $hours . " hora",
                                            $minutes > 1 => $minutes . " minutos",

                                            default => "",
                                        }

                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>
            <?php if ($stats["ComicBooks"] > 0) { ?>
                <h2>Gibis</h2>
                <div class="cards" id="comicbooks">
                    <?php while ($comicBook = $comicBooks->fetchArray()) { ?>
                        <div class="card">
                            <div>
                                <div class="number"><?php echo $stats["ComicBooks"]-- ?></div>
                                <div class="date"><?php echo DateTime::createFromFormat("Y-m-d", $comicBook["Date"])->format("d/m/Y") ?></div>
                            </div>
                            <div>
                                <div class="title"><?php echo $comicBook["Title"] ?></div>
                                <div class="publisher-and-format"><?php echo $comicBook["Publisher"] ?> / <?php echo $comicBook["Format"] ?></div>
                            </div>
                            <div>
                                <div class="length">
                                    <?php

                                        $pages = $comicBook["Pages"];
                                        $issues = $comicBook["Issues"];

                                        echo match (true) {
                                            $pages > 1 and $issues > 1 => $pages . " páginas e " . $issues . " edições",
                                            $pages > 1 => $pages . " páginas",

                                            default => "",
                                        }

                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    </div>
    <footer class="footer">
        <div class="history">
            o que li em
            <ul>
                <?php for ($i = 2013; $i <= $today["year"]; $i++) { ?>
                    <li><a href="?q=ano: <?php echo $i ?>"><?php echo $i ?></a></li>
                <?php } ?>
            </ul>
        </div>
        <div class="credits">
            ícone por <a href="https://www.iconfinder.com/sudheepb">sudheep b</a> em <a href="https://www.iconfinder.com/icons/4879874/book_education_learning_study_icon" title="Iconfinder">Iconfinder</a>
        </div>
    </footer>
    <script type="text/javascript" src="site.js"></script>
</body>

</html>

<?php

$db->close();

function get_books($db, $q) {
    $conditions = [];
    $parameters = [];

    switch (true) {
        case str_starts_with($q, "ano:") and strlen($q) > 4:
            $conditions[] = "Date LIKE :year";
            $parameters[":year"] = trim(substr($q, 4)) . "%";

            break;

        case str_starts_with($q, "editora:") and strlen($q) > 8:
            $conditions[] = "Publisher LIKE :publisher";
            $parameters[":publisher"] = "%" . trim(substr($q, 8)) . "%";

            break;

        case str_starts_with($q, "titulo:") and strlen($q) > 7:
            $conditions[] = "Title LIKE :title";
            $parameters[":title"] = "%" . trim(substr($q, 7)) . "%";

            break;

        case str_starts_with($q, "autor:") and strlen($q) > 6:
            $conditions[] = "Author LIKE :author";
            $parameters[":author"] = "%" . trim(substr($q, 6)) . "%";

            break;

        default:
            $conditions[] = "Publisher LIKE :publisher";
            $conditions[] = "Title LIKE :title";
            $conditions[] = "Author LIKE :author";
            $parameters[":publisher"] = "%" . $q . "%";
            $parameters[":title"] = "%" . $q . "%";
            $parameters[":author"] = "%" . $q . "%";
    }

    $conditions = join(" OR ", $conditions);

    $statement = $db->prepare("
        SELECT Date, Publisher, Title, Author, Format, Pages, Duration
        FROM Book
        WHERE $conditions
        ORDER BY Id DESC");

    foreach ($parameters as $key => $value) {
        $statement->bindValue($key, $value, SQLITE3_TEXT);
    }

    return $statement->execute();
}

function get_comicbooks($db, $q) {
    $conditions = [];
    $parameters = [];

    switch (true) {
        case str_starts_with($q, "ano:") and strlen($q) > 4:
            $conditions[] = "Date LIKE :year";
            $parameters[":year"] = trim(substr($q, 4)) . "%";

            break;

        case str_starts_with($q, "editora:") and strlen($q) > 8:
            $conditions[] = "Publisher LIKE :publisher";
            $parameters[":publisher"] = "%" . trim(substr($q, 8)) . "%";

            break;

        case str_starts_with($q, "titulo:") and strlen($q) > 7:
            $conditions[] = "Title LIKE :title";
            $parameters[":title"] = "%" . trim(substr($q, 7)) . "%";

            break;

        default:
            $conditions[] = "Publisher LIKE :publisher";
            $conditions[] = "Title LIKE :title";
            $parameters[":publisher"] = "%" . $q . "%";
            $parameters[":title"] = "%" . $q . "%";
    }

    $conditions = join(" OR ", $conditions);

    $statement = $db->prepare("
        SELECT Date, Publisher, Title, Format, Pages, Issues
        FROM ComicBook
        WHERE $conditions
        ORDER BY Id DESC");

    foreach ($parameters as $key => $value) {
        $statement->bindValue($key, $value, SQLITE3_TEXT);
    }

    return $statement->execute();
}

function generate_statistics($books, $comicBooks) {
    $stats = [
        "Total" => 0,
        "Books" => 0,
        "ComicBooks" => 0,
        "Paper" => 0,
        "Audio" => 0,
        "eBook" => 0,
    ];

    while ($row = $books->fetchArray()) {
        $stats["Total"] += 1;
        $stats["Books"] += 1;

        $key = get_key_for_format($row["Format"]);

        $stats[$key] += 1;
    }

    while ($row = $comicBooks->fetchArray()) {
        $stats["Total"] += 1;
        $stats["ComicBooks"] += 1;

        $key = get_key_for_format($row["Format"]);

        $stats[$key] += 1;
    }

    $books->reset();
    $comicBooks->reset();

    return $stats;
}

function get_key_for_format($format) {
    return match ($format) {
        "Capa comum", "Capa dura" => "Paper",
        "Audiolivro" => "Audio",
        "eBook" => "eBook",
    };
}

?>
