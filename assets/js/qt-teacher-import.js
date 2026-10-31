document.addEventListener('DOMContentLoaded',()=>{
 const input=document.getElementById('qtImportFile');
 const area=document.getElementById('qtDropArea');
 const details=document.getElementById('qtFileDetails');
 const info=document.getElementById('qtFileInfo');
 const selected=document.getElementById('qtSelectedFile');
 const form=document.getElementById('qtImportForm');
 const submit=document.getElementById('qtImportSubmit');
 if(!input||!area)return;
 const update=()=>{
  const file=input.files?.[0];
  details.hidden=!file;
  selected.textContent=file?'Đã chọn: '+file.name:'Chưa chọn file';
  if(file)info.textContent=file.name+' · '+(file.size/1024).toFixed(1)+' KB';
 };
 input.addEventListener('change',update);
 area.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();input.click();}});
 ['dragenter','dragover'].forEach(type=>area.addEventListener(type,e=>{e.preventDefault();area.classList.add('dragging');}));
 ['dragleave','drop'].forEach(type=>area.addEventListener(type,e=>{e.preventDefault();area.classList.remove('dragging');}));
 area.addEventListener('drop',e=>{if(e.dataTransfer?.files?.length){input.files=e.dataTransfer.files;update();}});
 document.getElementById('qtClearFile')?.addEventListener('click',()=>{input.value='';update();});
 form?.addEventListener('submit',e=>{
  const file=input.files?.[0];
  if(!file)return;
  const ext=file.name.split('.').pop().toLowerCase();
  if(!['csv','txt','xlsx','docx'].includes(ext)||file.size>5*1024*1024){
   e.preventDefault();alert('Chỉ chấp nhận CSV, XLSX, DOCX tối đa 5 MB.');return;
  }
  submit.disabled=true;submit.textContent='Đang kiểm tra và import...';
 });
 update();
});
