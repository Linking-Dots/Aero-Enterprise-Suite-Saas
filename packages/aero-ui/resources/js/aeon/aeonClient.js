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
