document.addEventListener('DOMContentLoaded', async () => {
  const { apiUrl, apiToken, userIdentifier } = await chrome.storage.sync.get({
    apiUrl:         'http://localhost:8000',
    apiToken:       '',
    userIdentifier: '',
  });

  document.getElementById('apiUrl').value         = apiUrl;
  document.getElementById('apiToken').value       = apiToken;
  document.getElementById('userIdentifier').value = userIdentifier;

  // Toggle visibilidade do token
  document.getElementById('toggle-token').addEventListener('click', () => {
    const input = document.getElementById('apiToken');
    const toggle = document.getElementById('toggle-token');
    if (input.type === 'password') {
      input.type = 'text';
      toggle.textContent = 'ocultar';
    } else {
      input.type = 'password';
      toggle.textContent = 'mostrar';
    }
  });

  document.getElementById('save-btn').addEventListener('click', async () => {
    const statusEl = document.getElementById('status-msg');
    statusEl.className = 'status';
    statusEl.style.display = 'none';

    const values = {
      apiUrl:         document.getElementById('apiUrl').value.trim().replace(/\/$/, ''),
      apiToken:       document.getElementById('apiToken').value.trim(),
      userIdentifier: document.getElementById('userIdentifier').value.trim(),
    };

    if (!values.apiToken) {
      statusEl.textContent = 'O token de API é obrigatório.';
      statusEl.className = 'status error';
      return;
    }

    await chrome.storage.sync.set(values);

    statusEl.textContent = 'Configurações salvas com sucesso!';
    statusEl.className = 'status success';

    setTimeout(() => { statusEl.style.display = 'none'; }, 3000);
  });
});
