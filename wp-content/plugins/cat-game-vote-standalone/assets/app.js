(function () {
  const modal = document.getElementById('catgame-event-rules-modal');
  const trigger = document.getElementById('catgame-event-rules-trigger');
  if (!modal || !trigger) {
    return;
  }

  const eventId = trigger.getAttribute('data-event-id') || '';
  const storageKey = eventId ? `catgame_rules_seen_${eventId}` : '';

  const openModal = () => {
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    trigger.setAttribute('aria-expanded', 'true');
  };

  const closeModal = () => {
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    trigger.setAttribute('aria-expanded', 'false');
  };

  trigger.addEventListener('click', openModal);

  modal.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) {
      return;
    }
    if (target.closest('[data-modal-close="1"]')) {
      closeModal();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && modal.classList.contains('is-open')) {
      closeModal();
    }
  });

  if (storageKey) {
    try {
      if (!window.sessionStorage.getItem(storageKey)) {
        openModal();
        window.sessionStorage.setItem(storageKey, '1');
      }
    } catch (_) {
      openModal();
    }
  }
})();

(function () {
  window.catgameToast = function catgameToast(message, type = 'info', timeout = 2200) {
    const el = document.getElementById('catgame-toast');
    if (!el || !message) {
      return;
    }

    el.className = `catgame-toast is-visible is-${type}`;
    el.textContent = message;

    window.clearTimeout(el._t);
    el._t = window.setTimeout(() => {
      el.className = 'catgame-toast';
      el.textContent = '';
    }, timeout);
  };

  const params = new URLSearchParams(window.location.search);
  const uploaded = params.get('uploaded');
  const voted = params.get('voted');
  const deleted = params.get('deleted');
  const profileSaved = params.get('profile_saved');
  const completeProfile = params.get('complete_profile');
  const error = params.get('catgame_error');

  if (voted === '1') {
    window.catgameToast('Gracias por tu voto', 'success');
  }

  if (uploaded === '1') {
    window.catgameToast('Foto subida correctamente', 'success');
  }

  if (deleted === '1') {
    window.catgameToast('Publicación eliminada', 'success');
  }

  if (profileSaved === '1') {
    window.catgameToast('Ubicación guardada', 'success');
  }

  if (completeProfile === '1') {
    window.catgameToast('Completa tu ciudad y país para continuar', 'info');
  }

  if (error) {
    console.warn('CatGame warning:', error);
    const errorMessages = {
      upload_banned: 'Tienes restringida la subida de publicaciones temporalmente. Puedes seguir reaccionando.',
    };
    window.catgameToast(errorMessages[error] || 'Ocurrió un error. Intenta nuevamente.', 'error');
  }

  const shouldClean = voted === '1' || uploaded === '1' || deleted === '1' || profileSaved === '1' || completeProfile === '1' || !!error;
  if (shouldClean && window.history && typeof window.history.replaceState === 'function') {
    params.delete('voted');
    params.delete('uploaded');
    params.delete('catgame_error');
    params.delete('deleted');
    params.delete('profile_saved');
    params.delete('complete_profile');
    const nextQuery = params.toString();
    const nextUrl = `${window.location.pathname}${nextQuery ? `?${nextQuery}` : ''}${window.location.hash}`;
    window.history.replaceState({}, '', nextUrl);
  }
})();

(function () {
  const form = document.querySelector('form.cg-form input[name="action"][value="catgame_upload"]')?.closest('form');
  const input = document.getElementById('catgame-cat-image');
  if (!form || !input) {
    return;
  }

  const stateEl = document.getElementById('catgame-compress-status');
  const previewEl = document.getElementById('catgame-image-preview');
  const filePickerText = document.querySelector('.cg-file-picker-text');
  const universalPickerBtn = document.querySelector('[data-catgame-pick-universal="1"]');
  const filePickerBtn = document.querySelector('[data-catgame-pick-file="1"]');
  const cameraPickerBtn = document.querySelector('[data-catgame-pick-camera="1"]');
  const universalProxyInput = document.getElementById('catgame-cat-image-universal');
  const fileProxyInput = document.getElementById('catgame-cat-image-file');
  const cameraProxyInput = document.getElementById('catgame-cat-image-camera');
  const titleInput = form.querySelector('input[name="title"]');
  const submitButton = form.querySelector('button[type="submit"]');

  const TARGET_MAX_SIDE = 1280;
  const TARGET_MAX_BYTES = 900 * 1024;
  const FALLBACK_MAX_QUALITY = 0.6;
  const QUALITY_STEPS = [0.82, 0.78, 0.72, 0.66, 0.6];
  const FALLBACK_FORMAT = 'image/jpeg';

  let compressedFile = null;
  let compressing = false;
  let previewObjectUrl = null;

  const syncFileToMainInput = (sourceInput) => {
    if (!sourceInput || typeof DataTransfer !== 'function') {
      return;
    }

    const nextFile = sourceInput.files && sourceInput.files[0] ? sourceInput.files[0] : null;
    if (!nextFile) {
      return;
    }

    const dt = new DataTransfer();
    dt.items.add(nextFile);
    input.files = dt.files;
    input.dispatchEvent(new Event('change', { bubbles: true }));
  };

  const setState = (text, isBusy) => {
    if (stateEl) stateEl.textContent = text;
    if (submitButton) submitButton.disabled = !!isBusy;
    compressing = !!isBusy;
  };


  const isIOS = () => {
    const ua = window.navigator.userAgent || '';
    const platform = window.navigator.platform || '';
    const maxTouchPoints = Number(window.navigator.maxTouchPoints || 0);
    const iOSUA = /iPad|iPhone|iPod/i.test(ua);
    const iOSPlatform = /iPad|iPhone|iPod/i.test(platform);
    const iPadOS = platform === 'MacIntel' && maxTouchPoints > 1;
    const isAppleWebKit = /AppleWebKit/i.test(ua);
    return (iOSUA || iOSPlatform || iPadOS) && isAppleWebKit;
  };

  const isiOSClient = isIOS();
  if (universalPickerBtn) universalPickerBtn.classList.toggle('is-hidden', !isiOSClient);
  if (filePickerBtn) filePickerBtn.classList.toggle('is-hidden', isiOSClient);
  if (cameraPickerBtn) cameraPickerBtn.classList.toggle('is-hidden', isiOSClient);
  if (filePickerText) {
    filePickerText.textContent = isiOSClient ? 'Selecciona desde Fotos o Cámara de iOS' : 'JPG, PNG o WEBP';
  }

  if (titleInput) {
    titleInput.addEventListener('invalid', () => {
      const requiredMessage = titleInput.getAttribute('data-required-message') || 'Este campo es obligatorio.';
      if (titleInput.validity.valueMissing) {
        titleInput.setCustomValidity(requiredMessage);
      } else {
        titleInput.setCustomValidity('');
      }
    });

    titleInput.addEventListener('input', () => {
      titleInput.setCustomValidity('');
    });
  }

  if (universalPickerBtn && universalProxyInput) {
    universalPickerBtn.addEventListener('click', () => {
      universalProxyInput.click();
    });
    universalProxyInput.addEventListener('change', () => syncFileToMainInput(universalProxyInput));
  }


  if (isiOSClient && cameraProxyInput) {
    cameraProxyInput.disabled = true;
  }

  if (filePickerBtn && fileProxyInput) {
    filePickerBtn.addEventListener('click', () => {
      fileProxyInput.click();
    });
    fileProxyInput.addEventListener('change', () => syncFileToMainInput(fileProxyInput));
  }

  if (!isiOSClient && cameraPickerBtn && cameraProxyInput) {
    cameraPickerBtn.addEventListener('click', () => {
      cameraProxyInput.click();
    });
    cameraProxyInput.addEventListener('change', () => syncFileToMainInput(cameraProxyInput));
  }


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
      if (previewObjectUrl) {
        URL.revokeObjectURL(previewObjectUrl);
        previewObjectUrl = null;
      }
      previewEl.src = '';
      previewEl.style.display = 'none';
    }

    if (!file) {
      if (filePickerText) filePickerText.textContent = 'JPG, PNG o WEBP';
      setState('Estado: esperando archivo', false);
      return;
    }


    if (previewEl) {
      previewObjectUrl = URL.createObjectURL(file);
      previewEl.src = previewObjectUrl;
      previewEl.style.display = 'block';
    }

    try {
      setState('Estado: comprimiendo...', true);
      const nextFile = await buildCompressedFile(file);
      compressedFile = nextFile;
      setState('Estado: listo para enviar', false);
    } catch (err) {
      console.warn('CatGame compression fallback:', err);
      setState('Estado: error de compresión (se enviará original)', false);
    }
  });

  form.addEventListener('submit', (event) => {
    if (compressing) {
      event.preventDefault();
      return;
    }

    window.catgameToast?.('Subiendo foto…', 'info', 2600);

    if (!compressedFile) {
      return;
    }

    try {
      if (typeof DataTransfer !== 'function') {
        return;
      }

      const dt = new DataTransfer();
      dt.items.add(compressedFile);
      input.files = dt.files;
      setState('Estado: enviando archivo comprimido...', true);

      window.setTimeout(() => {
        setState('Estado: listo para enviar', false);
      }, 1200);
    } catch (err) {
      console.warn('CatGame submit fallback:', err);
    }
  });


  const uploadRulesModal = document.getElementById('catgame-upload-rules-modal');
  const openUploadRulesButtons = Array.from(document.querySelectorAll('[data-open-upload-rules="1"]'));
  const confirmTermsCheckbox = document.getElementById('catgame-confirm-terms');
  if (uploadRulesModal && openUploadRulesButtons.length) {
    const setUploadRulesOpen = (open) => {
      uploadRulesModal.classList.toggle('is-open', open);
      uploadRulesModal.setAttribute('aria-hidden', open ? 'false' : 'true');
    };

    openUploadRulesButtons.forEach((btn) => {
      btn.addEventListener('click', () => setUploadRulesOpen(true));
    });

    if (confirmTermsCheckbox) {
      confirmTermsCheckbox.addEventListener('click', () => setUploadRulesOpen(true));
    }

    uploadRulesModal.addEventListener('click', (event) => {
      const target = event.target;
      if (!(target instanceof HTMLElement)) return;
      if (target.closest('[data-upload-rules-close="1"]')) {
        setUploadRulesOpen(false);
      }
    });
  }
})();

(function () {
  const input = document.getElementById('catgame-upload-tags-input');
  const root = document.getElementById('catgame-tag-suggestions');
  if (!input || !root) {
    return;
  }

  const api = window.CATGAME || {};
  const endpoint = api.ajaxUrl || '';
  const nonce = api.nonce || '';

  let userTags = [];

  const escapeHtml = (value) => String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');

  const splitTags = (value) => value.split(/[\n,]+/).map((item) => item.trim().toLowerCase()).filter(Boolean);

  const normalizeTag = (value) => {
    const stripped = String(value || '').normalize('NFD').replace(/[̀-ͯ]/g, '');
    return stripped
      .toLowerCase()
      .replace(/[^a-z0-9_\s-]/g, '')
      .replace(/\s+/g, '_')
      .replace(/-+/g, '_')
      .replace(/^_+|_+$/g, '')
      .slice(0, 20);
  };

  const activeToken = () => {
    const value = input.value || '';
    const parts = value.split(/[\n,]/);
    return normalizeTag(parts[parts.length - 1] || '');
  };

  const renderSuggestions = () => {
    const token = activeToken();
    if (!token || !Array.isArray(userTags) || userTags.length === 0) {
      root.innerHTML = '';
      return;
    }

    const selected = new Set(splitTags(input.value || ''));
    const items = userTags
      .filter((tag) => tag.includes(token) && !selected.has(tag))
      .slice(0, 8);

    if (!items.length) {
      root.innerHTML = '';
      return;
    }

    root.innerHTML = `<p class="cg-tag-suggest__title">Mis etiquetas sugeridas</p><div class="cg-tag-suggest__list">${items.map((tag) => `<button type="button" class="cg-tag-suggest__btn" data-tag="${escapeHtml(tag)}">${escapeHtml(tag)}</button>`).join('')}</div>`;
  };

  const applySuggestion = (tag) => {
    const parts = (input.value || '').split(/[\n,]+/).map((item) => item.trim()).filter(Boolean);
    parts.pop();
    parts.push(tag);
    input.value = `${parts.join(', ')}, `;
    input.focus();
    renderSuggestions();
  };

  root.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) {
      return;
    }
    const btn = target.closest('[data-tag]');
    if (!(btn instanceof HTMLElement)) {
      return;
    }
    const tag = normalizeTag(btn.getAttribute('data-tag') || '');
    if (tag) {
      applySuggestion(tag);
    }
  });

  input.addEventListener('input', renderSuggestions);
  input.addEventListener('focus', renderSuggestions);

  const bootstrapFromDataset = () => {
    const raw = root.getAttribute('data-user-tags') || '[]';
    try {
      const parsed = JSON.parse(raw);
      if (Array.isArray(parsed)) {
        userTags = parsed.map((tag) => normalizeTag(String(tag || ''))).filter(Boolean);
      }
    } catch (_) {
      userTags = [];
    }
  };

  const fetchSuggestions = async () => {
    if (!endpoint || !nonce) {
      bootstrapFromDataset();
      renderSuggestions();
      return;
    }

    try {
      const body = new URLSearchParams();
      body.set('action', 'catgame_tag_suggestions');
      body.set('nonce', nonce);

      const response = await fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        credentials: 'same-origin',
        body: body.toString(),
      });

      const payload = await response.json();
      const tags = payload?.success && Array.isArray(payload?.data?.tags) ? payload.data.tags : [];
      userTags = tags.map((tag) => normalizeTag(String(tag || ''))).filter(Boolean);
      renderSuggestions();
    } catch (_) {
      bootstrapFromDataset();
      renderSuggestions();
    }
  };

  fetchSuggestions();
})();

(function () {
  const voteForm = document.getElementById('catgame-vote-form');
  if (!voteForm) {
    return;
  }

  const stars = Array.from(voteForm.querySelectorAll('.cg-star'));
  const ratingInput = document.getElementById('catgame-rating-value');
  const errorEl = document.getElementById('catgame-vote-error');
  const alreadyVotedEl = document.getElementById('catgame-already-voted');

  if (!stars.length || !ratingInput) {
    return;
  }

  const paintStars = (rating) => {
    stars.forEach((star) => {
      const value = Number(star.getAttribute('data-rating') || '0');
      const active = value <= rating;
      star.classList.toggle('is-active', active);
      star.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
  };

  const showError = (message) => {
    if (!errorEl) return;
    errorEl.textContent = message;
    errorEl.style.display = 'block';
  };

  const clearError = () => {
    if (!errorEl) return;
    errorEl.textContent = '';
    errorEl.style.display = 'none';
  };

  stars.forEach((star) => {
    star.addEventListener('click', () => {
      const value = Number(star.getAttribute('data-rating') || '0');
      ratingInput.value = String(value);
      paintStars(value);
      clearError();
    });

    star.addEventListener('mouseenter', () => {
      const value = Number(star.getAttribute('data-rating') || '0');
      paintStars(value);
    });
  });

  const starsWrap = voteForm.querySelector('.cg-rating-stars');
  if (starsWrap) {
    starsWrap.addEventListener('mouseleave', () => {
      paintStars(Number(ratingInput.value || '0'));
    });
  }

  voteForm.addEventListener('submit', (event) => {
    const rating = Number(ratingInput.value || '0');
    if (!Number.isInteger(rating) || rating < 1 || rating > 5) {
      event.preventDefault();
      showError('Selecciona una valoración de 1 a 5 antes de enviar.');
      return;
    }

    window.catgameToast?.('Enviando voto…', 'info', 2600);
    clearError();
  });

  if (window.location.search.includes('catgame_error=duplicate_vote')) {
    voteForm.style.display = 'none';
    if (alreadyVotedEl) {
      alreadyVotedEl.style.display = 'block';
    }
  }
})();

(function () {
  const wrappers = Array.from(document.querySelectorAll('.cg-img-wrap'));
  if (!wrappers.length) {
    return;
  }

  wrappers.forEach((wrapper) => {
    const img = wrapper.querySelector('.cg-img');
    if (!img) {
      return;
    }

    const markLoaded = () => {
      wrapper.classList.remove('is-error');
      wrapper.classList.add('is-loaded');
    };
    const markError = () => {
      wrapper.classList.remove('is-loaded');
      wrapper.classList.add('is-error');
    };

    if (img.complete) {
      if (img.naturalWidth > 0) {
        markLoaded();
      } else {
        markError();
      }
      return;
    }

    img.addEventListener('load', markLoaded, { once: true });
    img.addEventListener('error', markError, { once: true });
  });
})();

(function () {
  const profileButton = document.querySelector('.js-share-profile');
  const bestButton = document.querySelector('.js-share-best');

  const copyText = async (text) => {
    if (!text) return false;
    try {
      await navigator.clipboard.writeText(text);
      window.catgameToast?.('Enlace copiado', 'success');
      return true;
    } catch (_) {
      window.catgameToast?.('No se pudo copiar', 'error');
      return false;
    }
  };

  if (profileButton) {
    profileButton.addEventListener('click', async () => {
      const url = profileButton.getAttribute('data-url') || window.location.href;
      await copyText(url);
    });
  }

  if (bestButton) {
    bestButton.addEventListener('click', async () => {
      const url = bestButton.getAttribute('data-url') || window.location.href;
      if (navigator.share) {
        try {
          await navigator.share({ title: 'Cat Game Vote', text: 'Mira esta publicación', url });
          return;
        } catch (_) {
          // fallback copy
        }
      }
      await copyText(url);
    });
  }
})();


(function () {
  const profileForm = document.querySelector('#catgame-profile-form');
  if (!profileForm) {
    return;
  }

  const toggleButton = profileForm.querySelector('.js-avatar-color-toggle');
  const colorsPanel = profileForm.querySelector('.cg-avatar-colors');
  const saveButton = profileForm.querySelector('.js-profile-save');
  const cityInput = profileForm.querySelector('input[name="default_city"]');
  const countryInput = profileForm.querySelector('input[name="default_country"]');
  if (!toggleButton || !colorsPanel || !saveButton) {
    return;
  }

  const showSave = () => saveButton.classList.remove('is-hidden');
  const hideSave = () => saveButton.classList.add('is-hidden');

  const setExpanded = (expanded) => {
    colorsPanel.hidden = !expanded;
    toggleButton.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    if (expanded) {
      showSave();
    }
  };

  setExpanded(false);
  hideSave();

  toggleButton.addEventListener('click', () => {
    const isExpanded = toggleButton.getAttribute('aria-expanded') === 'true';
    setExpanded(!isExpanded);
  });

  profileForm.querySelectorAll('input[name="avatar_color"]').forEach((radio) => {
    radio.addEventListener('change', showSave);
  });

  [cityInput, countryInput].forEach((input) => {
    if (!input) return;
    input.addEventListener('input', showSave);
  });

  profileForm.addEventListener('submit', () => {
    setExpanded(false);
    hideSave();
  });
})();


(function () {
  const authShell = document.querySelector('.cg-auth-shell');
  if (!authShell) {
    return;
  }

  const tabs = Array.from(authShell.querySelectorAll('.cg-auth-tab'));
  const panels = Array.from(authShell.querySelectorAll('.cg-auth-panel'));

  const activateTab = (tabName) => {
    tabs.forEach((tab) => {
      const active = tab.dataset.authTab === tabName;
      tab.classList.toggle('is-active', active);
      tab.setAttribute('aria-selected', active ? 'true' : 'false');
    });

    panels.forEach((panel) => {
      const active = panel.dataset.authPanel === tabName;
      if (panel.dataset.authPanel === 'reset') {
        return;
      }
      panel.classList.toggle('is-active', active);
    });
  };

  tabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      activateTab(tab.dataset.authTab || 'login');
    });
  });

  authShell.addEventListener('click', (event) => {
    const toggle = event.target.closest('.cg-password-toggle');
    if (!toggle) {
      return;
    }

    const wrap = toggle.closest('.cg-password-wrap');
    const input = wrap ? wrap.querySelector('input') : null;
    if (!input) {
      return;
    }

    const visible = input.type === 'text';
    input.type = visible ? 'password' : 'text';
    toggle.classList.toggle('is-visible', !visible);
    toggle.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
  });
})();


(function () {
  const modal = document.getElementById('catgame-confirm-modal');
  const titleEl = document.getElementById('catgame-confirm-title');
  const textEl = document.getElementById('catgame-confirm-text');
  const acceptBtn = document.getElementById('catgame-confirm-accept');
  if (!modal || !titleEl || !textEl || !acceptBtn) {
    return;
  }

  let onAccept = null;

  const closeModal = () => {
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    onAccept = null;
  };

  const openModal = (title, text, acceptLabel, callback) => {
    titleEl.textContent = title || 'Confirmar acción';
    textEl.textContent = text || '¿Deseas continuar?';
    acceptBtn.textContent = acceptLabel || 'Eliminar';
    onAccept = callback;
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
  };

  modal.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) return;
    if (target.closest('[data-confirm-close="1"]')) {
      closeModal();
    }
  });

  acceptBtn.addEventListener('click', () => {
    if (typeof onAccept === 'function') {
      onAccept();
    }
    closeModal();
  });

  document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (form.dataset.cgConfirm !== '1') return;

    event.preventDefault();
    const title = form.dataset.cgConfirmTitle || 'Confirmar acción';
    const text = form.dataset.cgConfirmText || '¿Deseas continuar?';
    openModal(title, text, 'Eliminar', () => form.submit());
  }, true);
})();

(function () {
  const config = window.CATGAME_REACTIONS;
  if (!config) {
    return;
  }

  const labels = {
    adorable: { emoji: '😺', label: 'Adorable' },
    funny: { emoji: '😂', label: 'Me hizo reír' },
    cute: { emoji: '🥰', label: 'Tierno' },
    wow: { emoji: '🤩', label: 'Impresionante' },
    epic: { emoji: '🔥', label: 'Épico' },
  };

  const LONG_PRESS_MS = 350;
  const CANCEL_MOVE_PX = 10;

  const REACTION_MESSAGES = {
    loginRequired: 'Inicia sesión para reaccionar',
    readonly: 'No disponible',
    saveError: 'No se pudo guardar la reacción',
    rateLimited: (seconds) => `Has alcanzado el límite. Intenta nuevamente en ${seconds}s.`,
  };

  const initReactionButton = (btn) => {
    const reaction = btn.dataset.reaction || '';
    const meta = labels[reaction];
    if (!meta) return;

    btn.classList.add('reaction-btn');
    const label = btn.dataset.label || meta.label;
    btn.dataset.label = label;
    btn.setAttribute('aria-label', label);

    if (!btn.querySelector('.emoji')) {
      btn.textContent = '';

      const emoji = document.createElement('span');
      emoji.className = 'emoji';
      emoji.textContent = meta.emoji;

      const count = document.createElement('span');
      count.className = 'count';
      count.textContent = '0';

      btn.appendChild(emoji);
      btn.appendChild(count);
    }
  };

  const paintWidget = (widget, counts) => {
    const active = counts.user_reaction || '';
    widget.querySelectorAll('.cg-reaction-btn').forEach((btn) => {
      const reaction = btn.dataset.reaction || '';
      const countEl = btn.querySelector('.count');
      if (countEl && Object.hasOwn(counts, reaction)) {
        countEl.textContent = String(counts[reaction] || 0);
      }
      const isSelected = reaction === active;
      btn.classList.toggle('is-active', isSelected);
      btn.classList.toggle('is-selected', isSelected);
      btn.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
    });
  };

  const fetchCounts = async (widget) => {
    const submissionId = Number(widget.dataset.submissionId || '0');
    if (!submissionId) return;

    const url = new URL(config.getCountsUrl, window.location.origin);
    url.searchParams.set('submission_id', String(submissionId));
    url.searchParams.set('_wpnonce', config.nonce || '');

    const response = await fetch(url.toString(), { credentials: 'same-origin' });
    const payload = await response.json();
    if (payload?.success && payload.data) {
      paintWidget(widget, payload.data);
      return payload.data;
    }
    return null;
  };

  const applyOptimisticReaction = (widget, currentState, reactionType) => {
    const nextState = JSON.parse(JSON.stringify(currentState));
    const old = nextState.user_reaction || null;
    if (old === reactionType) {
      return nextState;
    }

    if (old && Object.hasOwn(nextState, old)) {
      nextState[old] = Math.max(0, Number(nextState[old] || 0) - 1);
    }
    if (Object.hasOwn(nextState, reactionType)) {
      nextState[reactionType] = Number(nextState[reactionType] || 0) + 1;
    }
    nextState.user_reaction = reactionType;
    paintWidget(widget, nextState);
    return nextState;
  };

  const sendReaction = async (widget, reactionType) => {
    const submissionId = Number(widget.dataset.submissionId || '0');
    if (!submissionId || !reactionType) return null;

    const fd = new FormData();
    fd.append('submission_id', String(submissionId));
    fd.append('reaction_type', reactionType);
    fd.append('_wpnonce', config.nonce || '');

    const response = await fetch(config.addOrUpdateUrl, {
      method: 'POST',
      body: fd,
      credentials: 'same-origin',
    });

    let payload = null;
    try {
      payload = await response.json();
    } catch (_) {
      payload = null;
    }

    if (payload?.success && payload.data) {
      return { ok: true, data: payload.data };
    }

    return {
      ok: false,
      message: payload?.data?.message || 'No se pudo guardar la reacción',
      status: Number(response.status || 0),
      code: payload?.data?.code || '',
      retryAfter: Number(payload?.data?.retry_after || 0),
    };
  };

  let globalTooltip = null;

  const getGlobalTooltip = () => {
    if (globalTooltip && document.body.contains(globalTooltip)) {
      return globalTooltip;
    }
    globalTooltip = document.createElement('span');
    globalTooltip.className = 'catgv-tooltip';
    globalTooltip.setAttribute('aria-hidden', 'true');
    document.body.appendChild(globalTooltip);
    return globalTooltip;
  };

  const hideLongPressTooltip = () => {
    if (!globalTooltip) return;
    globalTooltip.style.opacity = '0';
    globalTooltip.style.transform = 'translate(-50%, -6px)';
  };

  const showLongPressTooltip = (btn) => {
    const tooltip = getGlobalTooltip();
    const label = btn.dataset.label || '';
    if (!label) return;

    const rect = btn.getBoundingClientRect();
    tooltip.textContent = label;
    tooltip.style.left = `${rect.left + (rect.width / 2)}px`;
    tooltip.style.top = `${rect.top - 10}px`;
    tooltip.style.opacity = '1';
    tooltip.style.transform = 'translate(-50%, -100%)';
  };

  const clearLongPressUI = (btn) => {
    btn.classList.remove('active-hold');
    hideLongPressTooltip();
  };

  const showLongPressUI = (btn) => {
    btn.classList.add('active-hold');
    showLongPressTooltip(btn);
  };

  const floatReaction = (widget, btn) => {
    const emoji = btn.querySelector('.emoji')?.textContent || '';
    if (!emoji) return;

    const anchorRect = btn.getBoundingClientRect();
    const floating = document.createElement('span');
    floating.className = 'catgv-float-emoji';
    floating.setAttribute('aria-hidden', 'true');
    floating.textContent = emoji;
    floating.style.left = `${anchorRect.left + (anchorRect.width / 2)}px`;
    floating.style.top = `${anchorRect.top + (anchorRect.height / 2)}px`;

    document.body.appendChild(floating);
    floating.addEventListener('animationend', () => floating.remove(), { once: true });
    window.setTimeout(() => floating.remove(), 700);
  };

  const initReactionWidget = (widget) => {
    if (!(widget instanceof HTMLElement)) return;
    if (widget.dataset.reactionsReady === '1') return;
    widget.dataset.reactionsReady = '1';
    const buttons = Array.from(widget.querySelectorAll('.cg-reaction-btn'));
    const isLoggedIn = widget.dataset.loggedIn === '1';
    const isReadonly = widget.dataset.readonly === '1';
    const readonlyMessage = widget.dataset.readonlyMessage || (isLoggedIn ? REACTION_MESSAGES.readonly : REACTION_MESSAGES.loginRequired);
    let currentState = (() => {
      const base = { adorable: 0, funny: 0, cute: 0, wow: 0, epic: 0, user_reaction: widget.dataset.myReaction || null };
      try {
        const parsed = JSON.parse(widget.dataset.reactionCounts || '{}');
        return { ...base, ...parsed, user_reaction: widget.dataset.myReaction || parsed.user_reaction || null };
      } catch (_) {
        return base;
      }
    })();

    buttons.forEach((btn) => initReactionButton(btn));

    if (!isLoggedIn || isReadonly) {
      widget.classList.add('is-readonly');
      paintWidget(widget, currentState);

      widget.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;
        if (target.closest('.cg-reaction-btn')) {
          event.preventDefault();
          window.catgameToast?.(isReadonly ? readonlyMessage : REACTION_MESSAGES.loginRequired, 'info');
        }
      });

      fetchCounts(widget).then((serverState) => {
        if (serverState) {
          currentState = { ...currentState, ...serverState };
          paintWidget(widget, currentState);
        }
      }).catch(() => null);
      return;
    }

    const submitReaction = (btn) => {
      const reactionType = btn.dataset.reaction || '';
      if (!reactionType || widget.classList.contains('is-busy')) return;

      const previousState = JSON.parse(JSON.stringify(currentState));
      currentState = applyOptimisticReaction(widget, currentState, reactionType);

      widget.classList.add('is-busy');
      sendReaction(widget, reactionType)
        .then((result) => {
          if (result?.ok && result.data) {
            currentState = { ...currentState, ...result.data };
            paintWidget(widget, currentState);
            floatReaction(widget, btn);
          } else {
            currentState = previousState;
            paintWidget(widget, currentState);
            const retryAfter = Number(result?.retryAfter || 0);
            const retryMessage = retryAfter > 0
              ? REACTION_MESSAGES.rateLimited(retryAfter)
              : '';
            window.catgameToast?.(retryMessage || result?.message || REACTION_MESSAGES.saveError, 'error');
          }
        })
        .catch(() => {
          currentState = previousState;
          paintWidget(widget, currentState);
          window.catgameToast?.(REACTION_MESSAGES.saveError, 'error');
        })
        .finally(() => {
          widget.classList.remove('is-busy');
        });
    };

    if ('PointerEvent' in window) {
      const states = new WeakMap();

      const cancelState = (btn, state, { moved = false } = {}) => {
        if (!state) return;
        if (state.timer) {
          window.clearTimeout(state.timer);
          state.timer = null;
        }
        state.canceled = true;
        if (moved) {
          state.moved = true;
        }
        clearLongPressUI(btn);
      };

      buttons.forEach((btn) => {
        btn.addEventListener('pointerdown', (event) => {
          if (event.button !== 0 || widget.classList.contains('is-busy')) return;

          const state = {
            pointerId: event.pointerId,
            startX: event.clientX,
            startY: event.clientY,
            moved: false,
            canceled: false,
            longPress: false,
            timer: null,
          };

          state.timer = window.setTimeout(() => {
            state.longPress = true;
            showLongPressUI(btn);
          }, LONG_PRESS_MS);

          states.set(btn, state);
          try {
            btn.setPointerCapture(event.pointerId);
          } catch (_) {
            // noop
          }
        });

        btn.addEventListener('pointermove', (event) => {
          const state = states.get(btn);
          if (!state || state.pointerId !== event.pointerId || state.canceled) return;

          const dx = Math.abs(event.clientX - state.startX);
          const dy = Math.abs(event.clientY - state.startY);
          if (dx > CANCEL_MOVE_PX || dy > CANCEL_MOVE_PX) {
            cancelState(btn, state, { moved: true });
          }
        });

        btn.addEventListener('pointercancel', (event) => {
          const state = states.get(btn);
          if (!state || state.pointerId !== event.pointerId) return;
          cancelState(btn, state);
          states.delete(btn);
        });

        btn.addEventListener('pointerleave', () => {
          const state = states.get(btn);
          if (!state || state.canceled || state.longPress) return;
          cancelState(btn, state, { moved: true });
        });

        btn.addEventListener('pointerup', (event) => {
          const state = states.get(btn);
          if (!state || state.pointerId !== event.pointerId) return;

          if (state.timer) {
            window.clearTimeout(state.timer);
            state.timer = null;
          }

          const shouldVote = !state.canceled && !state.moved;
          clearLongPressUI(btn);
          states.delete(btn);

          if (shouldVote) {
            event.preventDefault();
            submitReaction(btn);
          }
        });

        btn.addEventListener('click', (event) => {
          event.preventDefault();
        });
      });
    } else {
      widget.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;
        const btn = target.closest('.cg-reaction-btn');
        if (!(btn instanceof HTMLButtonElement)) return;
        submitReaction(btn);
      });
    }

    paintWidget(widget, currentState);
    fetchCounts(widget).then((serverState) => {
      if (serverState) {
        currentState = { ...currentState, ...serverState };
        paintWidget(widget, currentState);
      }
    }).catch(() => null);
  };

  const bootstrapReactions = (root = document) => {
    const scope = root instanceof HTMLElement || root instanceof Document ? root : document;
    const widgets = Array.from(scope.querySelectorAll('.cg-reactions'));
    widgets.forEach((widget) => initReactionWidget(widget));
  };

  window.catgameInitReactions = bootstrapReactions;
  bootstrapReactions(document);
})();

(function () {
  const config = window.CATGAME_FEED;
  const container = document.querySelector('[data-feed-more="1"]');
  const list = document.getElementById('catgame-feed-list');
  const button = container ? container.querySelector('[data-feed-more-btn="1"]') : null;
  const end = container ? container.querySelector('[data-feed-end="1"]') : null;
  if (!config || !container || !list || !button || !end) {
    return;
  }

  const perPage = Number(container.dataset.perPage || '20');
  let nextOffset = Number(container.dataset.nextOffset || '0');
  let hasMore = container.dataset.hasMore === '1';
  let isLoading = false;

  const syncUi = () => {
    button.hidden = !hasMore;
    button.disabled = isLoading;
    button.textContent = isLoading ? 'Cargando…' : 'Cargar más';
    end.hidden = hasMore;
  };

  const loadMore = async () => {
    if (!hasMore || isLoading) return;
    isLoading = true;
    syncUi();

    try {
      const url = new URL(config.moreUrl, window.location.origin);
      url.searchParams.set('_wpnonce', config.nonce || '');
      url.searchParams.set('offset', String(nextOffset));
      url.searchParams.set('per_page', String(perPage));
      const response = await fetch(url.toString(), { credentials: 'same-origin' });
      if (!response.ok) {
        throw new Error('http_error');
      }
      const payload = await response.json();
      if (!payload || payload.success !== true || !payload.data || typeof payload.data !== 'object') {
        throw new Error('invalid_payload');
      }

      const html = String(payload.data.html || '');
      if (html !== '') {
        const fragment = document.createRange().createContextualFragment(html);
        list.appendChild(fragment);
        window.catgameInitReactions?.(list);
      }

      hasMore = !!payload.data.has_more;
      nextOffset = Number(payload.data.next_offset || nextOffset);
      container.dataset.nextOffset = String(nextOffset);
      container.dataset.hasMore = hasMore ? '1' : '0';
    } catch (_) {
      window.catgameToast?.('No se pudo cargar más publicaciones', 'error');
    } finally {
      isLoading = false;
      syncUi();
    }
  };

  button.addEventListener('click', loadMore);
  syncUi();
})();

(function () {
  const config = window.CATGAME || {};
  const modal = document.getElementById('catgame-report-modal');
  const form = document.getElementById('catgame-report-form');
  const idInput = document.getElementById('catgame-report-submission-id');
  const nonceInput = document.getElementById('catgame-report-nonce');
  if (!config || !config.ajaxUrl || !config.nonce || !modal || !form || !idInput || !nonceInput) {
    return;
  }

  const setOpen = (open) => {
    modal.classList.toggle('is-open', open);
    modal.setAttribute('aria-hidden', open ? 'false' : 'true');
  };

  document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) return;

    if (target.closest('[data-report-close="1"]')) {
      setOpen(false);
      return;
    }

    const btn = target.closest('[data-report-btn="1"]');
    if (!btn) return;

    const submissionId = Number(btn.getAttribute('data-submission-id') || '0');
    if (!submissionId) return;

    idInput.value = String(submissionId);
    nonceInput.value = String(config.nonce || '');
    setOpen(true);
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const fd = new FormData(form);
    const params = new URLSearchParams();
    params.set('action', 'catgame_report_submission');
    params.set('nonce', String(config.nonce || nonceInput.value || ''));
    params.set('submission_id', String(fd.get('submission_id') || '0'));
    params.set('reason', String(fd.get('reason') || ''));
    params.set('detail', String(fd.get('detail') || ''));

    console.debug('[catgame] report submit', {
      action: params.get('action'),
      submission_id: params.get('submission_id'),
      reason: params.get('reason'),
    });

    try {
      const response = await fetch(config.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        },
        body: params.toString(),
      });

      const rawResponse = await response.text();
      let payload = null;
      if (rawResponse) {
        try {
          payload = JSON.parse(rawResponse);
        } catch (_) {
          payload = null;
        }
      }

      console.debug('[catgame] report response', {
        status: response.status,
        ok: response.ok,
        raw: rawResponse,
        payload,
      });

      if (!response.ok) {
        if (response.status === 403) {
          window.catgameToast?.('Tu red está bloqueando la conexión al servidor (403). Prueba con datos móviles u otra Wi-Fi.', 'error');
          return;
        }

        if (response.status === 0) {
          window.catgameToast?.('No se pudo conectar al servidor desde esta red. Prueba otra conexión.', 'error');
          return;
        }

        window.catgameToast?.(payload?.data?.message || 'No se pudo enviar el reporte.', 'error');
        return;
      }

      if (!payload?.success) {
        window.catgameToast?.(payload?.data?.message || 'No se pudo enviar el reporte.', 'error');
        return;
      }

      const submissionId = Number(idInput.value || '0');
      if (submissionId > 0) {
        document.querySelectorAll(`[data-submission-id="${submissionId}"]`).forEach((widget) => {
          const card = widget.closest('.cg-card');
          if (card) card.remove();
        });
      }

      window.catgameToast?.('Reporte enviado. Esta publicación quedó en revisión.', 'success');
      setOpen(false);
      form.reset();
    } catch (error) {
      const message = String(error?.message || '');
      if (error instanceof TypeError || /Failed to fetch|NetworkError/i.test(message)) {
        window.catgameToast?.('No se pudo conectar al servidor desde esta red. Prueba otra conexión.', 'error');
        return;
      }

      window.catgameToast?.('No se pudo enviar el reporte.', 'error');
    }
  });
})();

(function () {
  const config = window.CATGAME || {};
  const modal = document.getElementById('catgame-appeal-modal');
  const form = document.getElementById('catgame-appeal-form');
  const idInput = document.getElementById('catgame-appeal-submission-id');
  if (!config || !config.ajaxUrl || !config.nonce || !modal || !form || !idInput) {
    return;
  }

  const setOpen = (open) => {
    modal.classList.toggle('is-open', open);
    modal.setAttribute('aria-hidden', open ? 'false' : 'true');
  };

  document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) return;

    if (target.closest('[data-appeal-close="1"]')) {
      setOpen(false);
      return;
    }

    const btn = target.closest('[data-appeal-btn="1"]');
    if (!btn) return;

    const submissionId = Number(btn.getAttribute('data-submission-id') || '0');
    if (!submissionId) return;

    idInput.value = String(submissionId);
    setOpen(true);
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const fd = new FormData(form);
    const params = new URLSearchParams();
    params.set('action', 'catgame_submit_appeal');
    params.set('nonce', String(config.nonce || ''));
    params.set('submission_id', String(fd.get('submission_id') || '0'));
    params.set('message', String(fd.get('message') || ''));

    try {
      const response = await fetch(config.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        },
        body: params.toString(),
      });

      const payload = await response.json().catch(() => null);
      if (!response.ok || !payload?.success) {
        window.catgameToast?.(payload?.data?.message || 'No se pudo enviar la apelación.', 'error');
        return;
      }

      window.catgameToast?.('Apelación enviada (pendiente)', 'success');
      setOpen(false);
      form.reset();
      const submissionId = Number(params.get('submission_id') || '0');
      if (submissionId > 0) {
        document.querySelectorAll(`[data-submission-id="${submissionId}"]`).forEach((btn) => {
          if (btn instanceof HTMLElement && btn.matches('[data-appeal-btn="1"]')) {
            const state = document.createElement('p');
            state.className = 'cg-appeal-state';
            state.textContent = 'Apelación pendiente';
            btn.replaceWith(state);
          }
        });
      }
    } catch (_) {
      window.catgameToast?.('No se pudo enviar la apelación.', 'error');
    }
  });
})();

(function () {
  const config = window.CATGAME || {};
  const bell = document.getElementById('catgame-notif-bell');
  const badge = document.getElementById('catgame-notif-badge');
  const modal = document.getElementById('catgame-notifications-modal');
  const list = document.getElementById('catgame-notifications-list');
  if (!bell || !badge || !modal || !list || !config.ajaxUrl || !config.nonce) {
    return;
  }

  const setOpen = (open) => {
    modal.classList.toggle('is-open', open);
    modal.setAttribute('aria-hidden', open ? 'false' : 'true');
  };

  const setBadge = (count) => {
    const total = Number(count || 0);
    badge.textContent = String(total);
    badge.hidden = total <= 0;
  };

  const formatDate = (iso) => {
    if (!iso) return '';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return '';
    return d.toLocaleString('es-CL', {
      day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });
  };

  const renderItems = (items) => {
    list.innerHTML = '';
    if (!Array.isArray(items) || items.length === 0) {
      const li = document.createElement('li');
      li.className = 'cg-notifications-empty';
      li.textContent = 'No tienes notificaciones';
      list.appendChild(li);
      return;
    }

    items.forEach((item) => {
      const li = document.createElement('li');
      li.className = `cg-notification-item ${item?.read_at ? '' : 'is-unread'}`.trim();
      li.innerHTML = `<p class="cg-notification-title">${item?.title || 'Notificación'}</p>
        <p class="cg-notification-message">${item?.message || ''}</p>
        <small class="cg-notification-date">${formatDate(item?.created_at || '')}</small>`;
      list.appendChild(li);
    });
  };

  const post = async (action) => {
    const params = new URLSearchParams();
    params.set('action', action);
    params.set('nonce', String(config.nonce));
    const response = await fetch(config.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: params.toString(),
    });
    const payload = await response.json();
    if (!response.ok || !payload?.success) {
      throw new Error(payload?.data?.message || 'No se pudo completar la acción.');
    }
    return payload.data || {};
  };

  const loadNotifications = async () => {
    try {
      const data = await post('catgame_get_notifications');
      renderItems(data.items || []);
      setBadge(Number(data.unread_count || 0));
    } catch (_) {
      setBadge(0);
    }
  };

  bell.addEventListener('click', async () => {
    setOpen(true);
    try {
      await post('catgame_mark_notifications_read');
      setBadge(0);
      list.querySelectorAll('.cg-notification-item').forEach((item) => item.classList.remove('is-unread'));
    } catch (_) {
      // noop
    }
  });

  modal.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) return;
    if (target.closest('[data-notifications-close="1"]')) {
      setOpen(false);
    }
  });

  loadNotifications();
})();
