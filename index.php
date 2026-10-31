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
$q = trim($_GET["q"] ?? "") ?: "ano: " . $today["year"];
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
                            <?php foreach ($entries as $e) { ?>
                                <li class="entry">
                                    <h3><?php echo $e["title"] ?></h3>
                                    <dl>
                                        <dt>Lido em</dt>
                                        <dd class="date">
                                            <time datetime="<?php echo $e['date']->format("Y-m-d") ?>">
                                                <?php echo format_entry_date($e) ?>
                                            </time>
                                        </dd>
                                        <dt>Título</dt>
                                        <dd class="title"><?php echo $e["title"] ?></dd>
                                        <?php if (array_key_exists("author", $e)) { ?>
                                            <dt>Autor</dt>
                                            <dd class="author"><?php echo $e["author"] ?></dd>
                                        <?php } ?>
                                        <dt>Editora</dt>
                                        <dd class="publisher"><?php echo $e["publisher"] ?></dd>
                                        <dt>Formato</dt>
                                        <dd class="format"><?php echo $e["format"] ?></dd>
                                        <dt>Número de páginas, edições ou duração</dt>
                                        <dd class="length"><?php echo format_entry_length($e) ?></dd>
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

    $result = $statement->execute();
    $arr = array();

    while ($r = $result->fetchArray()) {
        array_push($arr, new_entry_from_row("book", $r));
    }

    return $arr;
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

    $result = $statement->execute();
    $arr = array();

    while ($r = $result->fetchArray()) {
        array_push($arr, new_entry_from_row("comicbook", $r));
    }

    return $arr;
}

function new_entry_from_row(string $kind, array $r): array
{
    $pages = 0;
    $issues = 0;
    $hours = 0;
    $minutes = 0;

    if (array_key_exists("pages", $r)) {
        $pages = intval($r["pages"] ?? 0);
    }

    if (array_key_exists("issues", $r)) {
        $issues = intval($r["issues"] ?? 0);
    }

    if (array_key_exists("duration", $r)) {
        if (preg_match(DURATION_REGEX, $r["duration"], $matches) == 1) {
            $hours = intval($matches["hours"] ?? 0);
            $minutes = intval($matches["minutes"] ?? 0);
        }
    }

    $r["kind"] = $kind;
    $r["date"] = DateTime::createFromFormat("Y-m-d", $r["date"]);
    $r["pages"] = $pages;
    $r["issues"] = $issues;
    $r["hours"] = $hours;
    $r["minutes"] = $minutes;

    return $r;
}

function group_entries_by_month(array $entries): array
{
    $groups = array();

    foreach ($entries as $e) {
        $key = generate_group_key($e);

        if (!array_key_exists($key, $groups)) {
            $groups[$key] = array();
        }

        $groups[$key][] = $e;
    }

    return $groups;
}

function generate_group_key(array $e): string
{
    $month = $e["date"]->format("n");
    $year = $e["date"]->format("Y");

    return MONTHS[$month] . " " . $year;
}

function generate_statistics(array $entries): string
{
    $books = 0;
    $comicbooks = 0;
    $hours = 0;
    $pages = 0;

    foreach ($entries as $e) {
        if ($e["kind"] == "book") {
            $books += 1;
        }

        if ($e["kind"] == "comicbook") {
            $comicbooks += 1;
        }

        $pages += $e["pages"];
        $hours += $e["hours"] + $e["minutes"] / 60;
    }

    $arr = array(
        format_count("%s leitura", "%s leituras", $books + $comicbooks),
        format_count("%s livro", "%s livros", $books),
        format_count("%s gibi", "%s gibis", $comicbooks),
        format_count("%s página", "%s páginas", $pages),
        format_count("%s hora", "%s horas", floor($hours)),
    );

    return implode(" · ", array_filter($arr));
}

function format_count(string $singular, string $plural, int $count): string
{
    $f = number_format($count, decimal_separator: ",", thousands_separator: '.');

    return match (true) {
        $count < 1  => "",
        $count == 1 => sprintf($singular, $f),
        $count > 1  => sprintf($plural, $f),
    };
}

function format_entry_date(array $e): string
{
    $weekday = $e["date"]->format("N");
    $day = $e["date"]->format("d");

    return WEEKDAYS[$weekday] . ", " . $day;
}

function format_entry_length(array $e): string
{
    $arr = array(
        format_count("%s página", "%s páginas", $e["pages"]),
        format_count("%s edição", "%s edições", $e["issues"] > 1 ? $e["issues"] : 0),
        format_count("%s hora", "%s horas", $e["hours"]),
        format_count("%s minuto", "%s minutos", $e["minutes"]),
    );

    return implode(" e ", array_filter($arr));
}

function escape_value(string $value): string
{
    $arr = explode(" ", trim($value));

    foreach ($arr as &$v) {
        $v = "\"" . str_replace("\"", "\"\"", $v) . "\"";
    }

    return implode(" ", $arr);
}

?>
