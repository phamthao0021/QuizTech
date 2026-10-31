document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('.datatable-component table,.pa-wrap table.pa-table,table[data-qt-mobile="cards"]').forEach(table=>{
    const headings=[...table.querySelectorAll('thead th')].map(th=>th.textContent.trim());
    if(!headings.length)return;
    table.classList.add('qt-responsive-card-table');
    table.querySelectorAll('tbody tr').forEach(tr=>{
      const cells=[...tr.children].filter(c=>c.matches('td,th'));
      if(cells.some(c=>c.hasAttribute('colspan'))||cells.length!==headings.length)return;
      cells.forEach((cell,i)=>{
        const label=headings[i]||'Thao tác';cell.dataset.qtLabel=label;
        if(i===0&&cell.querySelector('input[type=checkbox]'))cell.dataset.qtCheck='1';
        if(/thao tác|tác vụ|action/i.test(label)||cell.querySelectorAll('button,a.btn').length>1)cell.dataset.qtAction='1';
      });
      const title=cells.find(c=>!c.dataset.qtCheck&&!c.dataset.qtAction&&c.textContent.trim().length>5);
      if(title)title.dataset.qtTitle='1';
    });
  });
});
