document.addEventListener('DOMContentLoaded', async () => {
  const { apiToken, apiUrl, userIdentifier } = await chrome.storage.sync.get({
    apiToken:       '',
    apiUrl:         'http://localhost:8000',
    userIdentifier: 'anonymous',
  });

  const dot      = document.getElementById('status-dot');
  const statusTx = document.getElementById('status-text');
  const userId   = document.getElementById('user-id');

  if (apiToken) {
    dot.className      = 'dot active';
    statusTx.textContent = 'Monitoramento ativo';
    userId.textContent   = `Usuário: ${userIdentifier || 'não definido'}`;
  } else {
    dot.className      = 'dot inactive';
    statusTx.textContent = 'Token não configurado';
    userId.textContent   = 'Configure o token para ativar o monitoramento.';
  }

  document.getElementById('btn-dashboard').addEventListener('click', () => {
    chrome.tabs.create({ url: `${apiUrl}/` });
  });

  document.getElementById('btn-options').addEventListener('click', () => {
    chrome.runtime.openOptionsPage();
  });
});
