const offlineClassName = "is-offline";

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
});

window.addEventListener('online', () => {
  document.body.classList.remove(offlineClassName);
});

window.addEventListener('offline', () => {
  document.body.classList.add(offlineClassName);
});
