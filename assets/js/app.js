/* ============================================================
   DENYE KWEN v2.0 - API Client + Fonksyon Itilite Global
   ============================================================ */

// Baz URL API a (chanje si dosye a pa nan htdocs/denye-kwen)
const API_BASE = 'api';

// ------------------------------------------------------------
// Rele API ak fetch (async/await)
// ------------------------------------------------------------
async function api(endpoint, method = 'GET', body = null) {
  const opts = {
    method,
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' }
  };
  if (body) opts.body = JSON.stringify(body);

  try {
    const res = await fetch(`${API_BASE}/${endpoint}`, opts);
    const json = await res.json();

    // Sesyon ekspire → retounen nan login
    if (res.status === 401 && !endpoint.includes('auth.php')) {
      sessionStorage.clear();
      localStorage.removeItem('dk_user');
      window.location.href = 'index.html';
      return json;
    }
    return json;
  } catch (err) {
    return { success: false, data: null, message: 'Pa ka kominike ak sèvè a. Verifye XAMPP/Apache ap mache.' };
  }
}

// ------------------------------------------------------------
// Sesyon lokal (sessionStorage/localStorage)
// ------------------------------------------------------------
function saveUser(user, remember = false) {
  const s = JSON.stringify(user);
  sessionStorage.setItem('dk_user', s);
  if (remember) localStorage.setItem('dk_user', s);
}

function getUser() {
  const s = sessionStorage.getItem('dk_user') || localStorage.getItem('dk_user');
  return s ? JSON.parse(s) : null;
}

function clearUser() {
  sessionStorage.removeItem('dk_user');
  localStorage.removeItem('dk_user');
}

// ------------------------------------------------------------
// Pwoteksyon paj: verifye wòl epi redirije si pa gen dwa
// ------------------------------------------------------------
async function requireRole(roles) {
  const user = getUser();
  if (!user) { window.location.href = 'index.html'; return null; }

  // Verifye sesyon PHP a toujou valid
  const check = await api('auth.php?action=check');
  if (!check.success) { clearUser(); window.location.href = 'index.html'; return null; }

  if (!roles.includes(check.data.role)) {
    toast('Ou pa gen aksè nan paj sa a.', 'error');
    setTimeout(() => { window.location.href = dashboardFor(check.data.role); }, 1200);
    return null;
  }
  return check.data;
}

function dashboardFor(role) {
  return { Admin: 'admin.html', Kesye: 'kesye.html', Odite: 'odite.html' }[role] || 'index.html';
}

// ------------------------------------------------------------
// Dekonekte
// ------------------------------------------------------------
async function logout() {
  await api('auth.php?action=logout', 'POST');
  clearUser();
  window.location.href = 'index.html';
}

// ------------------------------------------------------------
// Fòma lajan HTG
// ------------------------------------------------------------
function money(n) {
  return Number(n || 0).toLocaleString('fr-HT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' HTG';
}

function fmtDate(d) {
  if (!d) return '—';
  const dt = new Date(String(d).replace(' ', 'T'));
  return dt.toLocaleDateString('fr-FR') + ' ' + dt.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
}

function fmtDateOnly(d) {
  if (!d) return '—';
  return new Date(String(d).replace(' ', 'T')).toLocaleDateString('fr-FR');
}

// ------------------------------------------------------------
// Toast notifications
// ------------------------------------------------------------
function toast(msg, type = 'info') {
  let container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
  }
  const icons = {
    success: 'fa-circle-check',
    error: 'fa-circle-xmark',
    warning: 'fa-triangle-exclamation',
    info: 'fa-circle-info'
  };
  const el = document.createElement('div');
  el.className = `toast ${type}`;
  el.innerHTML = `<i class="fa-solid ${icons[type] || icons.info}"></i><span>${msg}</span>`;
  container.appendChild(el);
  setTimeout(() => {
    el.classList.add('hide');
    setTimeout(() => el.remove(), 350);
  }, 3500);
}

// ------------------------------------------------------------
// Modal helpers
// ------------------------------------------------------------
function openModal(id) { document.getElementById(id)?.classList.add('show'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('show'); }

// Fèmen modal lè klike sou overlay la
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-overlay')) e.target.classList.remove('show');
});

// ------------------------------------------------------------
// Konfimasyon anvan aksyon danjere
// ------------------------------------------------------------
function confirmAction(msg) {
  return window.confirm(msg || 'Èske ou sèten?');
}

// ------------------------------------------------------------
// Inisyal (avatar) soti nan yon non
// ------------------------------------------------------------
function initials(name) {
  return (name || '?').split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();
}

// ------------------------------------------------------------
// Escape HTML (sekirite XSS bò kliyan)
// ------------------------------------------------------------
function esc(s) {
  const div = document.createElement('div');
  div.textContent = s == null ? '' : String(s);
  return div.innerHTML;
}

// ------------------------------------------------------------
// Topbar + sidebar comportement komen
// ------------------------------------------------------------
function initShell(user) {
  // Avatar ak non
  document.querySelectorAll('[data-user-avatar]').forEach(el => el.textContent = initials(user.non_konple));
  document.querySelectorAll('[data-user-name]').forEach(el => el.textContent = user.non_konple);
  document.querySelectorAll('[data-user-role]').forEach(el => el.textContent = user.role);

  // Dropdown itilizatè
  const chip = document.querySelector('.user-chip');
  if (chip) {
    chip.addEventListener('click', e => {
      e.stopPropagation();
      chip.querySelector('.dropdown')?.classList.toggle('show');
    });
    document.addEventListener('click', () => chip.querySelector('.dropdown')?.classList.remove('show'));
  }

  // Hamburger mobil
  const burger = document.querySelector('.hamburger');
  const sidebar = document.querySelector('.sidebar');
  const backdrop = document.querySelector('.sidebar-backdrop');
  if (burger && sidebar) {
    burger.addEventListener('click', () => {
      sidebar.classList.toggle('open');
      backdrop?.classList.toggle('show');
    });
    backdrop?.addEventListener('click', () => {
      sidebar.classList.remove('open');
      backdrop.classList.remove('show');
    });
  }

  // Bouton dekonekte
  document.querySelectorAll('[data-logout]').forEach(btn =>
    btn.addEventListener('click', e => { e.preventDefault(); logout(); })
  );
}

// ------------------------------------------------------------
// Loading state nan yon bouton
// ------------------------------------------------------------
function btnLoading(btn, loading, textLoading = 'Chajman...') {
  if (!btn) return;
  if (loading) {
    btn.dataset.orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> ${textLoading}`;
  } else {
    btn.disabled = false;
    btn.innerHTML = btn.dataset.orig || btn.innerHTML;
  }
}
