/* Smooth-scroll cho anchor trong mục lục (nếu dùng) */
document.addEventListener('click', (e)=>{
  const link = e.target.closest('a[href^="#"]');
  if(!link) return;
  const target = document.querySelector(link.getAttribute('href'));
  if(target){
    e.preventDefault();
    target.scrollIntoView({behavior:'smooth', block:'start'});
  }
});

/* Tabs dạng gạch chân – “Cùng một nhà” */
document.querySelectorAll('.tabs-underline .tab').forEach(btn=>{
  btn.addEventListener('click', ()=>{
    const key = btn.dataset.tab;
    // active tab
    btn.parentElement.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
    btn.classList.add('active');
    // active panel
    document.querySelectorAll('#family .tab-panel').forEach(p=>p.classList.remove('active'));
    const panel = document.getElementById('panel-' + key);
    if(panel) panel.classList.add('active');
  });
});

/* Accordion (mỗi hàng có số, gạch và caret) */
document.querySelectorAll('#family .acc-toggle').forEach(tgl=>{
  tgl.addEventListener('click', ()=>{
    const row = tgl.closest('.acc-row');
    const content = row.querySelector('.acc-content');
    const icon = row.querySelector('.acc-icon');
    const expanded = tgl.getAttribute('aria-expanded') === 'true';
    tgl.setAttribute('aria-expanded', String(!expanded));
    content.classList.toggle('open');
    if(icon) icon.textContent = content.classList.contains('open') ? '▾' : '▸';
  });
});

/* Segmented tabs – “Người dẫn đường” */
document.querySelectorAll('.segmented .seg-btn').forEach(btn=>{
  btn.addEventListener('click', ()=>{
    const key = btn.dataset.seg; // 'bod' | 'exec'
    btn.parentElement.querySelectorAll('.seg-btn').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('#leaders .seg-panel').forEach(p=>p.classList.remove('active'));
    const panel = document.getElementById('panel-' + key);
    if(panel) panel.classList.add('active');
  });
});

/* Timeline scroller – bóng đổ 2 mép khi overflow */
(function(){
  const scroller = document.querySelector('#milestones .tl-scroller');
  if(!scroller) return;
  const shadow = ()=>{
    scroller.style.boxShadow =
      scroller.scrollLeft > 0
        ? 'inset 40px 0 30px -30px rgba(0,0,0,.35), inset -40px 0 30px -30px rgba(0,0,0,.35)'
        : (scroller.scrollWidth > scroller.clientWidth ? 'inset -40px 0 30px -30px rgba(0,0,0,.35)' : 'none');
  };
  shadow();
  scroller.addEventListener('scroll', shadow);
  window.addEventListener('resize', shadow);
})();
