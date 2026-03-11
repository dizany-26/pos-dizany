(function () {
  const STORAGE_KEY = 'dizany-theme';
  const THEME_LIGHT = 'theme-light';
  const THEME_DARK = 'theme-dark';

  function getStoredTheme() {
    const saved = localStorage.getItem(STORAGE_KEY);
    return saved === 'dark' ? THEME_DARK : THEME_LIGHT;
  }

  function applyTheme(theme) {
    const isDark = theme === THEME_DARK;
    document.documentElement.classList.remove(THEME_LIGHT, THEME_DARK);
    document.body.classList.remove(THEME_LIGHT, THEME_DARK);

    document.documentElement.classList.add(theme);
    document.body.classList.add(theme);

    document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', theme);

    localStorage.setItem(STORAGE_KEY, isDark ? 'dark' : 'light');

    const toggle = document.getElementById('themeToggle');
    if (toggle) {
      toggle.setAttribute('aria-checked', String(isDark));
      toggle.setAttribute('title', isDark ? 'Cambiar a tema claro' : 'Cambiar a tema oscuro');
      toggle.setAttribute('aria-label', isDark ? 'Activar tema claro' : 'Activar tema oscuro');
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    applyTheme(getStoredTheme());

    const toggle = document.getElementById('themeToggle');
    if (!toggle) return;

    const toggleTheme = () => {
      const next = document.body.classList.contains(THEME_DARK) ? THEME_LIGHT : THEME_DARK;
      applyTheme(next);
    };

    toggle.addEventListener('click', toggleTheme);
    toggle.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        toggleTheme();
      }
    });
  });
})();
