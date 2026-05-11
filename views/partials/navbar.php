<?php
require_once __DIR__ . '/../../controllers/auth.php';
require_once __DIR__ . '/../../controllers/maintenance.php';
auth_start_session();
$h = $h ?? fn($v) => htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
$activeNav = $activeNav ?? '';
$sessionTimeout = auth_config_timeout();
$lastActivity = $_SESSION['last_activity'] ?? time();
$remaining = max(0, $sessionTimeout - (time() - $lastActivity));
$navMaintenanceMode = function_exists('maintenance_mode_enabled') && maintenance_mode_enabled();
$navMaintenanceUntil = $navMaintenanceMode && function_exists('maintenance_mode_until_text') ? maintenance_mode_until_text() : '';
$routeUrl = static fn(string $page): string => '/redmine/?page=' . rawurlencode($page);
$navItems = [
    ['key' => 'mensajes', 'label' => 'Reportes', 'href' => $routeUrl('dashboard'), 'icon' => 'bi-inboxes', 'can' => true],
    ['key' => 'webhook', 'label' => 'Simular webhook', 'href' => $routeUrl('webhook'), 'icon' => 'bi-broadcast-pin', 'can' => auth_can('simulador')],
    ['key' => 'horas', 'label' => 'Horas extra', 'href' => $routeUrl('horas-extra'), 'icon' => 'bi-clock-history', 'can' => auth_can('horas_extra')],
    ['key' => 'historico', 'label' => 'Historico', 'href' => $routeUrl('historico'), 'icon' => 'bi-archive', 'can' => auth_can('historico')],
    ['key' => 'usuarios', 'label' => 'Usuarios', 'href' => $routeUrl('usuarios'), 'icon' => 'bi-people', 'can' => auth_can('usuarios')],
    ['key' => 'configuracion', 'label' => 'Configuracion', 'href' => $routeUrl('configuracion'), 'icon' => 'bi-sliders', 'can' => auth_can('configuracion') || auth_can('categorias') || auth_can('unidades')],
    ['key' => 'estadisticas', 'label' => 'Estadisticas', 'href' => $routeUrl('estadisticas'), 'icon' => 'bi-bar-chart-line', 'can' => auth_can('estadisticas')],
    ['key' => 'estadisticas_api', 'label' => 'Redmine API', 'href' => $routeUrl('estadisticas-api'), 'icon' => 'bi-cloud-arrow-down', 'can' => auth_can('estadisticas_manual')],
    ['key' => 'security', 'label' => 'Actividad reciente', 'href' => $routeUrl('actividad'), 'icon' => 'bi-activity', 'can' => auth_can('actividad')],
];
?>
<nav class="navbar navbar-expand-xl sb-navbar navbar-dark sticky-top">
  <div class="container-fluid sb-navbar-shell">
    <div class="sb-navbar-top">
      <a class="navbar-brand sb-navbar-brand" href="<?= $h($routeUrl('dashboard')) ?>">
        <span class="sb-brand-mark"><i class="bi bi-grid-1x2-fill"></i></span>
        <span>Panel</span>
      </a>
      <div class="sb-nav-actions d-flex align-items-center gap-2">
        <?php if ($navMaintenanceMode): ?>
          <span class="sb-maintenance-badge d-inline-flex">
            <i class="bi bi-tools"></i>
            <span>Mantenci&oacute;n<?= $navMaintenanceUntil !== '' ? ' hasta ' . $h($navMaintenanceUntil) : ' activa' ?></span>
          </span>
        <?php endif; ?>
        <span class="sb-session-badge badge bg-light text-dark d-inline-flex align-items-center gap-1" id="session-timer" data-remaining="<?= $h($remaining) ?>" data-timeout="<?= $h($sessionTimeout) ?>">
          <i class="bi bi-clock"></i><span id="session-timer-text">--:--</span>
        </span>
        <?php if (!empty($_SESSION['user']['nombre'])): ?>
          <span class="sb-user-pill text-white-50 small d-none d-sm-inline"><i class="bi bi-person-circle"></i> Hola, <strong><?= $h($_SESSION['user']['nombre']) ?></strong></span>
        <?php endif; ?>
        <a class="btn btn-outline-light btn-sm sb-logout-btn" href="/redmine/logout.php"><i class="bi bi-box-arrow-right"></i> <span>Cerrar Sesion</span></a>
        <button class="navbar-toggler sb-navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Abrir navegacion">
          <span class="navbar-toggler-icon"></span>
        </button>
      </div>
    </div>
    <div class="collapse navbar-collapse sb-navbar-menu" id="navMenu">
      <ul class="navbar-nav sb-nav-list me-auto mb-0">
        <?php foreach ($navItems as $item): ?>
          <?php if (!$item['can']) { continue; } ?>
          <?php $isActive = $activeNav === $item['key']; ?>
          <li class="nav-item">
            <a class="nav-link sb-nav-link <?= $isActive ? 'active' : '' ?>" href="<?= $h($item['href']) ?>" <?= $isActive ? 'aria-current="page"' : '' ?>>
              <i class="bi <?= $h($item['icon']) ?>"></i>
              <span><?= $item['label'] ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</nav>
<?php if ($navMaintenanceMode): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('form').forEach((form) => {
    const method = (form.getAttribute('method') || 'get').toLowerCase();
    const actionInput = form.querySelector('[name="action"]');
    const action = actionInput ? actionInput.value : '';
    const allowed = form.closest('#maintenanceModal') && ['maintenance_settings', 'maintenance_export'].includes(action);
    if (method !== 'post' || allowed) return;
    form.querySelectorAll('input, select, textarea, button').forEach((control) => {
      if (control.matches('[data-bs-dismiss="modal"]')) return;
      control.disabled = true;
      control.title = 'Plataforma en mantencion';
    });
  });
});
</script>
<?php endif; ?>
<script>
window.addEventListener('load', () => {
  (function partialNav() {
    const enablePartialNav = true;
    const pageContent = document.getElementById('page-content');
    if (!enablePartialNav || !pageContent || !window.history || !window.fetch) return;
    const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const transitionMs = prefersReducedMotion ? 0 : 60;
    let isNavigating = false;
    const forceFullPaths = [
      'dashboard/dashboard.php',
      'dashboard.php',
      'horasextra/horas_extra.php',
      'horas_extra.php'
    ];
    const navLinks = document.querySelectorAll('.navbar-nav a.nav-link');
    const setActive = (urlStr) => {
      navLinks.forEach(a => {
        if (a.href === urlStr) a.classList.add('active'); else a.classList.remove('active');
      });
    };
    const executeScripts = (doc) => {
      const scripts = doc.querySelectorAll('script');
      scripts.forEach(old => {
        const s = document.createElement('script');
        if (old.src) {
          s.src = old.src;
        } else {
          s.textContent = old.textContent;
        }
        document.body.appendChild(s);
      });
      document.dispatchEvent(new Event('DOMContentLoaded'));
        document.dispatchEvent(new Event('partial:loaded'));
      };
    const wait = (ms) => new Promise(resolve => setTimeout(resolve, ms));
    const startLeave = () => {
      pageContent.classList.add('is-loading', 'is-leaving');
      return wait(transitionMs);
    };
    const startEnter = () => {
      pageContent.classList.remove('is-leaving');
      pageContent.classList.add('is-entering');
      pageContent.offsetHeight;
      requestAnimationFrame(() => {
        pageContent.classList.remove('is-entering', 'is-loading');
      });
    };
    const resetTransitionState = () => {
      pageContent.classList.remove('is-loading', 'is-leaving', 'is-entering');
    };
    const syncFavicon = (doc) => {
      if (!doc) return;
      const incoming = Array.from(doc.querySelectorAll('link[rel~="icon"], link[rel="shortcut icon"]'));
      if (incoming.length === 0) return;
      document.querySelectorAll('link[rel~="icon"], link[rel="shortcut icon"]').forEach(link => link.remove());
      incoming.forEach(link => {
        const clone = document.createElement('link');
        Array.from(link.attributes).forEach(attr => clone.setAttribute(attr.name, attr.value));
        document.head.appendChild(clone);
      });
    };
    const loadPage = async (url, push) => {
      if (isNavigating) return;
      const targetPath = (new URL(url, window.location.href)).pathname.toLowerCase();
      if (forceFullPaths.some(p => targetPath.endsWith(p))) {
        window.location.href = url;
        return;
      }
      isNavigating = true;
      startLeave();
      try {
        const res = await fetch(url, { headers: { 'X-Requested-With': 'partial-nav' } });
        let text = await res.text();
        text = text.replace(/^\uFEFF/, '');
        const doc = new DOMParser().parseFromString(text, 'text/html');
        const newContent = doc.getElementById('page-content');
        if (!newContent || newContent.querySelectorAll('script').length > 0) {
          resetTransitionState();
          window.location.href = url;
          return;
        }
        let contentHtml = (newContent.innerHTML || '').trim();
        contentHtml = contentHtml.replace(/\uFEFF/g, '');
        if (/<!doctype|<html|<head/i.test(contentHtml)) {
          resetTransitionState();
          window.location.href = url;
          return;
        }
        if (transitionMs > 0) await wait(transitionMs);
        pageContent.innerHTML = contentHtml;
        Array.from(pageContent.childNodes).forEach(n => {
          if (n.nodeType === 3 && /^\s*$/.test(n.textContent.replace(/\uFEFF/g, ''))) {
            n.remove();
          }
        });
        if (doc.title) document.title = doc.title;
        syncFavicon(doc);
        if (push) history.pushState({ url }, '', url);
        setActive(url);
        window.scrollTo(0, 0);
        executeScripts(doc);
        startEnter();
      } catch (err) {
        resetTransitionState();
        window.location.href = url;
      } finally {
        setTimeout(() => {
          isNavigating = false;
        }, transitionMs + 20);
      }
    };
    const handleClick = (e) => {
      const a = e.currentTarget;
      if (a.target === '_blank') return;
      const url = new URL(a.href, window.location.href);
      if (url.origin !== window.location.origin) return;
      e.preventDefault();
      loadPage(url.toString(), true);
    };
    navLinks.forEach(a => a.addEventListener('click', handleClick));
    window.addEventListener('popstate', (ev) => {
      const url = ev.state?.url || window.location.href;
      loadPage(url, false);
    });
  })();

  const extendBtn = document.getElementById('btn-extend-session');
  const extendPwd = document.getElementById('session-password');
  const extendMsg = document.getElementById('session-msg');
  const closeBtn = document.getElementById('btn-logout-session');
  const el = document.getElementById('session-timer');
  const textEl = document.getElementById('session-timer-text') || el;
  const baseTimeout = el ? (parseInt(el.getAttribute('data-timeout'), 10) || 300) : 300;
  let remaining = el ? (parseInt(el.getAttribute('data-remaining'), 10) || baseTimeout) : baseTimeout;
  let expiresAt = Date.now() + (remaining * 1000);
  const logoutUrl = '/redmine/logout.php';
  const modalEl = document.getElementById('sessionModal');
  const modal = (window.bootstrap && modalEl) ? new bootstrap.Modal(modalEl) : null;
  const modalTitleEl = modalEl ? modalEl.querySelector('.modal-title') : null;
  const modalBodyTextEl = modalEl ? modalEl.querySelector('.modal-body > p') : null;
  let modalShown = false;
  let sessionExpired = false;

  let tickHandle = null;
  function setTimerAppearance(secondsLeft) {
    if (!el) return;
    if (secondsLeft <= 20) {
      el.className = 'sb-session-badge badge bg-danger text-light d-inline-flex align-items-center gap-1';
    } else if (secondsLeft <= 60) {
      el.className = 'sb-session-badge badge bg-warning text-dark d-inline-flex align-items-center gap-1';
    } else {
      el.className = 'sb-session-badge badge bg-light text-dark d-inline-flex align-items-center gap-1';
    }
  }

  function setModalState(expired) {
    sessionExpired = expired;
    if (modalTitleEl) modalTitleEl.textContent = expired ? 'Sesion expirada' : 'Sesion por expirar';
    if (modalBodyTextEl) {
      modalBodyTextEl.textContent = expired
        ? 'Tu sesion ya expiro. Debes iniciar sesion nuevamente.'
        : 'Tu sesion expira pronto. Deseas continuar?';
    }
    if (extendPwd) extendPwd.disabled = false;
    if (extendBtn) {
      extendBtn.disabled = false;
      extendBtn.textContent = 'Continuar sesion';
    }
    if (closeBtn) {
      closeBtn.textContent = expired ? 'Cancelar' : 'Cerrar sesion';
    }
  }

  function getRemainingSeconds() {
    return Math.max(0, Math.ceil((expiresAt - Date.now()) / 1000));
  }

  function tick() {
    if (!el) return;
    remaining = getRemainingSeconds();
    if (remaining <= 0) {
      textEl.textContent = '00:00';
      setTimerAppearance(0);
      setModalState(true);
      if (modal && !modalShown) {
        modal.show();
        modalShown = true;
        if (extendPwd) setTimeout(() => extendPwd.focus(), 120);
      }
      return;
    }
    if (modal && !modalShown && remaining <= 60) {
      setModalState(false);
      modal.show();
      modalShown = true;
      if (extendPwd) setTimeout(() => extendPwd.focus(), 120);
    }
    const m = Math.floor(remaining / 60).toString().padStart(2, '0');
    const s = (remaining % 60).toString().padStart(2, '0');
    textEl.textContent = `${m}:${s}`;
    setTimerAppearance(remaining);
    tickHandle = setTimeout(tick, 1000);
  }

  function restartTick() {
    if (tickHandle) clearTimeout(tickHandle);
    tick();
  }

  function syncTimerState() {
    remaining = getRemainingSeconds();
    if (remaining <= 0) {
      restartTick();
      return;
    }
    if (modalShown && remaining > 60 && modal) {
      modal.hide();
      modalShown = false;
      setModalState(false);
      if (extendMsg) extendMsg.textContent = '';
    }
    restartTick();
  }

  restartTick();
  document.addEventListener('visibilitychange', syncTimerState);
  window.addEventListener('focus', syncTimerState);

  if (closeBtn) {
    closeBtn.addEventListener('click', () => {
      if (sessionExpired) {
        if (modal) modal.hide();
        modalShown = false;
        return;
      }
      window.location.href = logoutUrl;
    });
  }
  if (extendBtn && extendPwd) {
    extendBtn.addEventListener('click', async () => {
      if (extendMsg) extendMsg.textContent = '';
      const pwd = extendPwd.value.trim();
      if (!pwd) {
        if (extendMsg) extendMsg.textContent = 'Ingresa tu contrasena.';
        return;
      }
      try {
        const resp = await fetch('/redmine/session_extend.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: 'password=' + encodeURIComponent(pwd)
        });
        const data = await resp.json();
        if (data.ok) {
          remaining = parseInt(data.remaining ?? data.timeout ?? baseTimeout, 10) || baseTimeout;
          expiresAt = Date.now() + (remaining * 1000);
          modalShown = false;
          setModalState(false);
          extendPwd.value = '';
          if (extendMsg) extendMsg.textContent = 'Sesion extendida.';
          restartTick();
          if (modal) setTimeout(() => modal.hide(), 400);
        } else {
          if (extendMsg) extendMsg.textContent = data.msg || 'Contrasena incorrecta.';
        }
      } catch (e) {
        if (extendMsg) extendMsg.textContent = 'No se pudo extender la sesion.';
      }
    });
  }
  setModalState(false);
});
</script>

<div class="modal fade" id="sessionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Sesion por expirar</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p>Tu sesion expira pronto. Deseas continuar?</p>
        <div class="mb-3">
          <label class="form-label">Contrasena</label>
          <input type="password" id="session-password" class="form-control" autocomplete="current-password">
          <div class="form-text text-danger" id="session-msg"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" id="btn-logout-session">Cerrar sesion</button>
        <button type="button" class="btn btn-primary" id="btn-extend-session">Continuar sesion</button>
      </div>
    </div>
  </div>
</div>
