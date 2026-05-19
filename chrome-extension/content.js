/**
 * Content Script — GovCert
 *
 * RESPONSABILIDADE ÚNICA: ler o DOM das plataformas de IA e enviar os dados
 * capturados ao background.js via chrome.runtime.sendMessage.
 *
 * REGRA CRÍTICA (MV3): este arquivo NUNCA faz requisições HTTP.
 * Todo fetch é responsabilidade exclusiva do background.js, para evitar
 * bloqueio por Mixed Content e CORS nas abas HTTPS das plataformas de IA.
 */

(function () {
  'use strict';

  // ── Log de inicialização (primeiro sinal de vida no DevTools da aba) ───────
  console.log('[GovCert Content] Script carregado na URL:', window.location.href);

  // ── Mapa de seletores por plataforma ──────────────────────────────────────
  //
  // AVISO DE MANUTENÇÃO: estes seletores dependem do DOM atual de cada IA.
  // Quando uma plataforma atualizar o layout, este mapa precisa ser revisado.
  // Ver Seção 3 do README.md para o procedimento de atualização.
  const PLATFORM_SELECTORS = {
    // ── OpenAI ────────────────────────────────────────────────────────────────
    'chatgpt.com': {
      sendButton: '[data-testid="send-button"]',
      inputArea:  '#prompt-textarea',
      output:     '[data-message-author-role="assistant"]',
    },
    'chat.openai.com': {
      sendButton: '[data-testid="send-button"]',
      inputArea:  '#prompt-textarea',
      output:     '[data-message-author-role="assistant"]',
    },
    // ── Google ────────────────────────────────────────────────────────────────
    'gemini.google.com': {
      sendButton: 'button[aria-label="Send message"]',
      inputArea:  '.ql-editor, [contenteditable="true"]',
      output:     'model-response .markdown',
    },
    // ── Anthropic ─────────────────────────────────────────────────────────────
    'claude.ai': {
      sendButton: 'button[aria-label="Send Message"]',
      inputArea:  '[contenteditable="true"].ProseMirror',
      output:     '[data-is-streaming="false"] .font-claude-message',
    },
    // ── Microsoft ─────────────────────────────────────────────────────────────
    'copilot.microsoft.com': {
      sendButton: 'button[aria-label="Submit"]',
      inputArea:  'textarea',
      output:     '.ai-message-content',
    },
    // ── DeepSeek ──────────────────────────────────────────────────────────────
    'chat.deepseek.com': {
      sendButton: 'button[aria-label="send message"], div[role="button"].send-button, #send-button',
      inputArea:  'textarea#chat-input, textarea',
      output:     '.ds-markdown, .message-content',
    },
    // ── Perplexity ────────────────────────────────────────────────────────────
    'perplexity.ai': {
      sendButton: 'button[aria-label="Submit"]',
      inputArea:  'textarea[placeholder], textarea',
      output:     '.prose, [class*="answer"] .prose',
    },
    // ── Manus ─────────────────────────────────────────────────────────────────
    'manus.im': {
      sendButton: 'button[type="submit"], button[aria-label*="send" i], button[aria-label*="enviar" i]',
      inputArea:  'textarea',
      output:     '.message-content, .agent-response, [class*="message"][class*="assistant"]',
    },
    // ── Duck.ai (DuckDuckGo) ──────────────────────────────────────────────────
    'duck.ai': {
      sendButton: 'button[type="submit"]',
      inputArea:  'textarea',
      output:     '.chat-message--ai .chat-message__body, [class*="message"][class*="ai"]',
    },
    'duckduckgo.com': {
      sendButton: 'button[type="submit"]',
      inputArea:  'textarea',
      output:     '.chat-message--ai .chat-message__body, [class*="message"][class*="ai"]',
    },
  };

  const host      = location.hostname.replace('www.', '');
  const selectors = PLATFORM_SELECTORS[host];

  if (!selectors) {
    console.log(`[GovCert Content] Plataforma não reconhecida (${host}). Monitoramento não iniciado.`);
    return;
  }

  console.log(`[GovCert Content] Plataforma reconhecida: ${host}. Aguardando interações...`);

  // ── Estado ────────────────────────────────────────────────────────────────
  let pendingInput      = '';   // texto do usuário capturado antes do envio
  let lastOutputText    = '';   // último output registrado (evita duplicatas)
  let waitingForOutput  = false; // flag: já capturei um input e aguardo resposta

  // ── Captura do input ──────────────────────────────────────────────────────
  function captureInput() {
    const el = document.querySelector(selectors.inputArea);
    if (!el) return;

    const text = (el.value || el.innerText || el.textContent || '').trim();
    if (text) {
      pendingInput     = text;
      waitingForOutput = true;
      console.log(`[GovCert Content] Input detectado (${text.length} chars):`, text.slice(0, 80) + (text.length > 80 ? '…' : ''));
    }
  }

  // ── Envio da mensagem ao background ──────────────────────────────────────
  function dispatchToBackground(inputText, outputText) {
    const payload = {
      input_text:  inputText,
      output_text: outputText,
      url_source:  window.location.href,
      timestamp:   new Date().toISOString(),
    };

    console.log('[GovCert Content] Enviando par input/output ao background...');

    chrome.runtime.sendMessage({ action: 'NEW_AUDIT_LOG', payload }, (response) => {
      if (chrome.runtime.lastError) {
        // Service Worker pode estar hibernando; o Chrome o acorda sozinho.
        // Se aparecer aqui de forma persistente, recarregue a extensão.
        console.warn('[GovCert Content] Erro ao contatar background:', chrome.runtime.lastError.message);
        return;
      }
      if (response?.success) {
        console.log('[GovCert Content] Background confirmou: log enviado ao Laravel (202).');
      } else {
        console.warn('[GovCert Content] Background reportou falha:', response);
      }
    });
  }

  // ── MutationObserver: detecta quando a resposta da IA estabilizou ─────────
  const observer = new MutationObserver(() => {
    if (!waitingForOutput || !pendingInput) return;

    const outputEls = document.querySelectorAll(selectors.output);
    if (!outputEls.length) return;

    const lastEl      = outputEls[outputEls.length - 1];
    const currentText = lastEl.innerText?.trim() ?? '';

    // Ignora respostas muito curtas (streaming ainda em andamento)
    if (!currentText || currentText.length < 20) return;
    // Ignora se já processamos este texto
    if (currentText === lastOutputText) return;

    // Aguarda 1,5 s sem mudança para garantir que o streaming terminou
    clearTimeout(window._govCertTimer);
    window._govCertTimer = setTimeout(() => {
      const stable = lastEl.innerText?.trim() ?? '';

      // Verifica que o texto não mudou durante o timeout (streaming concluído)
      if (stable !== currentText || stable === lastOutputText) return;

      console.log(`[GovCert Content] Output estabilizado (${stable.length} chars).`);

      lastOutputText   = stable;
      waitingForOutput = false;

      dispatchToBackground(pendingInput, stable);
      pendingInput = '';
    }, 1500);
  });

  // ── Intercepta o clique no botão de envio ────────────────────────────────
  document.addEventListener('click', (e) => {
    if (e.target.closest(selectors.sendButton)) {
      console.log('[GovCert Content] Botão de envio clicado — capturando input...');
      captureInput();
    }
  }, true);

  // ── Intercepta envio via teclado (Enter sem Shift) ────────────────────────
  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter' || e.shiftKey) return;
    const active = document.activeElement;
    if (active && (active.tagName === 'TEXTAREA' || active.isContentEditable)) {
      console.log('[GovCert Content] Enter detectado — capturando input...');
      captureInput();
    }
  }, true);

  // ── Inicia o observer ─────────────────────────────────────────────────────
  observer.observe(document.body, {
    childList:     true,
    subtree:       true,
    characterData: true,
  });

  console.log(`[GovCert Content] MutationObserver ativo. Monitorando ${host}.`);
})();
