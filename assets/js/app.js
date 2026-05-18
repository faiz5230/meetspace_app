document.addEventListener('DOMContentLoaded', function(){
  const btn = document.getElementById('themeToggle');
  if(btn){
    btn.addEventListener('click', function(){
      const html = document.documentElement;
      const next = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
      html.setAttribute('data-bs-theme', next);
      document.cookie = "theme=" + next + "; path=/; max-age=31536000";
    });
  }

  async function pollNotifications(){
    try{
      const r = await fetch(BASE_URL_JS + '/api/notifications.php');
      const data = await r.json();
      if(data.items && data.items.length){
        data.items.forEach(n => showToast(n.title, n.message, n.type));
      }
    }catch(e){}
  }
  if(typeof BASE_URL_JS !== 'undefined'){
    setInterval(pollNotifications, 7000);
    pollNotifications();
  }
});

function showToast(title, message, type='info'){
  const area = document.getElementById('liveToastArea');
  if(!area) return;
  const id = 'toast_' + Date.now() + Math.random().toString(16).slice(2);
  const html = `
  <div id="${id}" class="toast align-items-center border-0 mb-2 text-bg-${type}" role="alert">
    <div class="d-flex">
      <div class="toast-body"><strong>${title}</strong><br>${message}</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>`;
  area.insertAdjacentHTML('beforeend', html);
  const el = document.getElementById(id);
  const t = new bootstrap.Toast(el, {delay: 4500});
  t.show();
  el.addEventListener('hidden.bs.toast', () => el.remove());
}
