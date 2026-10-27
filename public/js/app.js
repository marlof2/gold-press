(() => {
 try { Object.keys(localStorage).filter(key=>key.startsWith('gold-press:rascunho:')).forEach(key=>localStorage.removeItem(key)); } catch {}
 const sidebar=document.getElementById('sidebar'), toggle=document.getElementById('menu-toggle'), backdrop=document.getElementById('sidebar-backdrop');
 const closeButton=document.getElementById('sidebar-close');
 function menu(open) {
  sidebar?.classList.toggle('is-open',open); document.body.classList.toggle('menu-open',open);
  toggle?.setAttribute('aria-expanded',String(open)); if(backdrop) backdrop.hidden=!open;
  if(open) closeButton?.focus(); else toggle?.focus();
 }
 toggle?.addEventListener('click',()=>menu(toggle.getAttribute('aria-expanded')!=='true'));
 closeButton?.addEventListener('click',()=>menu(false)); backdrop?.addEventListener('click',()=>menu(false));
 document.addEventListener('keydown',event=>{
  if(toggle?.getAttribute('aria-expanded')!=='true') return;
  if(event.key==='Escape') menu(false);
  if(event.key==='Tab') {
   const items=[...sidebar.querySelectorAll('a,button')].filter(el=>el.offsetParent!==null),first=items[0],last=items.at(-1);
   if(event.shiftKey && document.activeElement===first) { event.preventDefault(); last.focus(); }
   else if(!event.shiftKey && document.activeElement===last) { event.preventDefault(); first.focus(); }
  }
 });
 window.matchMedia('(min-width: 1100px)').addEventListener('change', event=>{if(event.matches && toggle?.getAttribute('aria-expanded')==='true') menu(false);});
 document.querySelectorAll('form[data-confirm]').forEach(form=>form.addEventListener('submit',event=>{if(!window.confirm(form.dataset.confirm)) event.preventDefault();}));
 document.querySelectorAll('[data-password]').forEach(button=>button.addEventListener('click',()=>{
  const input=document.getElementById(button.dataset.password),show=input.type==='password';
  input.type=show?'text':'password'; button.textContent=show?'Ocultar':'Mostrar'; button.setAttribute('aria-label',show?'Ocultar senha':'Mostrar senha');
 }));
})();
