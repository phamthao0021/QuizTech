document.addEventListener('DOMContentLoaded', () => {
  const norm = s => String(s ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/đ/gi,'d').toLowerCase();
  document.querySelectorAll('[data-qt-table-toolbar]').forEach(toolbar => {
    const table=toolbar.nextElementSibling;
    if(!table?.matches('table.qt-enhanced-table'))return;
    const body=table.tBodies[0];if(!body)return;
    const all=[...body.rows].filter(r=>r.querySelectorAll('td').length>1 && !r.querySelector('td[colspan]'));
    const heads=[...table.querySelectorAll('thead th')].map(h=>h.textContent.trim());
    all.forEach(r=>[...r.cells].forEach((c,i)=>c.dataset.qtLabel=heads[i]||'Thông tin'));
    const footer=table.nextElementSibling;
    const search=toolbar.querySelector('input[type="search"]');
    const sort=toolbar.querySelector('.qt-shared-sort');
    const filters=[...toolbar.querySelectorAll('.qt-shared-filter')];
    const size=footer?.querySelector('.qt-shared-pagesize');
    const count=footer?.querySelector('.qt-shared-count');
    const nav=footer?.querySelector('.qt-shared-pages');
    let page=1;
    function render(){
      const term=norm(search?.value.trim());
      let filtered=all.filter(r=>{
        if(term && !norm(r.textContent).includes(term))return false;
        return filters.every(f=>{
          const value=f.value;if(!value)return true;
          const type=f.dataset.filter;
          if(type==='status')return (r.dataset.qtStatus||'').toLowerCase()===value;
          if(type==='ownership')return r.dataset.qtOwnership===value;
          if(type==='questions')return value==='has'?Number(r.dataset.qtQuestions)>0:Number(r.dataset.qtQuestions)===0;
          return true;
        });
      });
      if(sort?.value==='az')filtered.sort((a,b)=>norm(a.textContent).localeCompare(norm(b.textContent),'vi'));
      if(sort?.value==='za')filtered.sort((a,b)=>norm(b.textContent).localeCompare(norm(a.textContent),'vi'));
      if(sort?.value==='original')filtered.sort((a,b)=>all.indexOf(a)-all.indexOf(b));
      const n=Number(size?.value)||10,pages=Math.max(1,Math.ceil(filtered.length/n));page=Math.min(page,pages);
      all.forEach(r=>{r.hidden=true;r.style.display='none';});
      filtered.forEach((r,i)=>{const show=i>=(page-1)*n&&i<page*n;r.hidden=!show;r.style.display=show?'':'none';body.appendChild(r)});
      if(count)count.textContent=filtered.length?`Hiển thị ${(page-1)*n+1}–${Math.min(page*n,filtered.length)} / ${filtered.length}`:'Không có dữ liệu phù hợp';
      if(nav){nav.replaceChildren();const btn=(label,p,disabled,active=false)=>{const b=document.createElement('button');b.type='button';b.textContent=label;b.disabled=disabled;if(active)b.setAttribute('aria-current','page');b.addEventListener('click',()=>{page=p;render()});nav.appendChild(b)};
        btn('‹',page-1,page===1);const start=Math.max(1,Math.min(page-2,pages-4));for(let i=start;i<=Math.min(pages,start+4);i++)btn(String(i),i,false,i===page);btn('›',page+1,page===pages);
      }
    }
    search?.addEventListener('input',()=>{page=1;render()});
    sort?.addEventListener('change',()=>{page=1;render()});
    filters.forEach(f=>f.addEventListener('change',()=>{page=1;render()}));
    size?.addEventListener('change',()=>{page=1;render()});
    toolbar.querySelector('.qt-shared-refresh')?.addEventListener('click',()=>{if(search)search.value='';if(sort)sort.value='original';filters.forEach(f=>f.value='');page=1;render()});
    render();
  });
});