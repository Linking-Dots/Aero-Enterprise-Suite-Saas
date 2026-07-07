// Aeon API client — talks to the aero-assistant backend (POST /aeon/message).
// CSRF: Laravel sets an XSRF-TOKEN cookie; echo it back as X-XSRF-TOKEN.

function xsrf() {
  const m = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
  return m ? decodeURIComponent(m[1]) : '';
}

export async function sendAeonMessage({ message, conversationId }) {
  const res = await fetch('/aeon/message', {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-XSRF-TOKEN': xsrf(),
    },
    body: JSON.stringify({
      message,
      conversation_id: conversationId ?? null,
      context: { page: window.location.pathname },
    }),
  });
  if (!res.ok) throw new Error(`Aeon request failed (${res.status})`);
  return res.json(); // { conversation_id, reply: { role, content, blocks } }
}
