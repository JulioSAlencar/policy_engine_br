/**
 * Service Worker (background) — GovCert
 * Responsável por: abrir a página de opções na primeira instalação
 * e exibir notificações de status.
 */

chrome.runtime.onInstalled.addListener((details) => {
  if (details.reason === 'install') {
    chrome.runtime.openOptionsPage();
  }
});

// Escuta mensagens do content script (para notificações opcionais)
chrome.runtime.onMessage.addListener((message) => {
  if (message.type === 'LOG_SENT') {
    // Notificação discreta — pode ser expandida futuramente
    console.log('[GovCert BG] Log enviado:', message.logId);
  }
});
