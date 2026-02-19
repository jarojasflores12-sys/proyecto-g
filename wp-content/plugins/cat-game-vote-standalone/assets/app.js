(function () {
  const params = new URLSearchParams(window.location.search);
  const error = params.get('catgame_error');
  if (error) {
    console.warn('CatGame warning:', error);
  }

  const input = document.getElementById('catgame-cat-image');
  const sizeEl = document.getElementById('catgame-file-size');

  if (!input || !sizeEl) {
    return;
  }

  const formatSize = (bytes) => {
    if (!Number.isFinite(bytes) || bytes < 0) return '-';
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(2)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
  };

  input.addEventListener('change', () => {
    const file = input.files && input.files[0] ? input.files[0] : null;
    sizeEl.textContent = `Tamaño seleccionado: ${file ? formatSize(file.size) : '-'}`;
  });
})();
