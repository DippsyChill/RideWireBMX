(() => {
  const source = document.querySelector('#body_html');
  const visualEditor = document.querySelector('[data-visual-editor]');
  const canvas = document.querySelector('[data-rich-content]');
  if (!source || !visualEditor || !canvas) return;

  canvas.innerHTML = source.value;
  source.hidden = true;
  visualEditor.hidden = false;
  try { document.execCommand('defaultParagraphSeparator', false, 'p'); } catch (_) { /* Keep the editor usable when a browser lacks this optional command. */ }

  const sync = () => { source.value = canvas.innerHTML; };
  canvas.addEventListener('input', sync);
  visualEditor.querySelectorAll('[data-command]').forEach((button) => {
    button.addEventListener('pointerdown', (event) => event.preventDefault());
    button.addEventListener('click', () => {
      const command = button.dataset.command;
      let value = button.dataset.value || null;
      if (command === 'createLink') {
        value = window.prompt('Paste a full HTTPS link:');
        if (!value || !/^https:\/\//i.test(value)) return;
      }
      canvas.focus();
      if (command === 'formatBlock') value = `<${value}>`;
      document.execCommand(command, false, value);
      sync();
    });
  });
  source.form?.addEventListener('submit', sync);

  const uploadButton = document.querySelector('[data-upload-image]');
  const fileInput = document.querySelector('[data-image-file]');
  const imageDialog = document.querySelector('[data-image-dialog]');
  const imageDetailsForm = document.querySelector('[data-image-details-form]');
  const insertImageButton = document.querySelector('[data-insert-image]');
  const cancelImageButton = document.querySelector('[data-cancel-image]');
  const uploadStatus = document.querySelector('[data-upload-status]');
  const dialogStatus = document.querySelector('[data-dialog-status]');
  let pendingImage = null;
  let savedRange = null;

  const rememberCaret = () => {
    const selection = window.getSelection();
    if (selection?.rangeCount && canvas.contains(selection.getRangeAt(0).commonAncestorContainer)) savedRange = selection.getRangeAt(0).cloneRange();
  };
  uploadButton?.addEventListener('pointerdown', rememberCaret);
  uploadButton?.addEventListener('click', () => { rememberCaret(); fileInput?.click(); });

  const setUploadMessage = (message, isError = false) => {
    if (uploadStatus) { uploadStatus.textContent = message; uploadStatus.classList.toggle('is-error', isError); }
  };
  const toWebp = async (file) => {
    if (!['image/jpeg','image/png','image/webp'].includes(file.type)) throw new Error('Choose a JPEG, PNG or WebP image.');
    if (file.size > 25 * 1024 * 1024) throw new Error('Choose an image smaller than 25 MB. It will be resized before upload.');
    if (!window.createImageBitmap) throw new Error('This browser cannot prepare the image. Try a recent version of Chrome, Edge, Firefox or Safari.');
    const bitmap = await createImageBitmap(file);
    const scale = Math.min(1, 2200 / Math.max(bitmap.width, bitmap.height));
    const canvasEl = document.createElement('canvas');
    canvasEl.width = Math.max(1, Math.round(bitmap.width * scale));
    canvasEl.height = Math.max(1, Math.round(bitmap.height * scale));
    const context = canvasEl.getContext('2d');
    if (!context) throw new Error('Could not prepare this image in the browser.');
    context.drawImage(bitmap, 0, 0, canvasEl.width, canvasEl.height);
    bitmap.close();
    for (const quality of [0.84,0.74,0.64,0.54]) {
      const blob = await new Promise((resolve) => canvasEl.toBlob(resolve, 'image/webp', quality));
      if (blob && blob.size <= 8 * 1024 * 1024) return blob;
    }
    throw new Error('This image is still larger than 8 MB after resizing. Choose a smaller image.');
  };

  fileInput?.addEventListener('change', async () => {
    const file = fileInput.files?.[0];
    fileInput.value = '';
    if (!file) return;
    setUploadMessage('Preparing the image on this device…');
    try {
      pendingImage = await toWebp(file);
      dialogStatus.textContent = `Ready to upload (${Math.max(1,Math.round(pendingImage.size/1024))} KB).`;
      dialogStatus.classList.remove('is-error');
      imageDetailsForm.reset();
      imageDialog.showModal();
      imageDetailsForm.querySelector('#inline-image-alt')?.focus();
    } catch (error) { pendingImage = null; setUploadMessage(error.message || 'Could not prepare this image.', true); }
  });

  cancelImageButton?.addEventListener('click', () => { pendingImage = null; imageDialog.close(); setUploadMessage('Image upload cancelled.'); });
  insertImageButton?.addEventListener('click', async () => {
    const altInput = imageDetailsForm.querySelector('#inline-image-alt');
    if (!altInput.reportValidity() || !pendingImage) return;
    insertImageButton.disabled = true;
    dialogStatus.textContent = 'Uploading image…';
    dialogStatus.classList.remove('is-error');
    const data = new FormData();
    data.append('csrf', document.querySelector('input[name="csrf"]')?.value || '');
    data.append('image_file', new File([pendingImage], 'ridewire-story-image.webp', {type:'image/webp'}));
    try {
      const section = document.body.dataset.editorSection;
      const response = await fetch(`upload-image.php?section=${encodeURIComponent(section)}`, {method:'POST',credentials:'same-origin',body:data,headers:{Accept:'application/json'}});
      const result = await response.json();
      if (!response.ok) throw new Error(result.error || 'Image upload failed.');
      const figure = document.createElement('figure');
      const image = document.createElement('img'); image.src = result.url; image.alt = altInput.value.trim(); image.loading = 'lazy'; figure.append(image);
      const captionText = imageDetailsForm.querySelector('#inline-image-caption').value.trim();
      if (captionText) { const caption = document.createElement('figcaption'); caption.textContent = captionText; figure.append(caption); }
      if (savedRange && canvas.contains(savedRange.commonAncestorContainer)) { const selection = window.getSelection(); selection.removeAllRanges(); selection.addRange(savedRange); savedRange.deleteContents(); savedRange.insertNode(figure); }
      else canvas.append(figure);
      const spacer = document.createElement('p'); spacer.append(document.createElement('br')); figure.after(spacer);
      pendingImage = null; sync(); imageDialog.close(); canvas.focus();
      setUploadMessage(`Image added. Upload storage: ${result.storage_used} of ${result.storage_limit}.`);
    } catch (error) { dialogStatus.textContent = error.message || 'Image upload failed.'; dialogStatus.classList.add('is-error'); }
    finally { insertImageButton.disabled = false; }
  });

  const title = document.querySelector('#title');
  const slug = document.querySelector('#slug');
  let manuallySetSlug = Boolean(slug?.value);
  slug?.addEventListener('input', () => { manuallySetSlug = Boolean(slug.value); });
  title?.addEventListener('input', () => {
    if (manuallySetSlug || !slug) return;
    slug.value = title.value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
  });
})();
