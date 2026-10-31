(() => {
  'use strict';
  const rows = Array.from(document.querySelectorAll('#examRows .qt-exam-row'));
  const search = document.getElementById('examSearch');
  const subject = document.getElementById('examSubjectFilter');
  const sort = document.getElementById('examSort');
  const pageSize = document.getElementById('examPageSize');
  const range = document.getElementById('examRange');
  const visible = document.getElementById('examVisibleCount');
  const pagination = document.getElementById('examPagination');
  const empty = document.getElementById('examEmpty');
  if (!search || !subject || !sort || !pageSize || !pagination) return;
  let page = 1;
  const normalize = s => String(s || '').normalize('NFD').replace(/[\\u0300-\\u036f]/g, '').toLocaleLowerCase('vi');
  function render() {
    const q = normalize(search.value.trim());
    const filtered = rows.filter(row =>
      (!subject.value || row.dataset.subject === subject.value) &&
      (!q || normalize(row.dataset.search).includes(q))
    );
    filtered.sort((a,b) => {
      switch (sort.value) {
        case 'oldest': return Number(a.dataset.id) - Number(b.dataset.id);
        case 'title': return normalize(a.dataset.title).localeCompare(normalize(b.dataset.title), 'vi');
        case 'duration': return Number(a.dataset.duration) - Number(b.dataset.duration);
        default: return Number(b.dataset.id) - Number(a.dataset.id);
      }
    });
    const size = Number(pageSize.value) || 10;
    const totalPages = Math.max(1, Math.ceil(filtered.length / size));
    page = Math.min(Math.max(1, page), totalPages);
    const start = (page - 1) * size;
    rows.forEach(r => r.hidden = true);
    filtered.forEach((r,i) => {
      r.hidden = !(i >= start && i < start + size);
      r.style.display = r.hidden ? 'none' : '';
      if (r.parentNode) r.parentNode.appendChild(r);
    });
    if (empty) empty.hidden = filtered.length !== 0;
    if (visible) visible.textContent = `${filtered.length} đề thi`;
    if (range) range.textContent = filtered.length ? `Hiển thị ${start + 1}–${Math.min(start + size,filtered.length)} / ${filtered.length} đề thi` : 'Không có đề thi';
    pagination.replaceChildren();
    function button(label,target,disabled,current=false) {
      const b = document.createElement('button');
      b.type='button'; b.textContent=label; b.disabled=disabled;
      if(current) b.setAttribute('aria-current','page');
      b.addEventListener('click',()=>{page=target;render();});
      pagination.appendChild(b);
    }
    button('‹',page-1,page===1);
    const from = Math.max(1,Math.min(page-2,totalPages-4));
    const to = Math.min(totalPages,from+4);
    for(let i=from;i<=to;i++) button(String(i),i,false,i===page);
    button('›',page+1,page===totalPages);
  }
  [search,subject,sort,pageSize].forEach(el => el.addEventListener(el===search?'input':'change',()=>{page=1;render();}));
  document.getElementById('examRefresh')?.addEventListener('click',()=>window.location.reload());
  render();
})();
