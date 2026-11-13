(() => {
  const sidebar = document.getElementById('sidebar');
  const toggle = document.getElementById('sidebarToggle');
  if (toggle && sidebar) {
    toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
    document.addEventListener('click', (event) => {
      if (window.innerWidth < 992 && sidebar.classList.contains('open') && !sidebar.contains(event.target) && !toggle.contains(event.target)) sidebar.classList.remove('open');
    });
  }
  document.querySelectorAll('form[onsubmit]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      const submitter = event.submitter;
      if (submitter) {
        submitter.disabled = true;
        submitter.dataset.originalText = submitter.innerHTML;
        submitter.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Working';
      }
    });
  });
  document.querySelectorAll('.alert-dismissible').forEach((alert) => {
    window.setTimeout(() => {
      if (window.bootstrap) bootstrap.Alert.getOrCreateInstance(alert).close();
    }, 6500);
  });
})();
