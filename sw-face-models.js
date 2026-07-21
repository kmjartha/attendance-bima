const CACHE_NAME = 'siabsen-face-models-v1';
const MODEL_FILES = [
  'assets/models/face_landmark_68_model-weights_manifest.json',
  'assets/models/face_landmark_68_model.bin',
  'assets/models/face_recognition_model-weights_manifest.json',
  'assets/models/face_recognition_model.bin',
  'assets/models/tiny_face_detector_model-weights_manifest.json',
  'assets/models/tiny_face_detector_model.bin'
];

function buildCacheUrls(scopeUrl) {
  return MODEL_FILES.map((file) => new URL(file, scopeUrl).toString());
}

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(buildCacheUrls(self.registration.scope)))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(
        keys
          .filter((key) => key !== CACHE_NAME)
          .map((key) => caches.delete(key))
      ))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const { request } = event;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return;

  const shouldCache = MODEL_FILES.some((file) => url.pathname.endsWith(`/${file.split('/').pop()}`));
  if (!shouldCache) return;

  event.respondWith(
    caches.match(request, { ignoreSearch: true }).then((cached) => {
      if (cached) return cached;

      return fetch(request).then((response) => {
        const copy = response.clone();
        caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
        return response;
      });
    })
  );
});
