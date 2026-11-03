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
const IS_COMIC_BOOK_SERIE_REGEX = "/^(?<serie_title>.*)(?<issue_number>#\d+)$/";

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
    <link rel="preload" as="font" type="font/woff2" href="crimson-pro.woff2" crossorigin>
    <link rel="stylesheet" href="site.css">
</head>

<body>
    <div class="container">
        <header class="header">
            <h1>Estou a ler</h1>
        </header>
        <main class="body">
            <div class="search">
                <form method="get">
                    <h2>Resultados da pesquisa por</h2>
                    <input
                        name="q"
                        value="<?php echo $q ?>"
                        placeholder="ano: 2026 ou autor: carla madeira ou titulo: a natureza da mordida">
                </form>
                <p class="stats">
                    <?php foreach (calculate_statistics($entries) as $stat) { ?><span><?php echo $stat ?></span><?php } ?>
                </p>
            </div>
            <?php foreach ($entries_by_month as $month => $entries) { ?>
                <div class="entries">
                    <h2><?php echo $month ?></h2>
                    <p class="stats">
                        <?php foreach (calculate_statistics($entries) as $stat) { ?><span><?php echo $stat ?></span><?php } ?>
                    </p>
                    <ol>
                        <?php foreach ($entries as $e) { ?>
                            <li class="entry">
                                <dl>
                                    <div class="entry-date">
                                        <dt>Lido em</dt>
                                        <dd class="date"><?php echo format_entry_date($e) ?></dd>
                                    </div>
                                    <div class="entry-content">
                                        <dt>Título</dt>
                                        <dd class="title"><?php echo $e["title"] ?></dd>
                                        <?php if (array_key_exists("author", $e)) { ?>
                                            <dt>Autor</dt>
                                            <dd class="author">
                                                <a href="/?q=<?php echo urlencode("autor: " . $e["author"]) ?>"><?php echo $e["author"] ?></a>
                                            </dd>
                                        <?php } ?>
                                        <dt>Editora</dt>
                                        <dd class="publisher">
                                            <a href="/?q=<?php echo urlencode("editora: " . $e["publisher"]) ?>"><?php echo $e["publisher"] ?></a>
                                        </dd>
                                        <dt>Formato</dt>
                                        <dd class="format"><?php echo $e["format"] ?></dd>
                                        <dt>Número de páginas, edições ou duração</dt>
                                        <dd class="length"><?php echo format_entry_length($e) ?></dd>
                                    </div>
                                </dl>
                            </li>
                        <?php } ?>
                    </ol>
                </div>
            <?php } ?>
        </main>
        <footer class="footer">
            <form class="history" method="get">
                <label for="history-year">O que li em</label>
                <select id="history-year" name="q">
                    <?php for ($i = $today["year"]; $i > 2013; $i--) { ?>
                        <option value="ano: <?php echo $i ?>" <?php if ($q === 'ano: ' . $i) echo ' selected' ?>><?php echo $i ?></option>
                    <?php } ?>
                    <option value="ano: 1970" <?php if ($q === 'ano: 1970') echo ' selected' ?>>Sem data</option>
                </select>
                <button type="submit">Ir</button>
            </form>
        </footer>
    </div>
</body>

</html>


<?php

$db->close();

function get_entries(SQLite3 $db, string $q): array
{
    $books = get_books($db, $q);
    $comicbooks = group_comicbooks_by_serie(get_comicbooks($db, $q));
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

function group_comicbooks_by_serie(array $comicbooks): array
{
    $arr = array();

    for ($i = count($comicbooks); $i > 0; $i--) {
        $acc = $cur = $comicbooks[$i - 1];

        for ($j = $i - 1; $j > 0; $j--) {
            $next = $comicbooks[$j - 1] ?? null;

            if (
                isset($next) &&
                $cur["date"] == $next["date"] &&
                preg_match(IS_COMIC_BOOK_SERIE_REGEX, $cur["title"], $curMatches) &&
                preg_match(IS_COMIC_BOOK_SERIE_REGEX, $next["title"], $nextMatches) &&
                $curMatches["serie_title"] == $nextMatches["serie_title"]
            ) {
                $acc["title"] .= " " . $nextMatches["issue_number"];
                $acc["pages"] += $next["pages"];
                $acc["issues"] += $next["issues"];

                $i--;
            } else {
                break;
            }
        }

        $arr[] = $acc;
    }

    return array_reverse($arr);
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

    if ($year == "1970") {
        return "Sem data";
    }

    return MONTHS[$month] . " " . $year;
}

function calculate_statistics(array $entries): array
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
            $comicbooks += $e["issues"];
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

    return array_filter($arr);
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
