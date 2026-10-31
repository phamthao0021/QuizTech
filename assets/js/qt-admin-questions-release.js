document.addEventListener('DOMContentLoaded',()=>{
 const table=document.getElementById('aqTable');if(!table)return;
 const rows=Array.from(table.querySelectorAll('tbody tr[data-id]'));
 const get=id=>document.getElementById(id), controls=['aqSearch','aqSubject','aqDifficulty','aqSort','aqSize'];let page=1;
 const norm=v=>String(v||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/đ/g,'d').toLowerCase().trim();
 function render(){const q=norm(get('aqSearch').value),subject=norm(get('aqSubject').value),difficulty=get('aqDifficulty').value,sort=get('aqSort').value,size=Number(get('aqSize').value)||10;
 const found=rows.filter(r=>(!q||norm(r.dataset.content+' '+r.dataset.subject+' '+r.dataset.id).includes(q))&&(!subject||norm(r.dataset.subject)===subject)&&(!difficulty||r.dataset.difficulty===difficulty));
 found.sort((a,b)=>sort==='az'?norm(a.dataset.content).localeCompare(norm(b.dataset.content),'vi'):sort==='old'?Number(a.dataset.id)-Number(b.dataset.id):Number(b.dataset.id)-Number(a.dataset.id));
 const total=Math.max(1,Math.ceil(found.length/size));page=Math.min(page,total);rows.forEach(r=>r.hidden=true);found.slice((page-1)*size,page*size).forEach(r=>{r.hidden=false;table.tBodies[0].appendChild(r)});
 get('aqEmpty').hidden=found.length!==0;get('aqInfo').textContent=found.length?`Hiển thị ${(page-1)*size+1}–${Math.min(page*size,found.length)} / ${found.length} câu hỏi`:'Không có kết quả';const nav=get('aqPages');nav.replaceChildren();
 const btn=(label,p,disabled=false,active=false)=>{const b=document.createElement('button');b.type='button';b.textContent=label;b.disabled=disabled;if(active)b.setAttribute('aria-current','page');b.addEventListener('click',()=>{page=p;render()});nav.appendChild(b)};
 btn('‹',page-1,page===1);for(let i=1;i<=total;i++){if(total>9&&Math.abs(i-page)>2&&i!==1&&i!==total)continue;btn(String(i),i,false,i===page)}btn('›',page+1,page===total);
 }
 controls.forEach(id=>get(id)?.addEventListener(id==='aqSearch'?'input':'change',()=>{page=1;render()}));get('aqReset')?.addEventListener('click',()=>{get('aqSearch').value='';get('aqSubject').value='';get('aqDifficulty').value='';get('aqSort').value='new';get('aqSize').value='10';page=1;render()});render();
});
