(() => {
  'use strict';
  const $=(p,s)=>p.querySelector(s);
  const make=(tag,className,txt)=>{const el=document.createElement(tag);el.className=className;if(txt!==undefined)el.textContent=txt;return el};
  document.querySelectorAll('.qt-notice').forEach(root=>{
    if(root.dataset.qtReady)return;root.dataset.qtReady='1';
    const trigger=$(root,'.qt-notice-trigger'),panel=$(root,'.qt-notice-panel'),list=$(root,'.qt-notice-list'),badge=$(root,'.qt-notice-badge'),sub=$(root,'.qt-notice-sub'),all=$(root,'.qt-notice-all'),err=$(root,'.qt-notice-error');
    let state={items:[],unread:0},busy=false;
    const error=(msg)=>{err.hidden=!msg;err.textContent=msg||''};
    const request=async(action,id)=>{
      const method=action?'POST':'GET';const opts={method,credentials:'same-origin',headers:{'Accept':'application/json'}};
      if(action){const payload=new URLSearchParams({action});if(id!==undefined)payload.set('id',String(id));opts.body=payload;opts.headers['X-CSRF-Token']=root.dataset.qtCsrf;}
      const res=await fetch(root.dataset.qtApi,opts);
      let data;try{data=await res.json()}catch(_){throw Error(`Máy chủ trả dữ liệu không hợp lệ (HTTP ${res.status}). Kiểm tra đường dẫn API.`)}
      if(!res.ok||!data.ok)throw Error(data.message||`Không thể xử lý (HTTP ${res.status})`);
      return data;
    };
    const paint=()=>{
      badge.hidden=state.unread<1;badge.textContent=state.unread>99?'99+':state.unread;
      sub.textContent=state.unread+' chưa đọc';all.disabled=busy||state.unread===0;
      list.replaceChildren();
      if(!state.items.length){list.append(make('p','qt-notice-empty','Chưa có thông báo.'));return;}
      state.items.forEach(item=>{
        const box=make('article','qt-notice-row'+(Number(item.is_read)?'':' unread'));box.dataset.id=item.id;
        box.append(make('strong','qt-notice-title',item.title));
        const sender=(item.sender_name||'').trim();
        box.append(make('p','qt-notice-sender',sender?'Người gửi: '+sender:'Người gửi: Hệ thống QuizTech'));
        box.append(make('p','qt-notice-body',item.body));
        const actions=make('div','qt-notice-actions');
        if(!Number(item.is_read)){
          const mark=make('button','qt-notice-mark','✓ Đã đọc');mark.type='button';mark.onclick=()=>doAction('mark',item.id);actions.append(mark);
        }
        if(item.action_url){
          const link=make('a','qt-notice-detail','Xem chi tiết');
          try{
            const candidate=new URL(root.dataset.qtRoot+item.action_url,document.baseURI);
            if(candidate.origin===location.origin){link.href=candidate.href;link.addEventListener('click',e=>{
              if(Number(item.is_read))return;
              e.preventDefault();doAction('mark',item.id).then(ok=>{if(ok)location.href=candidate.href});
            });actions.append(link)}
          }catch(_){}
        }
        box.append(actions,make('div','qt-notice-time',item.created_at));
        box.style.cursor=Number(item.is_read)?'default':'pointer';
        box.addEventListener('click',e=>{
          if(e.target.closest('a,button')||Number(item.is_read))return;
          doAction('mark',item.id);
        });
        list.append(box);
      });
    };
    const refresh=async()=>{if(busy)return;busy=true;error('');try{state=await request();paint()}catch(e){error(e.message)}finally{busy=false;all.disabled=state.unread===0}};
    const doAction=async(action,id)=>{
      if(busy)return false;busy=true;error('');all.disabled=true;
      try{state=await request(action,id);paint();return true}
      catch(e){error(e.message);return false}
      finally{busy=false;all.disabled=state.unread===0}
    };
    trigger.onclick=()=>{const open=panel.hidden;panel.hidden=!open;trigger.setAttribute('aria-expanded',String(open));if(open)refresh()};
    all.onclick=()=>doAction('mark_all');
    document.addEventListener('click',e=>{if(!root.contains(e.target)){panel.hidden=true;trigger.setAttribute('aria-expanded','false')}});
    document.addEventListener('keydown',e=>{if(e.key==='Escape'){panel.hidden=true;trigger.setAttribute('aria-expanded','false')}});
    refresh();
  });
})();
