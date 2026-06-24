<?php

const PAPER_FORMATS = "'Capa comum', 'Capa dura'";
const TOP_N = 15;

const MONTHS = [
    "01" => "Janeiro", "02" => "Fevereiro", "03" => "Março", "04" => "Abril",
    "05" => "Maio", "06" => "Junho", "07" => "Julho", "08" => "Agosto",
    "09" => "Setembro", "10" => "Outubro", "11" => "Novembro", "12" => "Dezembro",
];

const RATINGS = [5 => "★★★★★", 4 => "★★★★", 3 => "★★★", 2 => "★★", 1 => "★"];

$db = new SQLite3("Bookshelf.db");

$summary = [
    "Total" => count_rows($db, "SELECT (SELECT COUNT(*) FROM Book) + (SELECT COUNT(*) FROM ComicBook) AS count"),
    "Books" => count_rows($db, "SELECT COUNT(*) AS count FROM Book"),
    "ComicBooks" => count_rows($db, "SELECT COUNT(*) AS count FROM ComicBook"),
    "Paper" => count_rows($db, "SELECT (SELECT COUNT(*) FROM Book WHERE Format IN (" . PAPER_FORMATS . ")) + (SELECT COUNT(*) FROM ComicBook WHERE Format IN (" . PAPER_FORMATS . ")) AS count"),
    "Audio" => count_rows($db, "SELECT (SELECT COUNT(*) FROM Book WHERE Format = 'Audiolivro') + (SELECT COUNT(*) FROM ComicBook WHERE Format = 'Audiolivro') AS count"),
    "eBook" => count_rows($db, "SELECT (SELECT COUNT(*) FROM Book WHERE Format = 'eBook') + (SELECT COUNT(*) FROM ComicBook WHERE Format = 'eBook') AS count"),
];

$books_by_year = aggregate($db, "SELECT substr(Date, 1, 4) AS label, COUNT(*) AS count FROM Book GROUP BY label ORDER BY label DESC");
$books_by_month = relabel(aggregate($db, "SELECT substr(Date, 6, 2) AS label, COUNT(*) AS count FROM Book GROUP BY label ORDER BY label ASC"), MONTHS);
$books_by_rating = relabel(aggregate($db, "SELECT Rating AS label, COUNT(*) AS count FROM Book GROUP BY label ORDER BY label DESC"), RATINGS);
$books_by_format = aggregate($db, "SELECT Format AS label, COUNT(*) AS count FROM Book GROUP BY label ORDER BY count DESC, label ASC");
$books_by_publisher = aggregate($db, "SELECT Publisher AS label, COUNT(*) AS count FROM Book WHERE TRIM(Publisher) != '' GROUP BY label ORDER BY count DESC, label ASC LIMIT " . TOP_N);
$books_by_author = aggregate($db, "SELECT Author AS label, COUNT(*) AS count FROM Book GROUP BY label ORDER BY count DESC, label ASC LIMIT " . TOP_N);
$books_favorite_authors = aggregate($db, "SELECT Author AS label, COUNT(*) AS count FROM Book WHERE Rating >= 4 GROUP BY label ORDER BY count DESC, label ASC LIMIT " . TOP_N);
$books_pages_by_year = aggregate($db, "SELECT substr(Date, 1, 4) AS label, SUM(Pages) AS count FROM Book GROUP BY label HAVING SUM(Pages) > 0 ORDER BY label DESC");
$comicbooks_by_year = aggregate($db, "SELECT substr(Date, 1, 4) AS label, COUNT(*) AS count FROM ComicBook GROUP BY label ORDER BY label DESC");
$comicbooks_by_month = relabel(aggregate($db, "SELECT substr(Date, 6, 2) AS label, COUNT(*) AS count FROM ComicBook GROUP BY label ORDER BY label ASC"), MONTHS);
$comicbooks_by_format = aggregate($db, "SELECT Format AS label, COUNT(*) AS count FROM ComicBook GROUP BY label ORDER BY count DESC, label ASC");
$comicbooks_by_publisher = aggregate($db, "SELECT Publisher AS label, COUNT(*) AS count FROM ComicBook GROUP BY label ORDER BY count DESC, label ASC LIMIT " . TOP_N);
$comicbooks_pages_by_year = aggregate($db, "SELECT substr(Date, 1, 4) AS label, SUM(Pages) AS count FROM ComicBook GROUP BY label HAVING SUM(Pages) > 0 ORDER BY label DESC");
$comicbooks_issues_by_year = aggregate($db, "SELECT substr(Date, 1, 4) AS label, SUM(Issues) AS count FROM ComicBook GROUP BY label HAVING SUM(Issues) > 0 ORDER BY label DESC");

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="Estatísticas dos livros e gibis que li.">
    <title>Estou a ler — Estatísticas</title>
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
    <div class="container">
        <header class="header">
            <h1>Estatísticas</h1>
            <div class="stats" id="stats">
                <div class="item total">
                    <p class="heading">Total</p>
                    <p class="value"><?php echo $summary["Total"] ?></p>
                </div>
                <div class="item books">
                    <p class="heading">Livros</p>
                    <p class="value"><?php echo $summary["Books"] ?></p>
                </div>
                <div class="item comicbooks">
                    <p class="heading">Gibis</p>
                    <p class="value"><?php echo $summary["ComicBooks"] ?></p>
                </div>
                <hr class="separator">
                <div class="item paper">
                    <p class="heading">Em papel</p>
                    <p class="value"><?php echo $summary["Paper"] ?></p>
                </div>
                <div class="item audio">
                    <p class="heading">Em áudio</p>
                    <p class="value"><?php echo $summary["Audio"] ?></p>
                </div>
                <div class="item ebook">
                    <p class="heading">eBook</p>
                    <p class="value"><?php echo $summary["eBook"] ?></p>
                </div>
            </div>
        </header>
    </div>
    <hr class="separator">
    <div class="container">
        <main class="body">
            <section>
                <h2>Livros</h2>
                <div class="stat-groups">
                    <div class="stat-group">
                        <h3>Por ano</h3>
                        <?php render_stat_list($books_by_year) ?>
                    </div>
                    <div class="stat-group">
                        <h3>Por mês</h3>
                        <?php render_stat_list($books_by_month) ?>
                    </div>
                    <div class="stat-group">
                        <h3>Por nota</h3>
                        <?php render_stat_list($books_by_rating) ?>
                    </div>
                    <div class="stat-group">
                        <h3>Por formato</h3>
                        <?php render_stat_list($books_by_format) ?>
                    </div>
                    <div class="stat-group">
                        <h3>Por editora</h3>
                        <?php render_stat_list($books_by_publisher) ?>
                    </div>
                    <div class="stat-group">
                        <h3>Por autor</h3>
                        <?php render_stat_list($books_by_author) ?>
                    </div>
                    <div class="stat-group">
                        <h3>Autores favoritos</h3>
                        <?php render_stat_list($books_favorite_authors) ?>
                    </div>
                    <div class="stat-group">
                        <h3>Páginas por ano</h3>
                        <?php render_stat_list($books_pages_by_year) ?>
                    </div>
                </div>
            </section>
            <section>
                <h2>Gibis</h2>
                <div class="stat-groups">
                    <div class="stat-group">
                        <h3>Por ano</h3>
                        <?php render_stat_list($comicbooks_by_year) ?>
                    </div>
                    <div class="stat-group">
                        <h3>Por mês</h3>
                        <?php render_stat_list($comicbooks_by_month) ?>
                    </div>
                    <div class="stat-group">
                        <h3>Por formato</h3>
                        <?php render_stat_list($comicbooks_by_format) ?>
                    </div>
                    <div class="stat-group">
                        <h3>Por editora</h3>
                        <?php render_stat_list($comicbooks_by_publisher) ?>
                    </div>
                    <div class="stat-group">
                        <h3>Páginas por ano</h3>
                        <?php render_stat_list($comicbooks_pages_by_year) ?>
                    </div>
                    <div class="stat-group">
                        <h3>Edições por ano</h3>
                        <?php render_stat_list($comicbooks_issues_by_year) ?>
                    </div>
                </div>
            </section>
        </main>
    </div>
    <footer class="footer">
        <nav class="history" aria-label="Navegação">
            <a href="index.php">voltar ao início</a>
        </nav>
        <p class="credits">
            ícone por <a href="https://www.iconfinder.com/sudheepb">sudheep b</a> em <a href="https://www.iconfinder.com/icons/4879874/book_education_learning_study_icon" title="Iconfinder">Iconfinder</a>
        </p>
    </footer>
</body>

</html>

<?php

$db->close();

function aggregate(SQLite3 $db, string $sql): array {
    $rows = [];
    $result = $db->query($sql);

    while ($row = $result->fetchArray()) {
        $rows[] = $row;
    }

    return $rows;
}

function count_rows(SQLite3 $db, string $sql): int {
    return (int) $db->querySingle($sql);
}

function relabel(array $rows, array $map): array {
    foreach ($rows as &$row) {
        $row["label"] = $map[$row["label"]] ?? $row["label"];
    }

    return $rows;
}

function render_stat_list(array $rows): void {
    if (count($rows) == 0) {
        echo '<p class="empty">Nada por aqui ainda.</p>';

        return;
    }

    echo '<ul class="stat-list">';

    foreach ($rows as $row) {
        $label = htmlspecialchars($row["label"] ?? "", ENT_QUOTES);
        $count = (int) $row["count"];

        echo '<li><span class="label">' . $label . '</span><span class="leader"></span><span class="count">' . $count . '</span></li>';
    }

    echo '</ul>';
}

?>
