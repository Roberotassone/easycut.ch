(function(){
  // Cookie consent minimal (necessario per analytics/marketing). Qui usiamo solo "consenso base".
  const key = 'cookie_consent_v1';
  const box = document.getElementById('cookieBox');
  if(!box) return;
  const saved = localStorage.getItem(key);
  if(!saved) box.style.display='block';
  document.getElementById('cookieAccept').onclick = function(){
    localStorage.setItem(key,'accepted');
    box.style.display='none';
  };
  document.getElementById('cookieReject').onclick = function(){
    localStorage.setItem(key,'rejected');
    box.style.display='none';
  };
})();
