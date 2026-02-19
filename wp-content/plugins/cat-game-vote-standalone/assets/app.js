(function () {
  const params = new URLSearchParams(window.location.search);
  const error = params.get('catgame_error');
  if (error) {
    console.warn('CatGame warning:', error);
  }

  const form = document.querySelector('form.cg-form input[name="action"][value="catgame_upload"]')?.closest('form');
  const input = document.getElementById('catgame-cat-image');
  if (!form || !input) {
    return;
  }

  const originalEl = document.getElementById('catgame-file-size-original');
  const compressedEl = document.getElementById('catgame-file-size-compressed');
  const reductionEl = document.getElementById('catgame-file-reduction');
  const formatEl = document.getElementById('catgame-file-format');
  const stateEl = document.getElementById('catgame-compress-status');
  const previewEl = document.getElementById('catgame-image-preview');
  const submitButton = form.querySelector('button[type="submit"]');

  const TARGET_MAX_SIDE = 1280;
  const TARGET_MAX_BYTES = 900 * 1024;
  const FALLBACK_MAX_QUALITY = 0.6;
  const QUALITY_STEPS = [0.82, 0.78, 0.72, 0.66, 0.6];
  const FALLBACK_FORMAT = 'image/jpeg';

  let compressedFile = null;
  let compressing = false;

  const formatSize = (bytes) => {
    if (!Number.isFinite(bytes) || bytes < 0) return '-';
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(2)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
  };

  const setState = (text, isBusy) => {
    if (stateEl) stateEl.textContent = text;
    if (submitButton) submitButton.disabled = !!isBusy;
    compressing = !!isBusy;
  };

  const setCompressionInfo = ({ originalBytes = null, compressedBytes = null, format = '-' } = {}) => {
    if (originalEl) {
      originalEl.textContent = `Tamaño original: ${originalBytes === null ? '-' : formatSize(originalBytes)}`;
    }
    if (compressedEl) {
      compressedEl.textContent = `Tamaño comprimido: ${compressedBytes === null ? '-' : formatSize(compressedBytes)}`;
    }
    if (formatEl) {
      formatEl.textContent = `Formato final: ${format === '-' ? '-' : format.toUpperCase().replace('IMAGE/', '')}`;
    }
    if (reductionEl) {
      if (!Number.isFinite(originalBytes) || !Number.isFinite(compressedBytes) || originalBytes <= 0) {
        reductionEl.textContent = 'Reducción: -';
      } else {
        const reduction = Math.max(0, ((originalBytes - compressedBytes) / originalBytes) * 100);
        reductionEl.textContent = `Reducción: ${reduction.toFixed(1)}%`;
      }
    }
  };

  const supportsWebP = () => {
    try {
      const canvas = document.createElement('canvas');
      return canvas.toDataURL('image/webp').startsWith('data:image/webp');
    } catch (_) {
      return false;
    }
  };

  const loadImage = async (file) => {
    if ('createImageBitmap' in window) {
      return await createImageBitmap(file);
    }

    return await new Promise((resolve, reject) => {
      const img = new Image();
      const url = URL.createObjectURL(file);
      img.onload = () => {
        URL.revokeObjectURL(url);
        resolve(img);
      };
      img.onerror = () => {
        URL.revokeObjectURL(url);
        reject(new Error('No se pudo leer la imagen.'));
      };
      img.src = url;
    });
  };

  const canvasToBlob = (canvas, type, quality) =>
    new Promise((resolve, reject) => {
      if (!canvas.toBlob) {
        reject(new Error('canvas.toBlob no soportado'));
        return;
      }
      canvas.toBlob(
        (blob) => {
          if (!blob) {
            reject(new Error('No se pudo crear blob comprimido'));
            return;
          }
          resolve(blob);
        },
        type,
        quality
      );
    });

  const buildCompressedFile = async (file) => {
    const bitmap = await loadImage(file);
    const width = bitmap.width;
    const height = bitmap.height;

    const scale = Math.min(1, TARGET_MAX_SIDE / Math.max(width, height));
    const nextWidth = Math.max(1, Math.round(width * scale));
    const nextHeight = Math.max(1, Math.round(height * scale));

    const canvas = document.createElement('canvas');
    canvas.width = nextWidth;
    canvas.height = nextHeight;

    const ctx = canvas.getContext('2d');
    if (!ctx) {
      throw new Error('Canvas no disponible');
    }

    ctx.drawImage(bitmap, 0, 0, nextWidth, nextHeight);

    const targetMime = supportsWebP() ? 'image/webp' : FALLBACK_FORMAT;
    const extension = targetMime === 'image/webp' ? 'webp' : 'jpg';

    let outputBlob = null;
    for (const quality of QUALITY_STEPS) {
      outputBlob = await canvasToBlob(canvas, targetMime, quality);
      if (outputBlob.size <= TARGET_MAX_BYTES || quality <= FALLBACK_MAX_QUALITY) {
        break;
      }
    }

    if (!outputBlob) {
      throw new Error('Compresión no disponible');
    }

    const sanitizedName = (file.name || 'cat-image').replace(/\.[a-zA-Z0-9]+$/, '');
    const outputName = `${sanitizedName}.${extension}`;
    return new File([outputBlob], outputName, { type: targetMime, lastModified: Date.now() });
  };

  input.addEventListener('change', async () => {
    const file = input.files && input.files[0] ? input.files[0] : null;
    compressedFile = null;

    if (previewEl) {
      previewEl.src = '';
      previewEl.style.display = 'none';
    }

    if (!file) {
      setCompressionInfo();
      setState('Estado: esperando archivo', false);
      return;
    }

    setCompressionInfo({ originalBytes: file.size });

    if (previewEl) {
      previewEl.src = URL.createObjectURL(file);
      previewEl.style.display = 'block';
    }

    try {
      setState('Estado: comprimiendo...', true);
      const nextFile = await buildCompressedFile(file);
      compressedFile = nextFile;
      setCompressionInfo({ originalBytes: file.size, compressedBytes: nextFile.size, format: nextFile.type });
      setState('Estado: listo para enviar', false);
    } catch (err) {
      console.warn('CatGame compression fallback:', err);
      setCompressionInfo({ originalBytes: file.size, compressedBytes: null, format: file.type || '-' });
      setState('Estado: error de compresión (se enviará original)', false);
    }
  });

  form.addEventListener('submit', async (event) => {
    if (compressing) {
      event.preventDefault();
      return;
    }

    if (!compressedFile) {
      return;
    }

    event.preventDefault();

    try {
      setState('Estado: subiendo...', true);
      const formData = new FormData(form);
      formData.set('cat_image', compressedFile, compressedFile.name);

      const response = await fetch(form.action, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        redirect: 'follow',
      });

      if (response.url) {
        window.location.href = response.url;
        return;
      }

      form.submit();
    } catch (err) {
      console.warn('CatGame submit fallback:', err);
      form.submit();
    } finally {
      setState('Estado: listo para enviar', false);
    }
  });
})();
