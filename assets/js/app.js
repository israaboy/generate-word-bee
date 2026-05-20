// ── Toast ──────────────────────────────────────────────────────────────────
(function () {
  const container = document.createElement('div');
  container.className = 'toast-container';
  document.body.appendChild(container);

  window.toast = function (tipo, msg, duracao) {
    duracao = duracao || 4000;
    const el = document.createElement('div');
    el.className = 'toast ' + tipo;
    const icons = { success: '✓', error: '✕', info: 'i' };
    el.innerHTML = '<span class="toast-icon">' + (icons[tipo] || 'i') + '</span><span class="toast-msg">' + msg + '</span>';
    container.appendChild(el);
    requestAnimationFrame(function () {
      requestAnimationFrame(function () { el.classList.add('show'); });
    });
    setTimeout(function () {
      el.classList.remove('show');
      setTimeout(function () { el.remove(); }, 400);
    }, duracao);
  };
})();

// ── Loading button ─────────────────────────────────────────────────────────
window.btnLoading = function (btn, loading) {
  const spinner = btn.querySelector('.spinner');
  const label   = btn.querySelector('.btn-label');
  btn.disabled = loading;
  if (spinner) spinner.style.display = loading ? 'inline-block' : 'none';
  if (label)   label.style.opacity   = loading ? '0' : '1';
};

// ── Drag & Drop genérico ───────────────────────────────────────────────────
window.initDropzone = function (dropzoneId, inputId, accept, onFile) {
  const dz    = document.getElementById(dropzoneId);
  const input = document.getElementById(inputId);
  if (!dz || !input) return;

  dz.addEventListener('dragover',  function(e) { e.preventDefault(); dz.classList.add('drag-over'); });
  dz.addEventListener('dragleave', function()  { dz.classList.remove('drag-over'); });
  dz.addEventListener('drop', function(e) {
    e.preventDefault(); dz.classList.remove('drag-over');
    const f = e.dataTransfer.files[0];
    if (f) onFile(f);
  });
  input.addEventListener('change', function() { if (input.files[0]) onFile(input.files[0]); });
};

// ── Canvas assinatura ──────────────────────────────────────────────────────
window.initCanvas = function (canvasId) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return null;

  const dpr  = window.devicePixelRatio || 1;
  const rect = canvas.getBoundingClientRect();

  // Usa fallback caso o canvas ainda não tenha dimensões renderizadas
  const W = (rect.width  > 0 ? rect.width  : canvas.offsetWidth  || 580);
  const H = (rect.height > 0 ? rect.height : canvas.offsetHeight || 130);

  canvas.width  = Math.round(W * dpr);
  canvas.height = Math.round(H * dpr);
  canvas.style.width  = W + 'px';
  canvas.style.height = H + 'px';

  const ctx = canvas.getContext('2d');
  ctx.scale(dpr, dpr);
  ctx.strokeStyle = '#1a1a1a';
  ctx.lineWidth   = 2;
  ctx.lineCap     = 'round';
  ctx.lineJoin    = 'round';

  let drawing = false;

  function getPos(e) {
    const r   = canvas.getBoundingClientRect();
    const src = e.touches ? e.touches[0] : e;
    return {
      x: (src.clientX - r.left),
      y: (src.clientY - r.top)
    };
  }

  function onStart(e) {
    drawing = true;
    ctx.beginPath();
    const p = getPos(e);
    ctx.moveTo(p.x, p.y);
    const ph = canvas.closest('.canvas-wrap') && canvas.closest('.canvas-wrap').querySelector('.canvas-placeholder');
    if (ph) ph.style.opacity = '0';
  }

  function onMove(e) {
    if (!drawing) return;
    e.preventDefault();
    const p = getPos(e);
    ctx.lineTo(p.x, p.y);
    ctx.stroke();
  }

  function onEnd() { drawing = false; }

  canvas.addEventListener('mousedown',  onStart);
  canvas.addEventListener('mousemove',  onMove);
  canvas.addEventListener('mouseup',    onEnd);
  canvas.addEventListener('mouseleave', onEnd);
  canvas.addEventListener('touchstart', onStart, { passive: false });
  canvas.addEventListener('touchmove',  onMove,  { passive: false });
  canvas.addEventListener('touchend',   onEnd);

  return {
    clear: function() {
      ctx.clearRect(0, 0, canvas.width / dpr, canvas.height / dpr);
      const ph = canvas.closest('.canvas-wrap') && canvas.closest('.canvas-wrap').querySelector('.canvas-placeholder');
      if (ph) ph.style.opacity = '1';
    },
    isEmpty: function() {
      if (canvas.width === 0 || canvas.height === 0) return true;
      const data = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
      for (var i = 0; i < data.length; i++) {
        if (data[i] !== 0) return false;
      }
      return true;
    },
    toBase64: function() {
      return canvas.toDataURL('image/png');
    }
  };
};
