document.addEventListener('DOMContentLoaded', () => {
  'use strict';
  const $ = id => document.getElementById(id);
  const rows = [...document.querySelectorAll('#questionRows .qt-q-row')];
  const normalize = value => String(value ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/đ/gi,'d').toLowerCase();
  const filters = [$('questionSearch'),$('questionSubject'),$('questionDifficulty'),$('questionOwnership'),$('questionSort'),$('questionPageSize')];
  let page = 1;
  function render() {
    const keyword = normalize($('questionSearch').value.trim());
    const filtered = rows.filter(r =>
      (!keyword || normalize(r.dataset.search).includes(keyword)) &&
      (!$('questionSubject').value || r.dataset.subject === $('questionSubject').value) &&
      (!$('questionDifficulty').value || r.dataset.difficulty === $('questionDifficulty').value) &&
      (!$('questionOwnership').value || r.dataset.owner === $('questionOwnership').value)
    );
    filtered.sort((a,b) => {
      if ($('questionSort').value === 'oldest') return Number(a.dataset.id)-Number(b.dataset.id);
      if ($('questionSort').value === 'content') return normalize(a.dataset.content).localeCompare(normalize(b.dataset.content),'vi');
      return Number(b.dataset.id)-Number(a.dataset.id);
    });
    const size = Number($('questionPageSize').value)||10;
    const pages = Math.max(1,Math.ceil(filtered.length/size));
    page = Math.min(Math.max(page,1),pages);
    const start = (page-1)*size;
    rows.forEach(r=>{r.hidden=true; r.style.display='none';});
    filtered.forEach((r,i)=>{const show=i>=start&&i<start+size;r.hidden=!show;r.style.display=show?'':'none';r.parentNode.appendChild(r);});
    $('questionEmpty').hidden=filtered.length!==0;
    $('questionCount').textContent=filtered.length+' câu hỏi';
    $('questionRange').textContent=filtered.length?`Hiển thị ${start+1}–${Math.min(start+size,filtered.length)} / ${filtered.length}`:'Không có câu hỏi';
    const nav=$('questionPagination');nav.replaceChildren();
    function button(label,to,disabled,current=false) {
      const b=document.createElement('button');b.type='button';b.textContent=label;b.disabled=disabled;
      if(current)b.setAttribute('aria-current','page');
      b.addEventListener('click',()=>{page=to;render();});nav.appendChild(b);
    }
    button('‹',page-1,page===1);
    const first=Math.max(1,Math.min(page-2,pages-4));
    for(let i=first;i<=Math.min(pages,first+4);i++)button(String(i),i,false,i===page);
    button('›',page+1,page===pages);
  }
  filters.forEach(el=>el?.addEventListener(el.id==='questionSearch'?'input':'change',()=>{page=1;render();}));
  $('questionRefresh')?.addEventListener('click',()=>window.location.reload());
  render();

  $('questionFormModal')?.addEventListener('show.bs.modal',event=>{
    const d=event.relatedTarget?.dataset||{}, edit=d.mode==='edit';
    $('questionFormAction').value=edit?'edit':'add';
    $('questionFormId').value=edit?(d.id||''):'';
    $('questionFormTitle').textContent=edit?'Chỉnh sửa câu hỏi':'Thêm câu hỏi mới';
    $('questionFormSubmit').textContent=edit?'Lưu thay đổi':'Thêm câu hỏi';
    $('qFormSubject').value=edit?(d.subject||''):'';
    $('qFormContent').value=edit?(d.content||''):'';
    for(const letter of ['A','B','C','D'])$('qFormOption'+letter).value=edit?(d['option'+letter]||''):'';
    $('qFormCorrect').value=edit?(d.correct||'A'):'A';
    $('qFormDifficulty').value=edit?(d.difficulty||'medium'):'medium';
    $('qFormExplanation').value=edit?(d.explanation||''):'';
  });
  $('questionFormModal')?.querySelector('form')?.addEventListener('submit',event=>{
    const correct=$('qFormCorrect').value;
    if (!$('qFormOption'+correct).value.trim()) {
      event.preventDefault();
      $('qFormOption'+correct).setCustomValidity('Đáp án đúng phải có nội dung.');
      $('qFormOption'+correct).reportValidity();
    }
  });
  for(const letter of ['A','B','C','D'])$('qFormOption'+letter)?.addEventListener('input',event=>event.target.setCustomValidity(''));

  $('questionViewModal')?.addEventListener('show.bs.modal',event=>{
    const d=event.relatedTarget?.dataset||{};
    $('qViewId').textContent='#'+(d.id||'');
    $('qViewSubject').textContent=d.subjectName||'Chưa phân loại';
    $('qViewDifficulty').textContent=({easy:'Dễ',medium:'Trung bình',hard:'Khó'})[d.difficulty]||d.difficulty||'';
    $('qViewContent').textContent=d.content||'';
    $('qViewExplanation').textContent=d.explanation||'Chưa có giải thích.';
    const box=$('qViewOptions');box.replaceChildren();
    for(const letter of ['A','B','C','D']) {
      const value=d['option'+letter]||'';
      if(!value.trim())continue;
      const item=document.createElement('div');item.className='qt-q-view-option'+(d.correct===letter?' correct':'');
      const badge=document.createElement('b');badge.textContent=letter;
      const span=document.createElement('span');span.textContent=value;
      item.append(badge,span);
      if(d.correct===letter){const mark=document.createElement('i');mark.className='bi bi-check-circle-fill';item.append(mark);}
      box.append(item);
    }
  });
  $('questionDeleteModal')?.addEventListener('show.bs.modal',event=>{
    const d=event.relatedTarget?.dataset||{};
    $('qDeleteId').value=d.id||'';
    $('qDeleteContent').textContent=d.content||'';
  });
});
