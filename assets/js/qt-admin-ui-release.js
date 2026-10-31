/* Progressive enhancement: only plain, non-managed tables.
   Never overrides server filters, CRUD events or existing pagination. */
document.addEventListener('DOMContentLoaded',()=>{
 const root=document.querySelector('#adminWrapper main');if(!root)return;
 root.querySelectorAll('table').forEach((table,index)=>{
  if(table.closest('.modal')||table.dataset.qtEnhanced||table.closest('[data-qt-no-enhance]'))return;
  const body=table.tBodies[0];if(!body)return;
  const rows=Array.from(body.rows).filter(r=>r.cells.length>1);
  if(rows.length<6)return;
  const scope=table.closest('.card,.creative-table-card,.table-responsive')?.parentElement||table.parentElement;
  // Skip screens with their own searching, sorting, paging or dynamic tables.
  const nearby=scope?.querySelector('input[type="search"],input[id*="Search"],input[name="q"],[id*="Pagination"],[class*="pagination"],[id*="filter"],[id*="Filter"],[data-bs-toggle="tab"]');
  if(nearby||table.id||table.classList.contains('datatable')||table.classList.contains('dataTable'))return;
  table.dataset.qtEnhanced='1';
  const box=document.createElement('div');box.className='qt-admin-enhancer';
  const input=document.createElement('input');input.type='search';input.className='form-control';input.placeholder='Tìm nhanh trong bảng…';input.setAttribute('aria-label','Tìm nhanh trong bảng');
  const size=document.createElement('select');size.className='form-select';size.setAttribute('aria-label','Số dòng mỗi trang');
  [5,10,20,50].forEach(n=>{const o=new Option(n+' dòng',n);if(n===10)o.selected=true;size.add(o)});
  const refresh=document.createElement('button');refresh.type='button';refresh.className='btn btn-outline-primary';refresh.textContent='↻ Làm mới';
  const status=document.createElement('span');status.className='qt-admin-status';status.setAttribute('aria-live','polite');
  const pages=document.createElement('nav');pages.className='qt-admin-pages';pages.setAttribute('aria-label','Phân trang bảng '+(index+1));
  box.append(input,size,refresh,status);
  const container=table.closest('.table-responsive')||table;
  container.parentElement.insertBefore(box,container);container.insertAdjacentElement('afterend',pages);
  let page=1;
  function render(){
   const term=input.value.trim().toLocaleLowerCase('vi');
   const filtered=rows.filter(r=>r.textContent.toLocaleLowerCase('vi').includes(term));
   const count=Number(size.value),total=Math.max(1,Math.ceil(filtered.length/count));page=Math.min(page,total);
   rows.forEach(r=>r.classList.add('qt-admin-hidden'));
   filtered.slice((page-1)*count,page*count).forEach(r=>r.classList.remove('qt-admin-hidden'));
   status.textContent=`${filtered.length} / ${rows.length} bản ghi`;
   pages.replaceChildren();
   const make=(label,target,disabled=false,active=false)=>{
    const b=document.createElement('button');b.type='button';b.textContent=label;b.disabled=disabled;
    if(active)b.setAttribute('aria-current','page');
    b.addEventListener('click',()=>{page=target;render()});pages.append(b);
   };
   make('‹',page-1,page===1);
   const start=Math.max(1,Math.min(page-2,total-4)),end=Math.min(total,start+4);
   for(let i=start;i<=end;i++)make(String(i),i,false,i===page);
   make('›',page+1,page===total);
  }
  input.addEventListener('input',()=>{page=1;render()});
  size.addEventListener('change',()=>{page=1;render()});
  refresh.addEventListener('click',()=>{input.value='';size.value='10';page=1;render()});
  render();
 });
});
