(()=>{'use strict';
const root=document.getElementById('adminWrapper');if(!root)return;
function pager(items,anchor,opts={}){
 if(!items.length)return;let page=1,size=10,term='',type='';
 const filter=document.createElement('div');filter.className='qt-unified-filter';
 if(opts.search){const input=document.createElement('input');input.type='search';input.placeholder=opts.placeholder||'Tìm kiếm...';input.setAttribute('aria-label','Tìm kiếm');filter.append(input);input.addEventListener('input',()=>{term=input.value.trim().toLocaleLowerCase('vi');page=1;render()})}
 if(opts.typeSelector){const select=document.createElement('select');select.setAttribute('aria-label','Lọc loại');select.innerHTML='<option value="">Tất cả loại</option>';for(const val of opts.typeSelector){const o=document.createElement('option');o.value=val;o.textContent=val;select.append(o)}filter.append(select);select.addEventListener('change',()=>{type=select.value;page=1;render()})}
 if(opts.search||opts.typeSelector)anchor.parentElement.insertBefore(filter,anchor);
 const nav=document.createElement('div');nav.className='qt-unified-pager';nav.setAttribute('aria-label','Phân trang');
 const info=document.createElement('span');info.setAttribute('aria-live','polite');
 const actions=document.createElement('div');actions.className='qt-pager-actions';
 const label=document.createElement('label');label.textContent='Số dòng ';const sizes=document.createElement('select');sizes.className='form-select form-select-sm';sizes.setAttribute('aria-label','Số dòng mỗi trang');for(const n of [10,25,50,100]){const o=document.createElement('option');o.value=n;o.textContent=n;sizes.append(o)}label.append(sizes);
 const prev=document.createElement('button'),next=document.createElement('button'),current=document.createElement('span');for(const [b,t] of [[prev,'‹ Trước'],[next,'Sau ›']]){b.type='button';b.className='btn btn-sm btn-outline-primary';b.textContent=t}
 actions.append(label,prev,current,next);nav.append(info,actions);anchor.insertAdjacentElement('afterend',nav);
 function render(){const found=items.filter(el=>(!term||el.textContent.toLocaleLowerCase('vi').includes(term))&&(!type||el.textContent.includes(type)));const pages=Math.max(1,Math.ceil(found.length/size));page=Math.min(page,pages);items.forEach(el=>el.hidden=true);found.slice((page-1)*size,page*size).forEach(el=>el.hidden=false);info.textContent=found.length?`Hiển thị ${(page-1)*size+1}–${Math.min(page*size,found.length)} / ${found.length} bản ghi`:'Không có kết quả phù hợp';current.textContent=`${page} / ${pages}`;prev.disabled=page===1;next.disabled=page===pages}
 sizes.addEventListener('change',()=>{size=Number(sizes.value)||10;page=1;render()});prev.addEventListener('click',()=>{page--;render()});next.addEventListener('click',()=>{page++;render()});render();
}
const path=location.pathname;
if(/\/admin\/questions\.php$/.test(path)){
 const table=document.getElementById('aqTable');if(table){const rows=[...table.querySelectorAll('tbody tr[data-id]')];if(rows.length)pager(rows,table.closest('.card')||table,{search:false});}
}
if(/\/admin\/notifications\.php$/.test(path)){
 const history=root.querySelector('.qt-notice-history');if(history){const rows=[...history.querySelectorAll('.qt-notice-item')];if(rows.length)pager(rows,history,{search:true,placeholder:'Tìm tiêu đề, nội dung, người nhận...'});}
}
if(/\/admin\/practice\/index\.php$/.test(path)){
 const table=root.querySelector('.pa-desktop table');if(table){const rows=[...table.querySelectorAll('tbody tr')].filter(el=>el.children.length>1);if(rows.length)pager(rows,table.closest('.pa-desktop'),{search:false});}
}
})();