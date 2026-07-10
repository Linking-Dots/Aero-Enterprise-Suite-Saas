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
  return res.json(); // { conversation_id, reply: { id, role, content, blocks } }
}

// Streaming variant: POSTs to /aeon/message/stream and parses the SSE feed.
// `onStage(label)` fires as the agent loop progresses (thinking / querying /
// opening) so the UI can narrate instead of showing a dead spinner. Resolves
// with the same payload as sendAeonMessage.
export async function sendAeonMessageStream({ message, conversationId, onStage }) {
  const res = await fetch('/aeon/message/stream', {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'text/event-stream',
      'X-Requested-With': 'XMLHttpRequest',
      'X-XSRF-TOKEN': xsrf(),
    },
    body: JSON.stringify({
      message,
      conversation_id: conversationId ?? null,
      context: { page: window.location.pathname },
    }),
  });
  if (!res.ok || !res.body) throw new Error(`Aeon stream failed (${res.status})`);

  const reader = res.body.getReader();
  const decoder = new TextDecoder();
  let buffer = '';
  let done = null;

  const handle = (raw) => {
    const lines = raw.split('\n');
    let event = 'message';
    let data = '';
    lines.forEach((l) => {
      if (l.startsWith('event:')) event = l.slice(6).trim();
      else if (l.startsWith('data:')) data += l.slice(5).trim();
    });
    if (!data) return;
    let payload;
    try { payload = JSON.parse(data); } catch (e) { return; }
    if (event === 'stage' && onStage) onStage(payload.label || '');
    if (event === 'done') done = payload;
    if (event === 'error') throw new Error(payload.message || 'Aeon stream error');
  };

  for (;;) {
    const { value, done: eof } = await reader.read();
    if (eof) break;
    buffer += decoder.decode(value, { stream: true });
    let idx;
    while ((idx = buffer.indexOf('\n\n')) !== -1) {
      const raw = buffer.slice(0, idx);
      buffer = buffer.slice(idx + 2);
      if (raw.trim()) handle(raw);
    }
  }
  if (!done) throw new Error('Aeon stream ended without a reply');
  return done;
}

// Thumbs up/down on an assistant reply. value: 1 | -1 | 0 (clear).
export async function sendAeonFeedback({ messageId, value }) {
  const res = await fetch(`/aeon/messages/${messageId}/feedback`, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-XSRF-TOKEN': xsrf(),
    },
    body: JSON.stringify({ value }),
  });
  return res.ok;
}

// Submit a generative-UI operation form to the app's REAL endpoint. The server
// runs its own validation + permissions (HRMAC) + audit — this is not a bypass.
// Returns { ok, status, errors } where errors is a { field: message } map on 422.
export async function submitAeonForm({ action, method = 'post', values }) {
  const verb = (method || 'post').toUpperCase();
  const body = { ...values };
  // Laravel method spoofing for PUT/PATCH/DELETE over a POST.
  const httpMethod = verb === 'GET' ? 'GET' : 'POST';
  if (['PUT', 'PATCH', 'DELETE'].includes(verb)) body._method = verb;

  let res;
  try {
    res = await fetch(action, {
      method: httpMethod,
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': xsrf(),
      },
      body: JSON.stringify(body),
    });
  } catch (e) {
    return { ok: false, status: 0, errors: { _: 'Network error — please try again.' } };
  }

  if (res.ok) return { ok: true, status: res.status, errors: {} };

  if (res.status === 422) {
    let errors = {};
    try {
      const data = await res.json();
      errors = data.errors || {};
    } catch (e) { /* keep empty */ }
    return { ok: false, status: 422, errors };
  }
  if (res.status === 403) return { ok: false, status: 403, errors: { _: "You don't have permission to do this." } };
  if (res.status === 419) return { ok: false, status: 419, errors: { _: 'Your session expired — reload the page and try again.' } };
  return { ok: false, status: res.status, errors: { _: `Couldn't submit (error ${res.status}).` } };
}
