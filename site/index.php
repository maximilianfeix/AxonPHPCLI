<?php
/**
 * Template for the GitHub Pages site. Rendered by site/build.php.
 *
 * @var list<array{id: string, label: string, about: string, manifest: ?string, versions: list<string>, tools: list<string>, outputs: array<string, string>}> $presets
 * @var list<array{name: string, label: string, path: string}>                                                                                           $services
 * @var list<array{name: string, type: string, typeLabel: string, packages: list<string>, command: string}>                                              $tools
 * @var array<string, string>                                                                                                                            $toolTypes
 * @var list<array{string, string, string}>                                                                                                              $summary    label, value, note
 * @var string                                                                                                                                           $repository
 * @var string                                                                                                                                           $version
 */
$command = 'vendor/bin/axonphp ci:init github';
$install = 'composer require --dev maxim/axonphp-cli';
$row = 0;
$copyIcon = '<svg class="idle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>'
    .'<svg class="done" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5 9-10"/></svg>';
$mark = '<svg class="mark" viewBox="0 0 128 128" aria-hidden="true"><rect x="14" y="14" width="100" height="100" rx="26" fill="currentColor" opacity=".16"/><g fill="none" stroke="currentColor" stroke-width="12" stroke-linecap="round" stroke-linejoin="round"><path d="M35 88 56 38l21 50"/><path d="M44 70h47"/></g><circle cx="91" cy="70" r="10" class="node"/></svg>';
$icon = static fn (string $paths): string => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$paths.'</svg>';
$features = [
    ['wide', $icon('<path d="M4 6h16M4 12h16M4 18h10"/><circle cx="19" cy="18" r="2"/>'), 'A matrix that follows your constraint', '<code>"php": "^8.2"</code> becomes a test matrix of 8.2, 8.3, 8.4 and 8.5. Narrow the constraint and the matrix narrows with it, on every provider.', 'matrix'],
    ['', $icon('<path d="M12 3l8 4v5c0 5-3.4 8-8 9-4.600-1-8-4-8-9V7z"/><path d="M9 12l2 2 4-4"/>'), 'The tools you already use', count($tools).' tools across tests, static analysis, code style and dependency checks. Installed means it runs.', ''],
    ['', $icon('<path d="M20 12a8 8 0 1 1-2.300-5.700"/><path d="M20 4v5h-5"/>'), 'A pipeline that stays in sync', '<code>ci:check</code> fails when the committed file drifts from the project. <code>ci:update</code> brings it back.', ''],
    ['', $icon('<path d="M4 19V5M4 19h16"/><path d="M8 15l4-5 3 3 5-7"/>'), 'Coverage with a floor', 'A coverage job on the newest PHP version, and a minimum the job has to reach.', ''],
    ['', $icon('<rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>'), 'Careful defaults', 'Dependency caching, least-privilege permissions, cancelled superseded runs and no duplicate runs for pull requests.', ''],
    ['full', $icon('<path d="M4 7h10M4 12h16M4 17h7"/><path d="M17 5l3 2-3 2"/>'), 'Plain YAML that belongs to you', 'No plugin, no runtime, no lock-in. The result is one readable file for your CI service. Nothing is overwritten unless you confirm it, and <code>--dry-run</code> prints the result first.', 'providers'],
];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>AxonPHP CLI: a CI pipeline that fits your PHP project</title>
<meta name="description" content="AxonPHP CLI reads your composer.json and generates a ready-to-run CI pipeline for GitHub Actions, GitLab CI, Bitbucket Pipelines or CircleCI. Zero configuration.">
<meta name="color-scheme" content="dark light">
<meta property="og:title" content="AxonPHP CLI">
<meta property="og:description" content="A CI pipeline that fits your PHP project, generated from composer.json.">
<meta property="og:type" content="website">
<meta property="og:image" content="https://maximilianfeix.github.io/AxonPHPCLI/og.png">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" href="favicon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --bg: #090A17;
  --bg-2: #0E1022;
  --surface: #13152B;
  --raised: #1A1D3A;
  --border: #272B52;
  --border-strong: #3A3F73;
  --text: #EEF0FD;
  --muted: #A6ACD4;
  --primary: #979FFF;
  --primary-ink: #0B0C1F;
  --accent: #5EEAD4;
  --link: #B4BAFF;
  --ring: #C9CDFF;
  --glow: rgb(119 123 255 / 0.32);
  --grid: rgb(151 159 255 / 0.07);
  --grad: linear-gradient(100deg, #A9B0FF 0%, #C4A1FF 45%, #7FE3F0 100%);

  --code-comment: #7F86B3;
  --code-key: #A9B4FF;
  --code-string: #F2C879;
  --ok: #6FE39A;
  --bad: #FF8C8C;
  --warn: #F2C879;

  --sans: "Inter", -apple-system, "Segoe UI", Helvetica, Arial, sans-serif;
  --display: "Space Grotesk", var(--sans);
  --mono: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  --radius: 14px;
  --radius-s: 9px;
  --wrap: 1140px;
}

:root[data-theme="light"] {
  --bg: #FBFBFF;
  --bg-2: #F4F5FC;
  --surface: #FFFFFF;
  --raised: #F4F5FC;
  --border: #DFE2F3;
  --border-strong: #C4C9E8;
  --text: #15172F;
  --muted: #525879;
  --primary: #4F58B8;
  --primary-ink: #FFFFFF;
  --accent: #0F8F7F;
  --link: #434CAE;
  --ring: #4F58B8;
  --glow: rgb(119 123 255 / 0.18);
  --grid: rgb(79 88 184 / 0.07);
  --grad: linear-gradient(100deg, #4F58B8 0%, #8A4FD0 50%, #0E8FA3 100%);
  --code-comment: #666C8F;
  --code-key: #434CAE;
  --code-string: #8A5200;
  --ok: #17803D;
  --bad: #C62F2F;
  --warn: #8A5200;
}

@media (prefers-color-scheme: light) {
  :root:not([data-theme="dark"]) {
    --bg: #FBFBFF;
    --bg-2: #F4F5FC;
    --surface: #FFFFFF;
    --raised: #F4F5FC;
    --border: #DFE2F3;
    --border-strong: #C4C9E8;
    --text: #15172F;
    --muted: #525879;
    --primary: #4F58B8;
    --primary-ink: #FFFFFF;
    --accent: #0F8F7F;
    --link: #434CAE;
    --ring: #4F58B8;
    --glow: rgb(119 123 255 / 0.18);
    --grid: rgb(79 88 184 / 0.07);
    --grad: linear-gradient(100deg, #4F58B8 0%, #8A4FD0 50%, #0E8FA3 100%);
    --code-comment: #666C8F;
    --code-key: #434CAE;
    --code-string: #8A5200;
    --ok: #17803D;
    --bad: #C62F2F;
    --warn: #8A5200;
  }
}

*, *::before, *::after { box-sizing: border-box; }
html { scroll-behavior: smooth; scroll-padding-top: 84px; }
body { margin: 0; background: var(--bg); color: var(--text); font: 400 1.0625rem/1.65 var(--sans); -webkit-font-smoothing: antialiased; overflow-x: hidden; }
img, svg { max-width: 100%; display: block; }
a { color: var(--link); text-underline-offset: 3px; }
:focus-visible { outline: 3px solid var(--ring); outline-offset: 2px; border-radius: 6px; }
h1, h2, h3 { margin: 0; font-family: var(--display); line-height: 1.12; letter-spacing: -0.02em; text-wrap: balance; }
h2 { font-size: clamp(1.75rem, 3.4vw, 2.5rem); font-weight: 600; }
h3 { font-size: 1.125rem; font-weight: 600; letter-spacing: -0.01em; }
p { margin: 0; }
code, pre, kbd { font-family: var(--mono); font-size: 0.875em; }
:not(pre) > code { background: var(--raised); border: 1px solid var(--border); border-radius: 6px; padding: 0.08em 0.4em; white-space: nowrap; }
.wrap { width: min(100% - 32px, var(--wrap)); margin-inline: auto; }
.skip { position: absolute; left: 16px; top: -100px; background: var(--text); color: var(--bg); padding: 10px 16px; border-radius: var(--radius-s); z-index: 100; }
.skip:focus { top: 10px; }
.sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip-path: inset(50%); white-space: nowrap; }
.grad { background: var(--grad); -webkit-background-clip: text; background-clip: text; color: transparent; }

/* Navigation */
.nav { position: sticky; top: 0; z-index: 50; background: color-mix(in srgb, var(--bg) 78%, transparent); backdrop-filter: blur(14px) saturate(160%); -webkit-backdrop-filter: blur(14px) saturate(160%); border-bottom: 1px solid color-mix(in srgb, var(--border) 70%, transparent); }
.nav .wrap { display: flex; align-items: center; gap: 20px; min-height: 64px; }
.brand { display: flex; align-items: center; gap: 10px; color: var(--text); text-decoration: none; font: 600 1.0625rem var(--display); }
.mark { width: 34px; height: 34px; color: var(--primary); }
.mark .node { fill: var(--accent); }
.tag { font: 500 0.75rem var(--mono); color: var(--muted); border: 1px solid var(--border); border-radius: 999px; padding: 2px 9px; text-decoration: none; }
.tag:hover { color: var(--text); border-color: var(--border-strong); }
.nav nav { margin-left: auto; }
.nav ul { display: flex; gap: 2px; list-style: none; margin: 0; padding: 0; }
.nav nav a { display: block; padding: 8px 12px; border-radius: 999px; color: var(--muted); text-decoration: none; font-size: 0.9375rem; font-weight: 500; transition: color 150ms, background-color 150ms; }
.nav nav a:hover, .nav nav a[aria-current="true"] { color: var(--text); background: var(--raised); }
.actions { display: flex; gap: 2px; align-items: center; }
.icon-btn { display: inline-grid; place-items: center; width: 42px; height: 42px; border-radius: 999px; border: 0; background: transparent; color: var(--muted); cursor: pointer; transition: color 150ms, background-color 150ms; }
.icon-btn:hover { color: var(--text); background: var(--raised); }
.icon-btn svg { width: 20px; height: 20px; }
.icon-btn .sun, :root[data-theme="light"] .icon-btn .moon { display: none; }
:root[data-theme="light"] .icon-btn .sun { display: block; }
@media (prefers-color-scheme: light) {
  :root:not([data-theme="dark"]) .icon-btn .moon { display: none; }
  :root:not([data-theme="dark"]) .icon-btn .sun { display: block; }
}
@media (max-width: 900px) {
  .nav .wrap { flex-wrap: wrap; gap: 0 12px; }
  .nav .actions { margin-left: auto; }
  .nav nav { order: 3; width: 100%; margin: 0 -8px; overflow-x: auto; scrollbar-width: none; }
  .nav nav::-webkit-scrollbar { display: none; }
  .nav ul { width: max-content; padding-bottom: 8px; }
  html { scroll-padding-top: 124px; }
}

/* Hero */
.hero { position: relative; isolation: isolate; text-align: center; padding: 84px 0 0; }
.hero::before { content: ""; position: absolute; inset: 0; z-index: -2; background-image: linear-gradient(var(--grid) 1px, transparent 1px), linear-gradient(90deg, var(--grid) 1px, transparent 1px); background-size: 44px 44px; mask-image: radial-gradient(ellipse 70% 62% at 50% 28%, #000 30%, transparent 78%); -webkit-mask-image: radial-gradient(ellipse 70% 62% at 50% 28%, #000 30%, transparent 78%); }
.hero::after { content: ""; position: absolute; left: 50%; top: -180px; z-index: -1; width: min(980px, 130vw); height: 620px; transform: translateX(-50%); background: radial-gradient(closest-side, var(--glow), transparent 72%); pointer-events: none; }
.pill { display: inline-flex; align-items: center; gap: 10px; padding: 5px 14px 5px 6px; border: 1px solid var(--border); border-radius: 999px; background: color-mix(in srgb, var(--surface) 80%, transparent); color: var(--muted); font-size: 0.875rem; text-decoration: none; transition: border-color 150ms, color 150ms; }
.pill:hover { border-color: var(--border-strong); color: var(--text); }
.pill b { font: 600 0.75rem var(--mono); color: var(--primary-ink); background: var(--primary); border-radius: 999px; padding: 2px 9px; }
.hero h1 { font-size: clamp(2.5rem, 6.8vw, 4.75rem); font-weight: 700; letter-spacing: -0.035em; margin: 26px auto 0; max-width: 15ch; }
.lead { font-size: clamp(1.0625rem, 1.6vw, 1.25rem); color: var(--muted); margin: 22px auto 0; max-width: 40em; }
.cta { display: flex; flex-wrap: wrap; justify-content: center; gap: 12px; margin-top: 34px; }
.btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 48px; padding: 0 22px; border-radius: 999px; font: 600 1rem var(--sans); text-decoration: none; border: 1px solid transparent; cursor: pointer; transition: transform 150ms, background-color 150ms, border-color 150ms, box-shadow 150ms; }
.btn svg { width: 18px; height: 18px; }
.btn-solid { background: var(--primary); color: var(--primary-ink); box-shadow: 0 8px 30px -8px var(--glow); }
.btn-solid:hover { transform: translateY(-1px); box-shadow: 0 12px 36px -8px var(--glow); }
.btn-line { color: var(--text); border-color: var(--border-strong); background: color-mix(in srgb, var(--surface) 70%, transparent); }
.btn-line:hover { border-color: var(--primary); }
.cmd { display: inline-flex; align-items: center; gap: 4px; max-width: 100%; margin-top: 18px; padding: 4px 4px 4px 18px; border: 1px solid var(--border); border-radius: 999px; background: var(--surface); font: 400 0.9375rem var(--mono); color: var(--text); }
.cmd span { overflow-x: auto; white-space: nowrap; scrollbar-width: none; }
.cmd i { font-style: normal; color: var(--accent); margin-right: 10px; user-select: none; }
.copy { flex: none; width: 40px; height: 40px; display: grid; place-items: center; border: 0; border-radius: 999px; background: transparent; color: var(--muted); cursor: pointer; transition: background-color 150ms, color 150ms; }
.copy:hover { color: var(--text); background: var(--raised); }
.copy svg { width: 17px; height: 17px; }
.copy .done, .copy.is-done .idle { display: none; }
.copy.is-done .done { display: block; color: var(--ok); }
.services { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px 10px; margin: 30px 0 0; padding: 0; list-style: none; color: var(--muted); font-size: 0.875rem; }
.services li { display: flex; align-items: center; gap: 8px; padding: 4px 12px; border: 1px solid var(--border); border-radius: 999px; }
.services li::before { content: ""; width: 7px; height: 7px; border-radius: 50%; background: var(--accent); }

/* Windows: terminal and code panes */
.window { position: relative; text-align: left; border: 1px solid var(--border); border-radius: var(--radius); background: var(--bg-2); box-shadow: 0 30px 80px -30px rgb(0 0 0 / 0.55), 0 0 0 1px rgb(255 255 255 / 0.02) inset; min-width: 0; }
.window-bar { display: flex; align-items: center; gap: 7px; padding: 11px 14px; border-bottom: 1px solid var(--border); font: 400 0.8125rem var(--mono); color: var(--muted); }
.window-bar i { width: 11px; height: 11px; border-radius: 50%; background: var(--border-strong); }
.window-bar span { margin-left: 8px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.window pre { margin: 0; padding: 16px 18px 18px; overflow: auto; font-size: 0.8125rem; line-height: 1.7; tab-size: 2; }
.window pre:focus-visible { outline-offset: -3px; }
.window .copy { position: absolute; top: 2px; right: 4px; }
.p, .ok { color: var(--ok); }
.ok { font-weight: 700; }
.t { color: var(--warn); }
.l, .c { color: var(--code-comment); }
.k { color: var(--code-key); }
.s { color: var(--code-string); }
.add { color: var(--ok); }
.del { color: var(--bad); }
.hero .window { margin: 56px auto 0; max-width: 860px; }
.hero .window::before { content: ""; position: absolute; inset: -1px; z-index: -1; border-radius: inherit; padding: 1px; background: var(--grad); opacity: 0.55; mask: linear-gradient(#000 0 0) content-box exclude, linear-gradient(#000 0 0); -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0); -webkit-mask-composite: xor; }
.hero .window pre { font-size: 0.875rem; }

/* Numbers */
.stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0; margin: 64px auto 0; padding: 0; list-style: none; border-block: 1px solid var(--border); }
.stats li { padding: 26px 12px; text-align: center; }
.stats li + li { border-left: 1px solid var(--border); }
.stats b { display: block; font: 700 clamp(1.75rem, 3.6vw, 2.5rem)/1 var(--display); letter-spacing: -0.03em; }
.stats span { display: block; margin-top: 8px; color: var(--muted); font-size: 0.9375rem; }
@media (max-width: 720px) {
  .stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .stats li:nth-child(3) { border-left: 0; }
  .stats li:nth-child(n+3) { border-top: 1px solid var(--border); }
}

/* Sections */
section { padding-block: 96px; }
section.band { background: var(--bg-2); border-block: 1px solid var(--border); }
.head { margin: 0 auto 44px; max-width: 44em; text-align: center; }
.eyebrow { display: block; margin-bottom: 14px; font: 500 0.8125rem var(--mono); letter-spacing: 0.08em; text-transform: uppercase; color: var(--accent); }
.head p { color: var(--muted); margin-top: 16px; font-size: 1.0625rem; }
@media (max-width: 640px) { section { padding-block: 64px; } }

/* How it works */
.flow { display: grid; grid-template-columns: minmax(0, 1fr) 56px minmax(0, 1fr) 56px minmax(0, 1fr); align-items: stretch; }
.stage { display: flex; flex-direction: column; gap: 14px; min-width: 0; }
.stage h3 { display: flex; align-items: center; gap: 10px; }
.stage h3 span { display: inline-grid; place-items: center; width: 30px; height: 30px; border-radius: 9px; background: var(--raised); border: 1px solid var(--border); font: 500 0.8125rem var(--mono); color: var(--primary); }
.stage > p { color: var(--muted); font-size: 0.9375rem; }
.card { flex: 1; border: 1px solid var(--border); border-radius: var(--radius); background: var(--surface); min-width: 0; }
.card pre { margin: 0; padding: 16px 18px; overflow-x: auto; font-size: 0.8125rem; line-height: 1.65; }
.card dl { margin: 0; padding: 16px 18px; display: grid; gap: 14px; }
.card dt { font: 500 0.75rem var(--mono); letter-spacing: 0.06em; text-transform: uppercase; color: var(--muted); }
.card dd { margin: 4px 0 0; display: flex; flex-wrap: wrap; gap: 6px; }
.chip { display: inline-block; padding: 2px 10px; border-radius: 999px; background: var(--raised); border: 1px solid var(--border); font: 500 0.8125rem var(--mono); }
.card ul { list-style: none; margin: 0; padding: 8px; display: grid; gap: 2px; }
.card li { padding: 9px 10px; border-radius: var(--radius-s); }
.card li b { display: block; font-weight: 600; font-size: 0.9375rem; }
.card li code { background: none; border: 0; padding: 0; color: var(--muted); white-space: normal; overflow-wrap: anywhere; }
.wire { align-self: center; position: relative; height: 2px; margin: 0 6px; background: linear-gradient(90deg, var(--border), var(--primary)); }
.wire::after { content: ""; position: absolute; right: -5px; top: -4px; width: 10px; height: 10px; border-radius: 50%; background: var(--accent); box-shadow: 0 0 0 4px color-mix(in srgb, var(--accent) 22%, transparent); }
.wire i { position: absolute; left: 0; top: -3px; width: 8px; height: 8px; border-radius: 50%; background: var(--accent); opacity: 0; }
@media (max-width: 900px) {
  .flow { grid-template-columns: minmax(0, 1fr); }
  .wire { width: 2px; height: 40px; margin: 14px 0 14px 14px; background: linear-gradient(180deg, var(--border), var(--primary)); }
  .wire::after { right: auto; left: -4px; top: auto; bottom: -5px; }
  .wire i { left: -3px; top: 0; }
}

/* Tabs and segmented controls */
.seg { display: inline-flex; flex-wrap: wrap; gap: 4px; padding: 4px; border: 1px solid var(--border); border-radius: 999px; background: var(--surface); }
.seg button { min-height: 38px; padding: 0 16px; border: 0; border-radius: 999px; background: none; color: var(--muted); font: 500 0.9375rem var(--sans); cursor: pointer; transition: color 150ms, background-color 150ms; }
.seg button:hover { color: var(--text); }
.seg button[aria-selected="true"], .seg button[aria-pressed="true"] { background: var(--primary); color: var(--primary-ink); font-weight: 600; }
@media (max-width: 560px) { .seg { border-radius: var(--radius); } }

/* Playground */
.play-top { display: flex; flex-direction: column; align-items: center; gap: 14px; margin-bottom: 26px; text-align: center; }
.about { color: var(--muted); min-height: 1.65em; }
.play { display: grid; grid-template-columns: minmax(0, 0.8fr) minmax(0, 1.2fr); gap: 20px; align-items: start; }
.play .window pre { max-height: 520px; }
.play .seg { margin: 10px 10px 0; border-radius: var(--radius-s); background: var(--bg); }
.play .seg button { border-radius: 7px; min-height: 34px; padding: 0 12px; font-size: 0.875rem; }
.derived { display: flex; flex-wrap: wrap; gap: 6px; padding: 14px 18px 16px; border-top: 1px solid var(--border); }
.derived span { color: var(--muted); font-size: 0.8125rem; width: 100%; }
.empty { padding: 28px 18px; color: var(--muted); }
@media (max-width: 900px) { .play { grid-template-columns: minmax(0, 1fr); } }

/* Features */
.bento { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
.tile { position: relative; overflow: hidden; display: flex; flex-direction: column; gap: 10px; padding: 26px; border: 1px solid var(--border); border-radius: var(--radius); background: var(--surface); transition: border-color 200ms, transform 200ms; }
.tile:hover { border-color: var(--border-strong); transform: translateY(-2px); }
.tile.wide { grid-column: span 2; }
.tile.full { grid-column: 1 / -1; }
.tile > svg { width: 38px; height: 38px; padding: 8px; border-radius: 10px; background: var(--raised); border: 1px solid var(--border); color: var(--primary); }
.tile p { color: var(--muted); font-size: 0.9375rem; }
.tile .extra { display: flex; flex-wrap: wrap; gap: 6px; margin-top: auto; padding-top: 12px; }
.tile .arrow { color: var(--muted); font-family: var(--mono); align-self: center; }
@media (max-width: 900px) { .bento { grid-template-columns: minmax(0, 1fr); } .tile.wide, .tile.full { grid-column: auto; } }

/* Sync */
.sync { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 20px; }
@media (max-width: 900px) { .sync { grid-template-columns: minmax(0, 1fr); } }

/* Detection table */
.filters { display: flex; justify-content: center; margin-bottom: 22px; }
.table-wrap { overflow-x: auto; border: 1px solid var(--border); border-radius: var(--radius); background: var(--surface); }
table { border-collapse: collapse; width: 100%; min-width: 720px; font-size: 0.9375rem; }
th, td { text-align: left; padding: 12px 18px; border-bottom: 1px solid var(--border); vertical-align: top; }
thead th { font: 500 0.75rem var(--mono); letter-spacing: 0.06em; text-transform: uppercase; color: var(--muted); background: var(--raised); }
tbody tr:last-child > * { border-bottom: 0; }
tbody tr { transition: background-color 120ms; }
tbody tr:hover { background: var(--raised); }
tbody th { font-weight: 600; white-space: nowrap; }
td code { white-space: nowrap; background: none; border: 0; padding: 0; }
td.type { white-space: nowrap; }
td.type span { font: 500 0.75rem var(--mono); padding: 2px 9px; border-radius: 999px; border: 1px solid var(--border); color: var(--muted); }

/* Commands */
.commands { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 16px; }
.commands > div { grid-column: span 2; min-width: 0; display: flex; flex-direction: column; gap: 10px; padding: 22px; border: 1px solid var(--border); border-radius: var(--radius); background: var(--surface); }
.commands > div:nth-child(n+4) { grid-column: span 3; }
.commands h3 code { font-size: 1.0625rem; background: none; border: 0; padding: 0; color: var(--primary); }
.commands p { color: var(--muted); font-size: 0.9375rem; }
.commands pre { margin: auto 0 0; padding: 12px 14px; overflow-x: auto; border-radius: var(--radius-s); background: var(--bg-2); border: 1px solid var(--border); font-size: 0.8125rem; line-height: 1.65; }
@media (max-width: 900px) { .commands { grid-template-columns: minmax(0, 1fr); } .commands > div, .commands > div:nth-child(n+4) { grid-column: auto; } }

/* Config */
.split { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 56px; align-items: center; }
.split .head { margin: 0; text-align: left; }
.split ul { margin: 20px 0 0; padding: 0; list-style: none; display: grid; gap: 10px; color: var(--muted); }
.split li { padding-left: 26px; position: relative; }
.split li::before { content: ""; position: absolute; left: 4px; top: 0.62em; width: 8px; height: 8px; border-radius: 50%; background: var(--accent); }
@media (max-width: 900px) { .split { grid-template-columns: minmax(0, 1fr); gap: 28px; } }

/* Install */
.steps { margin: 0 auto; padding: 0; list-style: none; display: grid; gap: 14px; max-width: 780px; counter-reset: step; }
.steps li { counter-increment: step; display: grid; grid-template-columns: 40px minmax(0, 1fr); gap: 4px 14px; align-items: center; }
.steps li::before { content: counter(step); grid-row: span 2; align-self: start; display: grid; place-items: center; width: 40px; height: 40px; border-radius: 12px; background: var(--raised); border: 1px solid var(--border); font: 600 1rem var(--display); color: var(--primary); }
.steps h3 { font-size: 1rem; }
.steps .cmd { margin: 4px 0 0; width: 100%; border-radius: var(--radius-s); justify-content: space-between; }
.steps .copy { border-radius: 7px; }
.note { color: var(--muted); margin: 22px auto 0; max-width: 780px; font-size: 0.9375rem; text-align: center; }

.final { text-align: center; position: relative; isolation: isolate; overflow: hidden; }
.final::before { content: ""; position: absolute; left: 50%; bottom: -320px; z-index: -1; width: min(900px, 130vw); height: 560px; transform: translateX(-50%); background: radial-gradient(closest-side, var(--glow), transparent 72%); }
.final h2 { font-size: clamp(2rem, 4.6vw, 3.25rem); max-width: 18ch; margin-inline: auto; }

footer { padding: 30px 0 44px; color: var(--muted); font-size: 0.9375rem; border-top: 1px solid var(--border); }
footer .wrap { display: flex; flex-wrap: wrap; gap: 10px 24px; justify-content: space-between; align-items: center; }
footer ul { display: flex; flex-wrap: wrap; gap: 4px 20px; list-style: none; margin: 0; padding: 0; }
footer a { color: var(--muted); text-decoration: none; }
footer a:hover { color: var(--text); text-decoration: underline; }

/*
 * Motion. Each animation runs once and ends within a few seconds, and none of it
 * applies under prefers-reduced-motion. Content is only hidden for the entrance
 * when scripting is on, so the page is complete without JavaScript.
 */
@media (prefers-reduced-motion: no-preference) {
  .hero .type { display: inline-block; vertical-align: bottom; overflow: hidden; white-space: pre; width: 0; animation: type 1s 0.5s forwards; }
  .hero .out { opacity: 0; animation: print 160ms ease-out forwards; animation-delay: calc(1.8s + var(--i) * 70ms); }
  @keyframes type { to { width: var(--n); } }
  @keyframes print { to { opacity: 1; } }

  .js [data-reveal], .js [data-reveal-children] > * { opacity: 0; transform: translateY(16px); }
  .js .in[data-reveal], .js [data-reveal-children] > .in { opacity: 1; transform: none; transition: opacity 520ms ease-out, transform 520ms cubic-bezier(0.2, 0.7, 0.2, 1), border-color 200ms; transition-delay: var(--d, 0ms); }

  .flow.in .wire i { animation: signal 0.7s ease-in-out 0.5s both; }
  .flow.in .wire ~ .wire i { animation-delay: 1.2s; }
  .flow.in .stage:last-child .card { animation: arrive 0.9s ease-out 1.9s; }
  @keyframes signal { 0% { left: 0; opacity: 0; } 20%, 80% { opacity: 1; } 100% { left: calc(100% - 4px); opacity: 0; } }
  @keyframes arrive { 0% { box-shadow: 0 0 0 0 var(--accent); border-color: var(--accent); } 100% { box-shadow: 0 0 0 8px transparent; } }

  .pane:not([hidden]) { animation: print 220ms ease-out; }
}
@media (prefers-reduced-motion: no-preference) and (max-width: 900px) {
  .flow.in .wire i { animation-name: signal-down; }
  @keyframes signal-down { 0% { top: 0; opacity: 0; } 20%, 80% { opacity: 1; } 100% { top: calc(100% - 4px); opacity: 0; } }
}
@media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } .btn, .tile { transition: none; } }
</style>
<script>
// Applied before first paint so a stored theme does not flash.
try {
  var stored = localStorage.getItem('axonphp-theme');
  if (stored === 'light' || stored === 'dark') document.documentElement.dataset.theme = stored;
} catch (e) {}
document.documentElement.classList.add('js');
</script>
</head>
<body>
<a class="skip" href="#main">Skip to content</a>

<header class="nav">
  <div class="wrap">
    <a class="brand" href="#top"><?= $mark ?><span>AxonPHP CLI</span></a>
    <a class="tag" href="<?= e($repository) ?>/releases/tag/v<?= e($version) ?>" aria-label="Release notes for version <?= e($version) ?>">v<?= e($version) ?></a>
    <nav aria-label="Sections">
      <ul>
        <li><a href="#how">How it works</a></li>
        <li><a href="#playground">Playground</a></li>
        <li><a href="#features">Features</a></li>
        <li><a href="#detection">Tools</a></li>
        <li><a href="#commands">Commands</a></li>
        <li><a href="#install">Install</a></li>
      </ul>
    </nav>
    <div class="actions">
      <button class="icon-btn" type="button" id="theme" aria-label="Switch between light and dark theme">
        <svg class="sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.900 4.900l1.400 1.400M17.700 17.700l1.400 1.400M2 12h2M20 12h2M4.900 19.100l1.400-1.400M17.700 6.300l1.400-1.400"/></svg>
        <svg class="moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.800A9 9 0 1 1 11.200 3a7 7 0 0 0 9.800 9.800z"/></svg>
      </button>
      <a class="icon-btn" href="<?= e($repository) ?>" aria-label="AxonPHP CLI on GitHub">
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 .5a11.500 11.500 0 0 0-3.640 22.410c.580.100.790-.250.790-.560v-2c-3.200.700-3.880-1.360-3.880-1.360-.520-1.330-1.280-1.690-1.280-1.690-1.050-.710.080-.700.080-.700 1.160.080 1.770 1.190 1.770 1.190 1.030 1.770 2.700 1.260 3.360.960.100-.750.400-1.260.730-1.550-2.550-.290-5.240-1.280-5.240-5.690 0-1.260.450-2.290 1.190-3.090-.120-.290-.520-1.460.110-3.050 0 0 .970-.310 3.180 1.180a11 11 0 0 1 5.780 0c2.200-1.490 3.170-1.180 3.170-1.180.630 1.590.230 2.760.110 3.050.740.800 1.190 1.830 1.190 3.090 0 4.420-2.700 5.400-5.260 5.680.410.360.780 1.060.780 2.140v3.170c0 .310.210.670.800.560A11.500 11.500 0 0 0 12 .5z"/></svg>
      </a>
    </div>
  </div>
</header>

<main id="main">
  <div class="hero" id="top">
    <div class="wrap">
      <a class="pill" href="<?= e($repository) ?>/releases/tag/v<?= e($version) ?>"><b>v<?= e($version) ?></b> See what is new in this release</a>
      <h1>A CI pipeline that <span class="grad">fits your PHP project</span></h1>
      <p class="lead">AxonPHP CLI reads your <code>composer.json</code>, works out which PHP versions and tools you use, and writes the pipeline for your CI service. No config file, no questions.</p>
      <div class="cta">
        <a class="btn btn-solid" href="#install">Get started</a>
        <a class="btn btn-line" href="#playground">Try the playground</a>
      </div>
      <div class="cmd">
        <span><i>$</i><?= e($install) ?></span>
        <button class="copy" type="button" data-copy="<?= e($install) ?>" aria-label="Copy the install command"><?= $copyIcon ?></button>
      </div>
      <ul class="services" aria-label="Supported CI services">
<?php foreach ($services as $service) { ?>
        <li><?= e($service['label']) ?></li>
<?php } ?>
      </ul>

      <figure class="window">
        <div class="window-bar" aria-hidden="true"><i></i><i></i><i></i><span>~/acme-library</span></div>
        <pre tabindex="0" aria-label="Example terminal session"><span class="p">$</span> <span class="type" style="--n: <?= strlen($command) ?>ch; animation-timing-function: steps(<?= strlen($command) ?>)"><?= e($command) ?></span>

<span class="out" style="--i: <?= $row++ ?>"><span class="t">AxonPHP CLI · GitHub Actions</span></span>
<span class="out" style="--i: <?= $row++ ?>"><span class="t">============================</span></span>

<?php foreach ($summary as [$label, $value, $note]) { ?>
<span class="out" style="--i: <?= $row++ ?>">  <span class="l"><?= e(str_pad($label, 17)) ?></span><?= e($value) ?><?= '' === $note ? '' : ' <span class="t">'.e($note).'</span>' ?></span>
<?php } ?>

<span class="out ok" style="--i: <?= $row + 3 ?>"> [OK] Created .github/workflows/ci.yml</span></pre>
        <figcaption class="sr-only">The command lists what it detected in the project and reports that the workflow file was created.</figcaption>
      </figure>

      <ul class="stats" aria-label="AxonPHP CLI in numbers">
        <li><b class="grad"><?= count($services) ?></b><span>CI services</span></li>
        <li><b class="grad"><?= count($tools) ?></b><span>tools detected</span></li>
        <li><b class="grad">0</b><span>config files needed</span></li>
        <li><b class="grad">100%</b><span>line coverage, enforced</span></li>
      </ul>
    </div>
  </div>

  <section id="how">
    <div class="wrap">
      <div class="head">
        <span class="eyebrow">How it works</span>
        <h2>Your composer.json already says what CI should run</h2>
        <p>Most pipelines start as a copy of the one from the last project, fixed up by hand. AxonPHP derives it from what the project declares.</p>
      </div>
      <div class="flow" data-reveal>
        <div class="stage">
          <h3><span>1</span> Read</h3>
          <div class="card"><pre><?= $presets[0]['manifest'] ?></pre></div>
          <p>The <code>php</code> constraint, every <code>ext-*</code> requirement and the packages you require.</p>
        </div>
        <div class="wire" aria-hidden="true"><i></i></div>
        <div class="stage">
          <h3><span>2</span> Derive</h3>
          <div class="card">
            <dl>
              <div><dt>PHP matrix</dt><dd><?php foreach ($presets[0]['versions'] as $phpVersion) { ?><span class="chip"><?= e($phpVersion) ?></span><?php } ?></dd></div>
              <div><dt>Tools</dt><dd><?php foreach ($presets[0]['tools'] as $toolName) { ?><span class="chip"><?= e($toolName) ?></span><?php } ?></dd></div>
              <div><dt>Extras</dt><dd><span class="chip">coverage ≥ 90%</span><span class="chip">lowest dependencies</span></dd></div>
            </dl>
          </div>
          <p>Which PHP versions to test, which extensions to install and which tools to run.</p>
        </div>
        <div class="wire" aria-hidden="true"><i></i></div>
        <div class="stage">
          <h3><span>3</span> Write</h3>
          <div class="card">
            <ul>
<?php foreach ($services as $service) { ?>
              <li><b><?= e($service['label']) ?></b><code><?= e($service['path']) ?></code></li>
<?php } ?>
            </ul>
          </div>
          <p>One file for your CI service. It is plain YAML and yours to edit.</p>
        </div>
      </div>
    </div>
  </section>

  <section id="playground" class="band">
    <div class="wrap">
      <div class="head">
        <span class="eyebrow">Playground</span>
        <h2>Pick a project, see its pipeline</h2>
        <p>This is the real output, rendered by the same code the command runs. Nothing here is written by hand.</p>
      </div>
      <div class="play-top">
        <div class="seg" role="tablist" aria-label="Example project" data-tabs="preset">
<?php foreach ($presets as $index => $preset) { ?>
          <button type="button" role="tab" id="preset-<?= e($preset['id']) ?>" aria-controls="project-<?= e($preset['id']) ?>" aria-selected="<?= 0 === $index ? 'true' : 'false' ?>"<?= 0 === $index ? '' : ' tabindex="-1"' ?>><?= e($preset['label']) ?></button>
<?php } ?>
        </div>
      </div>
<?php foreach ($presets as $index => $preset) { ?>
      <div class="pane" role="tabpanel" id="project-<?= e($preset['id']) ?>" aria-labelledby="preset-<?= e($preset['id']) ?>"<?= 0 === $index ? '' : ' hidden' ?>>
        <p class="about play-top"><?= e($preset['about']) ?></p>
        <div class="play">
          <div class="window">
            <div class="window-bar" aria-hidden="true"><i></i><i></i><i></i><span>composer.json</span></div>
<?php if (null === $preset['manifest']) { ?>
            <p class="empty">This project has no <code>composer.json</code>.</p>
<?php } else { ?>
            <pre tabindex="0" aria-label="composer.json of the <?= e($preset['label']) ?> example"><?= $preset['manifest'] ?></pre>
<?php } ?>
            <div class="derived">
              <span>AxonPHP derives</span>
<?php foreach ($preset['versions'] as $phpVersion) { ?>
              <b class="chip">PHP <?= e($phpVersion) ?></b>
<?php } ?>
<?php foreach ($preset['tools'] as $toolName) { ?>
              <b class="chip"><?= e($toolName) ?></b>
<?php } ?>
            </div>
          </div>
          <div class="window">
            <div class="seg" role="tablist" aria-label="CI service for the <?= e($preset['label']) ?> example" data-tabs="service">
<?php foreach ($services as $serviceIndex => $service) { ?>
              <button type="button" role="tab" id="tab-<?= e($preset['id'].'-'.$service['name']) ?>" data-service="<?= e($service['name']) ?>" aria-controls="out-<?= e($preset['id'].'-'.$service['name']) ?>" aria-selected="<?= 0 === $serviceIndex ? 'true' : 'false' ?>"<?= 0 === $serviceIndex ? '' : ' tabindex="-1"' ?>><?= e($service['label']) ?></button>
<?php } ?>
            </div>
<?php foreach ($services as $serviceIndex => $service) { ?>
            <div class="pane" role="tabpanel" id="out-<?= e($preset['id'].'-'.$service['name']) ?>" aria-labelledby="tab-<?= e($preset['id'].'-'.$service['name']) ?>"<?= 0 === $serviceIndex ? '' : ' hidden' ?>>
              <div class="window-bar"><span><?= e($service['path']) ?></span></div>
              <pre tabindex="0"><code><?= $preset['outputs'][$service['name']] ?></code></pre>
            </div>
<?php } ?>
          </div>
        </div>
      </div>
<?php } ?>
    </div>
  </section>

  <section id="features">
    <div class="wrap">
      <div class="head">
        <span class="eyebrow">Features</span>
        <h2>Everything a good pipeline has, none of the copy and paste</h2>
      </div>
      <div class="bento" data-reveal-children>
<?php foreach ($features as [$size, $svg, $title, $text, $extra]) { ?>
        <div class="tile <?= $size ?>">
          <?= $svg ?>

          <h3><?= e($title) ?></h3>
          <p><?= $text ?></p>
<?php if ('matrix' === $extra) { ?>
          <div class="extra" aria-hidden="true"><span class="chip">"php": "^8.2"</span><span class="arrow">→</span><span class="chip">8.2</span><span class="chip">8.3</span><span class="chip">8.4</span><span class="chip">8.5</span></div>
<?php } elseif ('providers' === $extra) { ?>
          <div class="extra" aria-hidden="true"><?php foreach ($services as $service) { ?><span class="chip"><?= e($service['path']) ?></span><?php } ?></div>
<?php } ?>
        </div>
<?php } ?>
      </div>
    </div>
  </section>

  <section id="sync" class="band">
    <div class="wrap">
      <div class="head">
        <span class="eyebrow">Stays in sync</span>
        <h2>The pipeline changes when the project does</h2>
        <p>Add a tool or drop a PHP version and the committed pipeline is out of date. One command tells you, the other fixes it.</p>
      </div>
      <div class="sync" data-reveal-children>
        <figure class="window" style="margin: 0">
          <div class="window-bar" aria-hidden="true"><i></i><i></i><i></i><span>in CI</span></div>
          <pre tabindex="0"><span class="p">$</span> composer require --dev phpstan/phpstan
<span class="p">$</span> vendor/bin/axonphp ci:check
 <span class="del">✗</span> .github/workflows/ci.yml is out of date

   <span class="del">- in your file</span>  <span class="add">+ expected</span>
     jobs:
<span class="add">   +   quality:</span>
<span class="add">   +     name: Code quality</span>
<span class="add">   +     runs-on: ubuntu-latest</span>
   <span class="l">…</span>
<span class="add">   +       - name: Static analysis (PHPStan)</span>
<span class="add">   +         run: vendor/bin/phpstan analyse --no-progress</span>

<span class="del"> [ERROR] The pipeline does not match the project.</span></pre>
          <figcaption class="sr-only">ci:check exits with 1 and prints the lines that are missing from the committed workflow.</figcaption>
        </figure>
        <figure class="window" style="margin: 0">
          <div class="window-bar" aria-hidden="true"><i></i><i></i><i></i><span>on your machine</span></div>
          <pre tabindex="0"><span class="p">$</span> vendor/bin/axonphp ci:update
 <span class="ok">✓</span> .github/workflows/ci.yml updated
 <span class="ok">✓</span> .gitlab-ci.yml updated
 <span class="ok">✓</span> bitbucket-pipelines.yml is up to date

<span class="ok"> [OK] Updated 2 pipelines. Review the changes and commit them.</span>

<span class="p">$</span> vendor/bin/axonphp ci:check
 <span class="ok">✓</span> .github/workflows/ci.yml is up to date
 <span class="ok">✓</span> .gitlab-ci.yml is up to date
 <span class="ok">✓</span> bitbucket-pipelines.yml is up to date</pre>
          <figcaption class="sr-only">ci:update rewrites the outdated pipelines, after which ci:check passes.</figcaption>
        </figure>
      </div>
    </div>
  </section>

  <section id="detection">
    <div class="wrap">
      <div class="head">
        <span class="eyebrow">Detection</span>
        <h2><?= count($tools) ?> tools, found by the packages you require</h2>
        <p>Tests run on every PHP version in the matrix. Everything else runs once, on the newest version.</p>
      </div>
      <div class="filters">
        <div class="seg" role="group" aria-label="Filter tools by type">
          <button type="button" data-filter="" aria-pressed="true">All</button>
<?php foreach ($toolTypes as $value => $label) { ?>
          <button type="button" data-filter="<?= e($value) ?>" aria-pressed="false"><?= e($label) ?></button>
<?php } ?>
        </div>
      </div>
      <div class="table-wrap" data-reveal tabindex="0" role="region" aria-label="Detected tools">
        <table>
          <thead>
            <tr><th scope="col">Tool</th><th scope="col">Type</th><th scope="col">Package</th><th scope="col">Command</th></tr>
          </thead>
          <tbody>
<?php foreach ($tools as $tool) { ?>
            <tr data-type="<?= e($tool['type']) ?>">
              <th scope="row"><?= e($tool['name']) ?></th>
              <td class="type"><span><?= e($tool['typeLabel']) ?></span></td>
              <td><?= implode('<br>', array_map(static fn (string $package): string => '<code>'.e($package).'</code>', $tool['packages'])) ?></td>
              <td><code><?= e($tool['command']) ?></code></td>
            </tr>
<?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <section id="commands" class="band">
    <div class="wrap">
      <div class="head">
        <span class="eyebrow">Commands</span>
        <h2>Five commands, one job each</h2>
      </div>
      <div class="commands" data-reveal-children>
        <div>
          <h3><code>ci:init</code></h3>
          <p>Generates the pipeline. Leave the provider out and it asks which one you use.</p>
          <pre tabindex="0"><span class="p">$ </span>axonphp ci:init gitlab
<span class="p">$ </span>axonphp ci:init github --dry-run
<span class="p">$ </span>axonphp ci:init circleci --min-coverage 90</pre>
        </div>
        <div>
          <h3><code>ci:check</code></h3>
          <p>Fails when a committed pipeline no longer matches the project. Made for CI.</p>
          <pre tabindex="0"><span class="p">$ </span>axonphp ci:check
<span class="p">$ </span>axonphp ci:check --format github
<span class="p">$ </span>axonphp ci:check --format json</pre>
        </div>
        <div>
          <h3><code>ci:update</code></h3>
          <p>Rewrites the pipelines that are out of date and leaves the others alone.</p>
          <pre tabindex="0"><span class="p">$ </span>axonphp ci:update
<span class="p">$ </span>axonphp ci:update --dry-run
<span class="p">$ </span>axonphp ci:update gitlab</pre>
        </div>
        <div>
          <h3><code>inspect</code></h3>
          <p>Shows what was detected without writing anything. JSON output for scripts.</p>
          <pre tabindex="0"><span class="p">$ </span>axonphp inspect
<span class="p">$ </span>axonphp inspect --format json | jq '.php.versions'</pre>
        </div>
        <div>
          <h3><code>tools</code></h3>
          <p>Lists every tool and provider the installed version supports.</p>
          <pre tabindex="0"><span class="p">$ </span>axonphp tools
<span class="p">$ </span>axonphp tools --format json</pre>
        </div>
      </div>
    </div>
  </section>

  <section id="config">
    <div class="wrap split" data-reveal-children>
      <div class="head">
        <span class="eyebrow">Configuration</span>
        <h2>Optional, and in the file you already have</h2>
        <p>AxonPHP needs no configuration. When you want choices to stick, put them under <code>extra.axonphp</code> in <code>composer.json</code>. Command line options still win.</p>
        <ul>
          <li><code>php</code> pins the versions to test.</li>
          <li><code>branches</code> lists the branches whose pushes trigger the pipeline.</li>
          <li><code>coverage</code>, <code>lowest</code> and <code>audit</code> switch on the extra jobs.</li>
          <li><code>min-coverage</code> is the line coverage the coverage job has to reach.</li>
        </ul>
      </div>
      <div class="window">
        <div class="window-bar" aria-hidden="true"><i></i><i></i><i></i><span>composer.json</span></div>
        <pre tabindex="0">{
  <span class="k">"extra"</span>: {
    <span class="k">"axonphp"</span>: {
      <span class="k">"branches"</span>: [<span class="s">"main"</span>, <span class="s">"develop"</span>],
      <span class="k">"min-coverage"</span>: 90,
      <span class="k">"lowest"</span>: true,
      <span class="k">"audit"</span>: true
    }
  }
}</pre>
      </div>
    </div>
  </section>

  <section id="install" class="band">
    <div class="wrap">
      <div class="head">
        <span class="eyebrow">Install</span>
        <h2>Three commands to your first green run</h2>
        <p>Requires PHP 8.2 or newer. Install it as a development dependency of the project you want a pipeline for.</p>
      </div>
      <ol class="steps" data-reveal-children>
<?php foreach ([
    ['Add the repository', 'composer config repositories.axonphp vcs '.$repository],
    ['Require the package', $install],
    ['Generate your pipeline', 'vendor/bin/axonphp ci:init'],
] as [$title, $stepCommand]) { ?>
        <li>
          <h3><?= e($title) ?></h3>
          <div class="cmd">
            <span><i>$</i><?= e($stepCommand) ?></span>
            <button class="copy" type="button" data-copy="<?= e($stepCommand) ?>" aria-label="Copy command: <?= e($title) ?>"><?= $copyIcon ?></button>
          </div>
        </li>
<?php } ?>
      </ol>
      <p class="note">The package is not on Packagist yet, which is why the repository is added first. A PHAR is attached to every <a href="<?= e($repository) ?>/releases">release</a>.</p>
    </div>
  </section>

  <section class="final">
    <div class="wrap">
      <h2>Stop copying pipelines between repositories</h2>
      <div class="cta">
        <a class="btn btn-solid" href="#install">Get started</a>
        <a class="btn btn-line" href="<?= e($repository) ?>">Star on GitHub</a>
      </div>
    </div>
  </section>
</main>

<footer>
  <div class="wrap">
    <span>AxonPHP CLI v<?= e($version) ?> · open source under the MIT license</span>
    <ul>
      <li><a href="<?= e($repository) ?>">GitHub</a></li>
      <li><a href="<?= e($repository) ?>/releases">Releases</a></li>
      <li><a href="<?= e($repository) ?>/blob/main/CHANGELOG.md">Changelog</a></li>
      <li><a href="<?= e($repository) ?>/blob/main/CONTRIBUTING.md">Contributing</a></li>
      <li><a href="<?= e($repository) ?>/issues">Issues</a></li>
    </ul>
  </div>
</footer>

<p class="sr-only" role="status" aria-live="polite" id="status"></p>

<script>
(function () {
  var root = document.documentElement;
  var status = document.getElementById('status');
  var all = function (selector, scope) { return Array.prototype.slice.call((scope || document).querySelectorAll(selector)); };

  document.getElementById('theme').addEventListener('click', function () {
    var light = root.dataset.theme
      ? root.dataset.theme === 'light'
      : window.matchMedia('(prefers-color-scheme: light)').matches;
    var next = light ? 'dark' : 'light';
    root.dataset.theme = next;
    try { localStorage.setItem('axonphp-theme', next); } catch (e) {}
    status.textContent = next === 'dark' ? 'Dark theme on' : 'Light theme on';
  });

  all('.copy').forEach(function (button) {
    button.addEventListener('click', function () {
      var done = function () {
        button.classList.add('is-done');
        status.textContent = 'Copied to clipboard';
        setTimeout(function () { button.classList.remove('is-done'); }, 1600);
      };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(button.dataset.copy).then(done, function () {
          status.textContent = 'Copy failed. Select the command and copy it manually.';
        });
      } else {
        status.textContent = 'Copy is not available. Select the command and copy it manually.';
      }
    });
  });

  // Entrances: reveal elements when they scroll into view, staggering siblings slightly.
  var revealables = all('[data-reveal], [data-reveal-children] > *');
  if ('IntersectionObserver' in window) {
    var revealer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('in');
        revealer.unobserve(entry.target);
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
    revealables.forEach(function (element) {
      var parent = element.parentElement;
      if (parent && parent.hasAttribute('data-reveal-children')) {
        element.style.setProperty('--d', Array.prototype.indexOf.call(parent.children, element) % 3 * 70 + 'ms');
      }
      revealer.observe(element);
    });
  } else {
    revealables.forEach(function (element) { element.classList.add('in'); });
  }

  // Highlight the section being read in the navigation.
  var links = all('.nav nav a');
  if ('IntersectionObserver' in window) {
    var spy = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        links.forEach(function (link) {
          if (link.getAttribute('href') === '#' + entry.target.id) link.setAttribute('aria-current', 'true');
          else link.removeAttribute('aria-current');
        });
      });
    }, { rootMargin: '-45% 0px -50% 0px' });
    links.forEach(function (link) {
      var section = document.querySelector(link.getAttribute('href'));
      if (section) spy.observe(section);
    });
  }

  // Tabs. The chosen CI service carries over when another project is picked.
  function select(tab, focus) {
    all('[role="tab"]', tab.parentElement).forEach(function (other) {
      var active = other === tab;
      other.setAttribute('aria-selected', active ? 'true' : 'false');
      other.tabIndex = active ? 0 : -1;
      document.getElementById(other.getAttribute('aria-controls')).hidden = !active;
    });
    if (focus) tab.focus();
  }
  all('[role="tablist"]').forEach(function (list) {
    var tabs = all('[role="tab"]', list);
    tabs.forEach(function (tab, index) {
      tab.addEventListener('click', function () {
        select(tab, false);
        if (tab.dataset.service) {
          all('[role="tab"][data-service="' + tab.dataset.service + '"]').forEach(function (twin) { select(twin, false); });
        }
      });
      tab.addEventListener('keydown', function (event) {
        var target = null;
        if (event.key === 'ArrowRight') target = tabs[(index + 1) % tabs.length];
        if (event.key === 'ArrowLeft') target = tabs[(index - 1 + tabs.length) % tabs.length];
        if (event.key === 'Home') target = tabs[0];
        if (event.key === 'End') target = tabs[tabs.length - 1];
        if (target) { event.preventDefault(); select(target, true); }
      });
    });
  });

  // Tool filter
  var filters = all('[data-filter]');
  var rows = all('tbody tr[data-type]');
  filters.forEach(function (button) {
    button.addEventListener('click', function () {
      var type = button.dataset.filter;
      var shown = 0;
      filters.forEach(function (other) { other.setAttribute('aria-pressed', other === button ? 'true' : 'false'); });
      rows.forEach(function (row) {
        row.hidden = type !== '' && row.dataset.type !== type;
        if (!row.hidden) shown++;
      });
      status.textContent = shown + ' tools shown';
    });
  });
})();
</script>
</body>
</html>
