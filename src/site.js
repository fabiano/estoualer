const offlineClassName = "is-offline";
const listViewClassName = "is-list-view";
const viewStorageKey = "view";

window.addEventListener('load', async () => {
  try {
    await navigator.serviceWorker.register('/service-worker.js');

    console.log('Service worker registered');
  } catch (err) {
    console.log(`Service worker registration failed with ${err}`);
  }

  if (!navigator.onLine) {
    document.body.classList.add(offlineClassName);
  }

  if (localStorage.getItem(viewStorageKey) === 'list') {
    document.body.classList.add(listViewClassName);
  }

  document.getElementById('view-toggle').addEventListener('click', () => {
    const isList = document.body.classList.toggle(listViewClassName);

    localStorage.setItem(viewStorageKey, isList ? 'list' : 'cards');
  });
});

window.addEventListener('online', () => {
  document.body.classList.remove(offlineClassName);
});

window.addEventListener('offline', () => {
  document.body.classList.add(offlineClassName);
});
