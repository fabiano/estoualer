<?php

const MONTHS = array(
    "1" => "Janeiro",
    "2" => "Fevereiro",
    "3" => "Março",
    "4" => "Abril",
    "5" => "Maio",
    "6" => "Junho",
    "7" => "Julho",
    "8" => "Agosto",
    "9" => "Setembro",
    "10" => "Outubro",
    "11" => "Novembro",
    "12" => "Dezembro",
);

const WEEKDAYS = array(
    "1" => "seg",
    "2" => "ter",
    "3" => "qua",
    "4" => "qui",
    "5" => "sex",
    "6" => "sáb",
    "7" => "dom",
);

const DURATION_REGEX = "/^((?<hours>\d+?)h)?\s{0,1}((?<minutes>\d+?)m)?$/";

$today = getdate();
$q = trim($_GET["q"] ?? "");

if (strlen($q) == 0) {
    $q = "ano: " . $today["year"];
}

$db = new SQLite3("estoualer.db");
$entries = get_entries($db, $q);
$entries_by_month = group_entries_by_month($entries);

?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <meta name="description" content="Os livros e gibis que li.">
    <title>Estou a ler</title>
</head>

<body>
    <div id="app">
        <div class="container">
            <header class="header">
                <h1>Estou a ler</h1>
                <form method="get">
                    <input id="q"
                        name="q"
                        value="<?php echo $q ?>"
                        placeholder="ano: 2026 ou autor: carla madeira ou titulo: a natureza da mordida"
                        aria-label="Pesquisar">
                    <button type="submit">Pesquisar</button>
                </form>
            </header>
        </div>
        <div class="container">
            <main class="body">
                <section>
                    <h2>Resumo das leituras</h2>
                    <p><?php echo generate_statistics($entries) ?></p>
                </section>
                <?php foreach ($entries_by_month as $month => $entries) { ?>
                    <section class="entries">
                        <header>
                            <h2><?php echo $month ?></h2>
                            <p><?php echo generate_statistics($entries) ?></p>
                        </header>
                        <ul>
                            <?php foreach ($entries as $entry) { ?>
                                <li class="entry">
                                    <h3><?php echo $entry["title"] ?></h3>
                                    <dl>
                                        <dt>Lido em</dt>
                                        <dd class="date">
                                            <time datetime="<?php echo $entry['date']->format("Y-m-d") ?>">
                                                <?php echo format_date($entry) ?>
                                            </time>
                                        </dd>
                                        <dt>Título</dt>
                                        <dd class="title"><?php echo $entry["title"] ?></dd>
                                        <?php if (array_key_exists("author", $entry)) { ?>
                                            <dt>Autor</dt>
                                            <dd class="author"><?php echo $entry["author"] ?></dd>
                                        <?php } ?>
                                        <dt>Editora</dt>
                                        <dd class="publisher"><?php echo $entry["publisher"] ?></dd>
                                        <dt>Formato</dt>
                                        <dd class="format"><?php echo $entry["format"] ?></dd>
                                        <dt>Número de páginas, edições ou duração</dt>
                                        <dd class="length"><?php echo format_length($entry) ?></dd>
                                    </dl>
                                </li>
                            <?php } ?>
                        </ul>
                    </section>
                <?php } ?>
            </main>
        </div>
        <div class="container">
            <footer class="footer">
                <nav aria-label="Histórico">
                    <form method="get">
                        <label for="history-year">O que li em</label>
                        <select id="history-year" name="q">
                            <?php for ($i = $today["year"]; $i > 2013; $i--) { ?>
                                <option value="ano: <?php echo $i ?>" <?php if ($q === 'ano: ' . $i) echo ' selected' ?>><?php echo $i ?></option>
                            <?php } ?>
                            <option value="ano: 1970" <?php if ($q === 'ano: 1970') echo ' selected' ?>>Sem data</option>
                        </select>
                        <button type="submit">Ir</button>
                    </form>
                </nav>
            </footer>
        </div>
    </div>
</body>

</html>


<?php

$db->close();

function get_entries(SQLite3 $db, string $q): array
{
    $books = get_books($db, $q);
    $comicbooks = get_comicbooks($db, $q);
    $entries = array_merge($books, $comicbooks);

    usort($entries, function ($a, $b) {
        $aDate = $a["date"];
        $bDate = $b["date"];

        return match (true) {
            $aDate == $bDate =>  0,
            $aDate >  $bDate => -1,
            $aDate <  $bDate =>  1,
        };
    });

    return $entries;
}

function get_books(SQLite3 $db, string $q): array
{
    switch (true) {
        case str_starts_with($q, "ano:") and strlen($q) > 4:
            $statement = $db->prepare("select * from book where date like :year order by date desc, id desc");
            $statement->bindValue(":year", trim(substr($q, 4)) . "%");

            break;

        case str_starts_with($q, "editora:") and strlen($q) > 8:
            $statement = $db->prepare("select * from bookfts where publisher match :publisher order by date desc, id desc");
            $statement->bindValue(":publisher", escape_value(substr($q, 8)));

            break;

        case str_starts_with($q, "titulo:") and strlen($q) > 7:
            $statement = $db->prepare("select * from bookfts where title match :title order by date desc, id desc");
            $statement->bindValue(":title", escape_value(substr($q, 7)));

            break;

        case str_starts_with($q, "autor:") and strlen($q) > 6:
            $statement = $db->prepare("select * from bookfts where author match :author order by date desc, id desc");
            $statement->bindValue(":author", escape_value(substr($q, 6)));

            break;

        default:
            $statement = $db->prepare("select * from bookfts where bookfts match :q order by date desc, id desc");
            $statement->bindValue(":q", escape_value($q));
    }

    $r = $statement->execute();
    $entries = array();

    while ($row = $r->fetchArray()) {
        array_push($entries, new_entry_from_row("book", $row));
    }

    return $entries;
}

function get_comicbooks(SQLite3 $db, string $q): array
{
    switch (true) {
        case str_starts_with($q, "ano:") and strlen($q) > 4:
            $statement = $db->prepare("select * from comicbook where date like :year order by date desc, id desc");
            $statement->bindValue(":year", trim(substr($q, 4)) . "%");

            break;

        case str_starts_with($q, "editora:") and strlen($q) > 8:
            $statement = $db->prepare("select * from comicbookfts where publisher match :publisher order by date desc, id desc");
            $statement->bindValue(":publisher", escape_value(substr($q, 8)));

            break;

        case str_starts_with($q, "titulo:") and strlen($q) > 7:
            $statement = $db->prepare("select * from comicbookfts where title match :title order by date desc, id desc");
            $statement->bindValue(":title", escape_value(substr($q, 7)));

            break;

        default:
            $statement = $db->prepare("select * from comicbookfts where comicbookfts match :q order by date desc, id desc");
            $statement->bindValue(":q", escape_value($q));
    }

    $r = $statement->execute();
    $entries = array();

    while ($row = $r->fetchArray()) {
        array_push($entries, new_entry_from_row("comicbook", $row));
    }

    return $entries;
}

function new_entry_from_row(string $kind, array $row): array
{
    $pages = 0;
    $issues = 0;
    $hours = 0;
    $minutes = 0;

    if (array_key_exists("pages", $row)) {
        $pages = intval($row["pages"] ?? 0);
    }

    if (array_key_exists("issues", $row)) {
        $issues = intval($row["issues"] ?? 0);
    }

    if (array_key_exists("duration", $row)) {
        if (preg_match(DURATION_REGEX, $row["duration"], $matches) == 1) {
            $hours = intval($matches["hours"] ?? 0);
            $minutes = intval($matches["minutes"] ?? 0);
        }
    }

    $row["kind"] = $kind;
    $row["date"] = DateTime::createFromFormat("Y-m-d", $row["date"]);
    $row["pages"] = $pages;
    $row["issues"] = $issues;
    $row["hours"] = $hours;
    $row["minutes"] = $minutes;

    return $row;
}

function group_entries_by_month(array $entries): array
{
    $groups = array();

    foreach ($entries as $entry) {
        $key = generate_group_key($entry);

        if (!array_key_exists($key, $groups)) {
            $groups[$key] = array();
        }

        $groups[$key][] = $entry;
    }

    return $groups;
}

function generate_group_key(array $entry): string
{
    $date = $entry["date"];
    $month = $date->format("n");
    $year = $date->format("Y");

    return MONTHS[$month] . " " . $year;
}

function generate_statistics(array $entries): string
{
    $books = 0;
    $comicbooks = 0;
    $hours = 0;
    $pages = 0;

    foreach ($entries as $entry) {
        if ($entry["kind"] == "book") {
            $books += 1;
        }

        if ($entry["kind"] == "comicbook") {
            $comicbooks += 1;
        }

        $pages += $entry["pages"];
        $hours += $entry["hours"] + $entry["minutes"] / 60;
    }

    $arr = array(
        pluralize($books + $comicbooks, "leitura", "leituras"),
        pluralize($books, "livro", "livros"),
        pluralize($comicbooks, "gibi", "gibis"),
        pluralize($pages, "página", "páginas"),
        pluralize(floor($hours), "hora", "horas"),
    );

    return join(" · ", array_filter($arr, fn($value) => $value != ""));
}

function pluralize(int $n, string $singular, string $plural): string
{
    return match (true) {
        $n == 1 => $n . " " . $singular,
        $n > 1  => format_number($n) . " " . $plural,

        default => "",
    };
}

function format_number(int $n): string
{
    return number_format($n, decimal_separator: ",", thousands_separator: '.');
}

function format_date(array $entry): string
{
    $weekday = $entry["date"]->format("N");
    $day = $entry["date"]->format("d");

    return WEEKDAYS[$weekday] . ", " . $day;
}

function format_length(array $entry): string
{
    $pages = $entry["pages"];
    $issues = $entry["issues"];
    $hours = $entry["hours"];
    $minutes = $entry["minutes"];

    $arr = array(
        pluralize($pages, "página", "páginas"),
        pluralize($issues, "edição", "edições"),
        pluralize($hours, "hora", "horas"),
        pluralize($minutes, "minuto", "minutos"),
    );

    return join(" e ", array_filter($arr, fn($value) => $value != ""));
}

function escape_value(string $value): string
{
    $arr = explode(" ", trim($value));

    foreach ($arr as &$v) {
        $v = "\"" . str_replace("\"", "\"\"", $v) . "\"";
    }

    return join(" ", $arr);
}

?>
