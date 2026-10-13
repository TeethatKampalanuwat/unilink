// ============ UniLink front-end ============
(function () {
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => [...el.querySelectorAll(s)];

  // ปุ่ม toggle: <button data-toggle="#id">
  document.addEventListener('click', (ev) => {
    const t = ev.target.closest('[data-toggle]');
    if (t) {
      ev.preventDefault();
      ev.stopPropagation();
      const target = $(t.dataset.toggle);
      if (target) target.hidden = !target.hidden;
      return;
    }
    // ปิด filter panel เมื่อคลิกข้างนอก
    const fp = $('#filterPanel');
    if (fp && !fp.hidden && !ev.target.closest('.filter-wrap')) fp.hidden = true;
  });

  // แสดง/ซ่อนรหัสผ่าน
  $$('.pw-toggle').forEach((b) => b.addEventListener('click', () => {
    const inp = b.previousElementSibling;
    inp.type = inp.type === 'password' ? 'text' : 'password';
  }));

  // ยืนยันก่อน submit
  $$('form[data-confirm]').forEach((f) => f.addEventListener('submit', (e) => {
    if (!confirm(f.dataset.confirm)) e.preventDefault();
  }));
  $$('[data-confirm-btn]').forEach((b) => b.addEventListener('click', (e) => {
    if (!confirm(b.dataset.confirmBtn)) e.preventDefault();
  }));

  // ---------- Modal รายละเอียดกิจกรรม (home) ----------
  const modal = $('#actModal');
  if (modal) {
    const open = (card) => {
      const d = JSON.parse(card.dataset.activity);
      $$('[data-f]', modal).forEach((el) => {
        const k = el.dataset.f;
        if (k === 'btn') return;
        el.textContent = (d[k] === null || d[k] === '' || d[k] === undefined) ? '-' : d[k];
      });
      if (!d.description) $('[data-f=description]', modal).textContent = 'ไม่มีรายละเอียดเพิ่มเติม';
      const img = $('.modal-img', modal);
      img.hidden = !d.image;
      if (d.image) img.src = d.image;
      const btn = $('[data-f=btn]', modal);
      btn.textContent = d.btnText === 'APPLY' ? 'APPLY NOW 🚀' : d.btnText;
      btn.className = 'btn btn-block ' + d.btnClass;
      if (d.btnHref) btn.href = d.btnHref; else btn.removeAttribute('href');
      modal.hidden = false;
      document.body.style.overflow = 'hidden';
    };
    const close = () => { modal.hidden = true; document.body.style.overflow = ''; };
    $$('.act-card[data-activity]').forEach((card) => {
      card.addEventListener('click', (e) => { if (!e.target.closest('a, button')) open(card); });
      card.addEventListener('keydown', (e) => { if (e.key === 'Enter') open(card); });
    });
    modal.addEventListener('click', (e) => { if (e.target === modal || e.target.closest('[data-close]')) close(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
  }

  // ---------- หน้า Create: คำถามคัดกรอง ----------
  const qList = $('#questionList');
  const qInput = $('#newQuestion');
  if (qList && qInput) {
    const addQ = () => {
      const v = qInput.value.trim();
      if (!v) return;
      const row = document.createElement('div');
      row.className = 'q-row';
      row.innerHTML = '<input name="questions[]"><button type="button" class="btn btn-light btn-sm" data-remove-q>✕</button>';
      row.querySelector('input').value = v;
      qList.appendChild(row);
      qInput.value = '';
      qInput.focus();
    };
    $('#addQuestion').addEventListener('click', addQ);
    qInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); addQ(); } });
    qList.addEventListener('click', (e) => { if (e.target.matches('[data-remove-q]')) e.target.parentElement.remove(); });
    // ถ้าพิมพ์คำถามค้างไว้แล้วกดโพสต์ ให้เพิ่มให้อัตโนมัติ
    $('#activityForm').addEventListener('submit', addQ);
  }

  // ---------- หน้า Create: live preview การ์ด ----------
  const preview = $('#cardPreview');
  if (preview) {
    const logo = $('[data-p=logo]', preview);
    let hasImage = !!logo.querySelector('img');
    $$('[data-preview-field]').forEach((inp) => {
      const k = inp.dataset.previewField;
      const update = () => {
        if (k === 'color') { logo.style.background = inp.value; return; }
        if (k === 'logo') { if (!hasImage) logo.textContent = inp.value || 'UL'; return; }
        if (k === 'image') {
          const f = inp.files[0];
          if (f) { hasImage = true; logo.innerHTML = ''; const im = new Image(); im.src = URL.createObjectURL(f); logo.appendChild(im); }
          return;
        }
        const el = $(`[data-p=${k}]`, preview);
        if (el) el.textContent = inp.value || '-';
      };
      inp.addEventListener('input', update);
      inp.addEventListener('change', update);
    });
  }

  // ---------- Profile: preview รูป + skill tags ----------
  $$('input[type=file][data-preview]').forEach((inp) => inp.addEventListener('change', () => {
    const f = inp.files[0];
    if (!f) return;
    const holder = inp.parentElement;
    const old = holder.querySelector('.avatar');
    const im = document.createElement('img');
    im.className = 'avatar xl';
    im.src = URL.createObjectURL(f);
    old.replaceWith(im);
  }));

  const tagInput = $('input[data-tags]');
  const tagBox = $('#skillPreview');
  if (tagInput && tagBox) {
    const render = () => {
      tagBox.innerHTML = '';
      tagInput.value.split(',').map((s) => s.trim()).filter(Boolean).forEach((s) => {
        const t = document.createElement('span');
        t.className = 'tag';
        t.textContent = s;
        tagBox.appendChild(t);
      });
    };
    tagInput.addEventListener('input', render);
    render();
  }
})();
