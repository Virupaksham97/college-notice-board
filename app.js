// Shared helpers for all pages.
const CATEGORIES = ["General", "Academic", "Examination", "Events", "Holiday", "Placement", "Sports"];

function esc(s) {
  return String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
}

async function api(url, method = "GET", body) {
  const res = await fetch(url, {
    method,
    headers: body ? { "Content-Type": "application/json" } : undefined,
    body: body ? JSON.stringify(body) : undefined,
    credentials: "same-origin",
  });
  let data = {};
  try { data = await res.json(); } catch {}
  if (!res.ok) {
    const err = new Error(data.error || "Something went wrong.");
    err.status = res.status;
    throw err;
  }
  return data;
}

function showMsg(el, text, type = "error") {
  el.textContent = text;
  el.className = "msg show " + type;
}

// Require login (and optionally a role); returns the user or redirects.
async function requireUser(roles) {
  try {
    const { user } = await api("/api/auth/me");
    if (roles && !roles.includes(user.role)) { location.href = "dashboard.html"; return null; }
    return user;
  } catch {
    location.href = "index.html";
    return null;
  }
}

function renderNav(user) {
  const nav = document.getElementById("nav");
  if (!nav) return;
  nav.innerHTML =
    '<a href="dashboard.html">Dashboard</a>' +
    (user.role === "admin" ? '<a href="notice_form.html">Post Notice</a>' : "") +
    '<a href="notices.html">View Notices</a>' +
    '<a href="#" id="logout">Logout</a>';
  document.getElementById("logout").addEventListener("click", async (e) => {
    e.preventDefault();
    await api("/api/auth/logout", "POST").catch(() => {});
    location.href = "index.html";
  });
}

function fmtDate(s) { return esc(s); }
