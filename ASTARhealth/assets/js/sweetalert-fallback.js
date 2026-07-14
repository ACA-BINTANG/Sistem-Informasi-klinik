(function () {
  if (window.Swal && typeof window.Swal.fire === 'function') return;

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>'"]/g, function (char) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[char];
    });
  }

  window.Swal = {
    fire: function (options) {
      options = typeof options === 'string' ? { title: options } : (options || {});
      var overlay = document.createElement('div');
      overlay.style.cssText = 'position:fixed;inset:0;background:rgba(15,23,42,.48);display:flex;align-items:center;justify-content:center;padding:20px;z-index:99999';
      overlay.innerHTML = '<div style="width:min(420px,100%);background:#fff;border-radius:18px;padding:28px;text-align:center;box-shadow:0 24px 60px rgba(0,0,0,.25)">' +
        '<h3 style="margin:0 0 12px;font:700 1.35rem Arial,sans-serif;color:#172033">' + escapeHtml(options.title || 'Informasi') + '</h3>' +
        '<p style="margin:0 0 22px;font:400 .96rem/1.55 Arial,sans-serif;color:#596275">' + escapeHtml(options.text || '') + '</p>' +
        '<button type="button" style="border:0;border-radius:10px;background:' + (options.confirmButtonColor || '#175cdd') + ';color:#fff;padding:10px 24px;font-weight:700;cursor:pointer">' + escapeHtml(options.confirmButtonText || 'OK') + '</button>' +
        '</div>';
      document.body.appendChild(overlay);

      return new Promise(function (resolve) {
        overlay.querySelector('button').addEventListener('click', function () {
          overlay.remove();
          resolve({ isConfirmed: true });
        });
      });
    }
  };
})();
