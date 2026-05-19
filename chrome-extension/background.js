/**
 * Service Worker (background) — GovCert
 *
 * ARQUITETURA MV3: este é o ÚNICO ponto que faz requisições HTTP ao Laravel.
 * O content.js nunca chama fetch() — ele apenas extrai dados do DOM e envia
 * uma mensagem para cá via chrome.runtime.sendMessage().
 * Isso evita bloqueio por Mixed Content e CORS nas abas das plataformas de IA.
 */

console.log("[GovCert Background] Service Worker iniciado.");

// ── Instalação ────────────────────────────────────────────────────────────────
chrome.runtime.onInstalled.addListener(({ reason }) => {
  if (reason === 'install') {
    console.log('[GovCert Background] Extensão instalada. Clique no ícone para fazer login.');
  }
});

// ── Listener principal de mensagens ──────────────────────────────────────────
// CRÍTICO: o listener síncrono DEVE retornar `true` antes de sair.
// Isso mantém a MessageChannel aberta enquanto a função async roda em background.
// Sem o `return true`, o Chrome fecha a porta e sendResponse vira no-op.
chrome.runtime.onMessage.addListener((message, _sender, sendResponse) => {
  if (message.action === 'NEW_AUDIT_LOG') {
    console.log('[GovCert Background] Mensagem NEW_AUDIT_LOG recebida.', message.payload);
    sendAuditLog(message.payload, sendResponse);
  }

  return true; // mantém canal assíncrono aberto (mandatório no MV3)
});

// ── Motor de envio HTTP ───────────────────────────────────────────────────────
async function sendAuditLog(payload, sendResponse) {
  console.log('[GovCert Background] Lendo credenciais do storage...');

  const { token, api_url, user_email } = await chrome.storage.local.get({
    token:      '',
    api_url:    '',
    user_email: 'anonymous',
  });

  console.log('[GovCert Background] api_url:', api_url);
  console.log('[GovCert Background] token presente:', !!token);

  if (!token || !api_url) {
    console.warn('[GovCert Background] Credenciais ausentes — faça login na extensão.');
    sendResponse({ success: false, error: 'no_credentials' });
    return;
  }

  const endpoint = `${api_url}/api/audit/logs`;

  const body = {
    user_identifier: user_email,
    input_text:      payload.input_text,
    output_text:     payload.output_text,
    url_source:      payload.url_source,
  };

  console.log(`[GovCert Background] Enviando POST para ${endpoint}...`);

  try {
    const response = await fetch(endpoint, {
      method: 'POST',
      headers: {
        'Accept':        'application/json',
        'Content-Type':  'application/json',
        'Authorization': 'Bearer ' + token,
      },
      body: JSON.stringify(body),
    });

    console.log(`[GovCert Background] Resposta da API: HTTP ${response.status}`);

    if (!response.ok) {
      const errorBody = await response.text();
      throw new Error(`HTTP ${response.status} — ${errorBody}`);
    }

    const timeString = new Date().toLocaleString('pt-BR', { timeZone: 'America/Sao_Paulo' });
    await chrome.storage.local.set({ last_sync_time: timeString });

    console.log(`[GovCert Background] Log aceito. last_sync_time gravado: ${timeString}`);

    sendResponse({ success: true, status: response.status });

  } catch (err) {
    console.error('[GovCert Background] Falha no envio:', err.message);
    sendResponse({ success: false, error: err.message });
  }
}
