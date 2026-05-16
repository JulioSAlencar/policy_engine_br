/**
 * Content Script — GovCert
 * Monitora inputs e outputs em interfaces de chat de IA.
 *
 * Estratégia: observa mutações no DOM para detectar quando o usuário submete
 * uma mensagem e quando a resposta é renderizada. Usa seletores específicos
 * por plataforma e MutationObserver para capturar dinamicamente.
 */

(function () {
  'use strict';

  // ─── Mapa de seletores por plataforma ────────────────────────────────────
  const PLATFORM_SELECTORS = {
    'chatgpt.com':           { input: '[data-testid="send-button"]', output: '[data-message-author-role="assistant"]' },
    'chat.openai.com':       { input: '[data-testid="send-button"]', output: '[data-message-author-role="assistant"]' },
    'gemini.google.com':     { input: 'button[aria-label="Send message"]', output: 'model-response .markdown' },
    'claude.ai':             { input: 'button[aria-label="Send Message"]', output: '[data-is-streaming="false"] .font-claude-message' },
    'copilot.microsoft.com': { input: 'button[aria-label="Submit"]', output: '.ai-message-content' },
  };

  const host     = location.hostname.replace('www.', '');
  const selectors = PLATFORM_SELECTORS[host];
  if (!selectors) return;

  let lastInput = '';
  let processingCapture = false;

  // ─── Observa envio do formulário capturando o input antes ────────────────
  function captureInput() {
    // Tenta recuperar o texto da área de input comum
    const textarea = document.querySelector('textarea, [contenteditable="true"][role="textbox"], [contenteditable="true"].ProseMirror');
    if (textarea) {
      lastInput = (textarea.value || textarea.innerText || '').trim();
    }
  }

  // ─── Aguarda resposta completa e envia para a API ─────────────────────────
  async function captureAndSend(outputElement) {
    if (processingCapture || !lastInput) return;
    processingCapture = true;

    const outputText = outputElement.innerText.trim();
    if (!outputText) { processingCapture = false; return; }

    const { apiToken, apiUrl, userIdentifier } = await chrome.storage.sync.get({
      apiToken:       '',
      apiUrl:         'http://localhost:8000',
      userIdentifier: 'anonymous',
    });

    if (!apiToken) {
      console.warn('[GovCert] Token de API não configurado. Acesse as opções da extensão.');
      processingCapture = false;
      return;
    }

    const payload = {
      user_identifier: userIdentifier,
      input_text:      lastInput,
      output_text:     outputText,
      url_source:      location.href,
      timestamp:       new Date().toISOString(),
    };

    try {
      const response = await fetch(`${apiUrl}/api/audit/logs`, {
        method:  'POST',
        headers: {
          'Content-Type':  'application/json',
          'Authorization': `Bearer ${apiToken}`,
          'Accept':        'application/json',
        },
        body: JSON.stringify(payload),
      });

      if (response.status === 202) {
        console.log('[GovCert] Log de auditoria enviado com sucesso.');
      } else {
        console.error('[GovCert] Erro ao enviar log:', response.status, await response.text());
      }
    } catch (err) {
      console.error('[GovCert] Falha na comunicação com a API:', err);
    } finally {
      lastInput = '';
      processingCapture = false;
    }
  }

  // ─── Observa mudanças no DOM para detectar resposta concluída ────────────
  let lastObservedText = '';

  const observer = new MutationObserver(() => {
    const outputElements = document.querySelectorAll(selectors.output);
    if (!outputElements.length) return;

    const last = outputElements[outputElements.length - 1];
    const currentText = last.innerText.trim();

    if (currentText && currentText !== lastObservedText && currentText.length > 20) {
      // Aguarda estabilização (a IA terminou de "digitar")
      clearTimeout(window._policyEngineTimer);
      window._policyEngineTimer = setTimeout(() => {
        const stable = last.innerText.trim();
        if (stable === currentText) {
          lastObservedText = stable;
          captureAndSend(last);
        }
      }, 1500);
    }
  });

  // ─── Intercepta o clique no botão de envio ────────────────────────────────
  document.addEventListener('click', (e) => {
    const btn = e.target.closest(selectors.input);
    if (btn) captureInput();
  }, true);

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      const active = document.activeElement;
      if (active && (active.tagName === 'TEXTAREA' || active.isContentEditable)) {
        captureInput();
      }
    }
  }, true);

  // ─── Inicia o observer ────────────────────────────────────────────────────
  observer.observe(document.body, { childList: true, subtree: true, characterData: true });

  console.log(`[GovCert] Monitoramento ativo em: ${host}`);
})();
