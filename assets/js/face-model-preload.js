(function () {
  const state = window.SIABSEN = window.SIABSEN || {};
  const paths = state.paths || {};
  const isAbsensiPage = window.location.pathname.includes('/absensi');
  const modelsUrl = paths.modelsUrl || new URL('assets/models/', window.location.href).toString();
  const swUrl = paths.swUrl || new URL('sw-face-models.js', window.location.href).toString();

  const modelFiles = [
    'face_landmark_68_model-weights_manifest.json',
    'face_landmark_68_model.bin',
    'face_recognition_model-weights_manifest.json',
    'face_recognition_model.bin',
    'tiny_face_detector_model-weights_manifest.json',
    'tiny_face_detector_model.bin'
  ];

  function normalizeBaseUrl(base) {
    if (!base) return new URL('assets/models/', window.location.href).toString();
    const value = String(base);
    return value.endsWith('/') ? value : `${value}/`;
  }

  function getModelUrls() {
    const base = normalizeBaseUrl(modelsUrl);
    return modelFiles.map((file) => `${base}${file}`);
  }

  async function registerServiceWorker() {
    if (!('serviceWorker' in navigator)) return;

    try {
      const scope = new URL(swUrl, window.location.href).pathname.replace(/\/[^/]*$/, '/');
      await navigator.serviceWorker.register(swUrl, { scope });
      await navigator.serviceWorker.ready;
    } catch (error) {
      console.warn('Service worker preload skipped', error);
    }
  }

  async function fetchModelsInBackground() {
    const urls = getModelUrls();
    const requests = urls.map(async (url) => {
      try {
        const response = await fetch(url, { cache: 'force-cache' });
        return response.ok;
      } catch (error) {
        return false;
      }
    });

    const results = await Promise.allSettled(requests);
    const ok = results.every((result) => result.status === 'fulfilled' && result.value);
    state.faceModelsReady = ok;
    return ok;
  }

  async function preloadFaceModels() {
    if (state.faceModelsPromise) return state.faceModelsPromise;
    if (sessionStorage.getItem('siabsen-face-models-preloaded') === '1') {
      state.faceModelsReady = true;
      return true;
    }

    state.faceModelsPromise = (async () => {
      await registerServiceWorker();
      const ok = await fetchModelsInBackground();
      if (ok) {
        sessionStorage.setItem('siabsen-face-models-preloaded', '1');
      }
      return ok;
    })();

    return state.faceModelsPromise;
  }

  if (!isAbsensiPage) {
    state.preloadFaceModels = preloadFaceModels;
    preloadFaceModels().catch(() => {});
  }
})();
