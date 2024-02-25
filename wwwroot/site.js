const MONTHS = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];
const TODAY = new Date();

window.onload = window.onpopstate = init;

async function init() {
  let year = TODAY.getUTCFullYear();

  // Try to get the year from the URL
  if (window.location.hash) {
    const yearFromURL = parseInt(window.location.hash.substring(1), 10);

    if (yearFromURL) {
      year = yearFromURL;
    }
  }

  // Scroll to the top of the page
  window.scrollTo(0, 0);

  // Get and render the data from the sheet
  document.body.classList.add("is-loading");

  render(await get(year));

  document.body.classList.remove("is-loading");
}

async function get(year) {
  console.time("get");

  const response = await fetch(`/bookshelf/${year}`);
  const json = await response.json();

  console.timeEnd("get");

  return json;
}

function render(items) {
  console.time("render");

  renderStats(items);
  renderCards(items);

  console.timeEnd("render");
}

function renderStats(items) {
  console.time("render stats");

  const $stats = document.getElementById("stats");
  const $total = $stats.querySelector(".total > .value");
  const $books = $stats.querySelector(".books > .value");
  const $comibooks = $stats.querySelector(".comibooks > .value");
  const $paper = $stats.querySelector(".paper > .value");
  const $audio = $stats.querySelector(".audio > .value");
  const $ebook = $stats.querySelector(".ebook > .value");

  $total.textContent = items.length;
  $books.textContent = items.filter(item => item.type === "Book").length;
  $comibooks.textContent = items.filter(item => item.type === "ComicBook").length;
  $paper.textContent = items.filter(item => item.format !== "eBook" && item.format !== "Audiolivro").length;
  $audio.textContent = items.filter(item => item.format === "Audiolivro").length;
  $ebook.textContent = items.filter(item => item.format === "eBook").length;

  console.timeEnd("render stats");
}

function renderCards(items) {
  console.time("render cards");

  const $fragment = document.createDocumentFragment();
  const $template = document.getElementById("card");

  let i = 1;

  for (const { date, publisher, title, length, format } of items) {
    const $card = $template.content.cloneNode(true);
    const $number = $card.querySelector(".number")
    const $date = $card.querySelector(".date")
    const $title = $card.querySelector(".title")
    const $publisherFormat = $card.querySelector(".publisher-and-format")
    const $length = $card.querySelector(".length")

    $number.textContent = `#${i}`;
    $date.textContent = date.split("-").reverse().join("/");
    $title.textContent = title;
    $publisherFormat.textContent = `${publisher} / ${format}`;

    const { pages, issues, hours, minutes } = length;

    if (pages > 0 && issues > 1) {
      $length.textContent = `${pages} páginas e ${issues} edições`;
    } else if (pages > 0) {
      $length.textContent = `${pages} páginas`;
    } else if (hours > 0 && minutes > 0) {
      $length.textContent = `${hours} horas e ${minutes} minutos`;
    } else if (hours > 0) {
      $length.textContent = `${hours} horas`;
    } else if (minutes > 0) {
      $length.textContent = `${minutes} minutos`;
    }

    $fragment.insertBefore($card, $fragment.firstChild);

    i++;
  }

  const $cards = document.getElementById("cards");

  $cards.replaceChildren($fragment);

  console.timeEnd("render cards");
}
