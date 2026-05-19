document.addEventListener('DOMContentLoaded', async () => {
  const viewLogin     = document.getElementById('view-login');
  const viewConnected = document.getElementById('view-connected');
  const loginError    = document.getElementById('login-error');
  const connectBtn    = document.getElementById('connect-btn');

  // ── Helpers de view ──────────────────────────────────────────────────────
  function showLogin() {
    viewLogin.style.display     = 'block';
    viewConnected.style.display = 'none';
  }

  function showConnected(userName, syncTime) {
    viewLogin.style.display     = 'none';
    viewConnected.style.display = 'block';

    document.getElementById('user-name-display').textContent = userName;
    document.getElementById('last-sync-display').textContent = syncTime
      ? new Date(syncTime).toLocaleString('pt-BR', { timeZone: 'America/Sao_Paulo' })
      : 'Nenhum envio recente';
  }

  function setError(msg) {
    loginError.textContent = msg;
    loginError.classList.add('visible');
  }

  function clearError() {
    loginError.textContent = '';
    loginError.classList.remove('visible');
  }

  // ── Estado inicial: lê storage e decide qual view mostrar ────────────────
  const stored = await chrome.storage.local.get({
    token:          '',
    user_name:      '',
    api_url:        'http://localhost:8000',
    last_sync_time: null,
  });

  if (stored.token) {
    showConnected(stored.user_name, stored.last_sync_time);
  } else {
    document.getElementById('api-url').value = stored.api_url;
    showLogin();
  }

  // ── Botão Conectar ───────────────────────────────────────────────────────
  connectBtn.addEventListener('click', async () => {
    clearError();

    const apiUrl   = document.getElementById('api-url').value.trim().replace(/\/$/, '');
    const email    = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;

    if (!apiUrl || !email || !password) {
      setError('Preencha todos os campos.');
      return;
    }

    connectBtn.disabled    = true;
    connectBtn.textContent = 'Conectando...';

    try {
      const res = await fetch(`${apiUrl}/api/extension/login`, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body:    JSON.stringify({ email, password, device_name: 'chrome-extension' }),
      });

      const data = await res.json();

      if (!res.ok) {
        const msg = data.errors?.email?.[0] ?? data.message ?? 'Erro ao autenticar.';
        setError(msg);
        return;
      }

      await chrome.storage.local.set({
        token:      data.token,
        user_name:  data.user_name,
        user_email: data.user_email,
        api_url:    apiUrl,
      });

      showConnected(data.user_name, null);
    } catch {
      setError('Não foi possível conectar ao servidor. Verifique a URL e tente novamente.');
    } finally {
      connectBtn.disabled    = false;
      connectBtn.textContent = 'Conectar';
    }
  });

  // ── Botão Desconectar ────────────────────────────────────────────────────
  document.getElementById('disconnect-btn').addEventListener('click', async () => {
    const { token, api_url } = await chrome.storage.local.get({ token: '', api_url: '' });

    // Tenta revogar o token no servidor; ignora falha de rede (logout offline)
    try {
      await fetch(`${api_url}/api/extension/logout`, {
        method:  'POST',
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
      });
    } catch { /* sem conexão — limpa o storage mesmo assim */ }

    await chrome.storage.local.remove(['token', 'user_name', 'user_email', 'last_sync_time']);
    showLogin();
  });

  // ── Botão Dashboard ──────────────────────────────────────────────────────
  document.getElementById('btn-dashboard').addEventListener('click', async () => {
    const { api_url } = await chrome.storage.local.get({ api_url: 'http://localhost:8000' });
    chrome.tabs.create({ url: `${api_url}/` });
  });
});
