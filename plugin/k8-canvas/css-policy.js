const BLOCKED_PATTERNS = [
  /[{}]/,
  /@import/i,
  /expression\s*\(/i,
  /javascript\s*:/i,
  /data\s*:/i,
  /url\s*\(/i,
  /-moz-binding/i
];

export function sanitizeDeclarations(value = '') {
  const declarations = String(value)
    .split(';')
    .map((declaration) => declaration.trim())
    .filter(Boolean)
    .filter((declaration) => declaration.includes(':'))
    .filter((declaration) => !BLOCKED_PATTERNS.some((pattern) => pattern.test(declaration)))
    .filter((declaration) => /^--[a-z0-9-_]+\s*:|^[a-z-]+\s*:/i.test(declaration))
    .join('; ');

  return declarations ? `${declarations};` : '';
}

export function compileScopedCss(scope, { baseCss = '', tabletCss = '', mobileCss = '' } = {}) {
  if (!/^k8c-[a-z0-9-]+$/i.test(scope)) return '';

  const base = sanitizeDeclarations(baseCss);
  const tablet = sanitizeDeclarations(tabletCss);
  const mobile = sanitizeDeclarations(mobileCss);
  const rules = [];

  if (base) rules.push(`.${scope}{${base}}`);
  if (tablet) rules.push(`@media (max-width: 1024px){.${scope}{${tablet}}}`);
  if (mobile) rules.push(`@media (max-width: 767px){.${scope}{${mobile}}}`);

  return rules.join('');
}
