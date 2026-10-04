<?php
/**
 * Template for the GitHub Pages site. Rendered by site/build.php.
 *
 * @var list<array{name: string, label: string, path: string, yaml: string}>                 $examples
 * @var list<array{name: string, type: string, packages: list<string>, command: string}> $tools
 * @var int                                                                                  $commandCount
 * @var string                                                                               $repository
 */
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>AxonPHP CLI: a CI pipeline that fits your PHP project</title>
<meta name="description" content="AxonPHP CLI reads your composer.json and generates a ready-to-run CI pipeline for GitHub Actions, GitLab CI or Bitbucket Pipelines. Zero configuration.">
<meta name="color-scheme" content="light dark">
<meta property="og:title" content="AxonPHP CLI">
<meta property="og:description" content="One command. A CI pipeline that fits your PHP project.">
<meta property="og:type" content="website">
<meta property="og:image" content="https://maximilianfeix.github.io/AxonPHPCLI/og.png">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" href="logo.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:ital,wght@0,400;0,500;0,600;0,700;1,700&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<style>
:root {
  --php-900: #1E2140;
  --php-800: #2A2E57;
  --php-700: #3D4777;
  --php-600: #4F5B93;
  --php-500: #777BB4;
  --php-400: #8892BF;
  --php-200: #C7CBEA;
  --php-100: #E2E4EF;
  --green: #7EE0A1;
  --yellow: #F0C674;

  --bg: #FFFFFF;
  --surface: #F5F6FB;
  --card: #FFFFFF;
  --text: #1E2140;
  --muted: #555B7E;
  --border: #D9DCEE;
  --link: #4F5B93;
  --primary: #4F5B93;
  --primary-hover: #3D4777;
  --on-primary: #FFFFFF;
  --code-bg: #1B1D36;
  --code-text: #E2E4EF;
  --ring: #4F5B93;

  --sans: "IBM Plex Sans", -apple-system, "Segoe UI", Helvetica, Arial, sans-serif;
  --mono: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  --radius: 12px;
  --wrap: 1120px;
}

:root[data-theme="dark"] {
  --bg: #14162B;
  --surface: #1B1D36;
  --card: #23264A;
  --text: #ECEEFA;
  --muted: #B4B9DA;
  --border: #363A66;
  --link: #B7BFEE;
  --primary: #C7CBEA;
  --primary-hover: #E2E4EF;
  --on-primary: #1E2140;
  --code-bg: #0F1124;
  --ring: #C7CBEA;
}

@media (prefers-color-scheme: dark) {
  :root:not([data-theme="light"]) {
    --bg: #14162B;
    --surface: #1B1D36;
    --card: #23264A;
    --text: #ECEEFA;
    --muted: #B4B9DA;
    --border: #363A66;
    --link: #B7BFEE;
    --primary: #C7CBEA;
    --primary-hover: #E2E4EF;
    --on-primary: #1E2140;
    --code-bg: #0F1124;
    --ring: #C7CBEA;
  }
}

*, *::before, *::after { box-sizing: border-box; }

html { scroll-behavior: smooth; scroll-padding-top: 80px; }

body {
  margin: 0;
  background: var(--bg);
  color: var(--text);
  font: 400 1rem/1.6 var(--sans);
  -webkit-font-smoothing: antialiased;
}

img, svg { max-width: 100%; height: auto; display: block; }

a { color: var(--link); text-underline-offset: 3px; }
a:hover { text-decoration-thickness: 2px; }

:focus-visible { outline: 3px solid var(--ring); outline-offset: 3px; border-radius: 4px; }

h1, h2, h3 { line-height: 1.15; margin: 0; letter-spacing: -0.02em; text-wrap: balance; }
h2 { font-size: clamp(1.6rem, 3.2vw, 2.25rem); }
h3 { font-size: 1.125rem; letter-spacing: -0.01em; }
p { margin: 0; }

code, pre, kbd { font-family: var(--mono); font-size: 0.875em; }
:not(pre) > code { background: var(--surface); border: 1px solid var(--border); border-radius: 6px; padding: 0.1em 0.4em; overflow-wrap: anywhere; }

.wrap { width: min(100% - 32px, var(--wrap)); margin-inline: auto; }

.skip { position: absolute; left: 16px; top: -100px; background: var(--primary); color: var(--on-primary); padding: 10px 16px; border-radius: 8px; z-index: 100; }
.skip:focus { top: 12px; }

.sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip-path: inset(50%); white-space: nowrap; }

/* Navigation */
.nav { position: sticky; top: 0; z-index: 50; background: var(--php-600); color: #fff; border-bottom: 1px solid rgb(255 255 255 / 0.14); }
.nav .wrap { display: flex; align-items: center; gap: 24px; min-height: 64px; }
.brand { display: flex; align-items: center; gap: 10px; color: #fff; text-decoration: none; font-weight: 700; font-size: 1.125rem; }
.brand img { width: 34px; height: 34px; border-radius: 9px; box-shadow: 0 0 0 1px rgb(255 255 255 / 0.35); }
.brand i { font-weight: 700; color: var(--php-100); }
.nav nav { margin-left: auto; }
.nav ul { display: flex; gap: 4px; list-style: none; margin: 0; padding: 0; }
.nav nav a { color: #fff; text-decoration: none; padding: 10px 12px; border-radius: 8px; font-weight: 500; font-size: 0.95rem; display: block; }
.nav nav a:hover { background: rgb(255 255 255 / 0.14); }
.nav :focus-visible { outline-color: #fff; }
.icon-btn { display: inline-grid; place-items: center; width: 44px; height: 44px; border-radius: 10px; border: 1px solid rgb(255 255 255 / 0.3); background: transparent; color: #fff; cursor: pointer; transition: background-color 150ms ease; }
.icon-btn:hover { background: rgb(255 255 255 / 0.14); }
.icon-btn svg { width: 20px; height: 20px; }
.icon-btn .moon, :root[data-theme="dark"] .icon-btn .sun { display: none; }
:root[data-theme="dark"] .icon-btn .moon { display: block; }
@media (prefers-color-scheme: dark) {
  :root:not([data-theme="light"]) .icon-btn .sun { display: none; }
  :root:not([data-theme="light"]) .icon-btn .moon { display: block; }
}
@media (max-width: 820px) {
  .nav .wrap { flex-wrap: wrap; gap: 0 16px; }
  .nav .actions { margin-left: auto; }
  .nav nav { order: 3; width: 100%; margin: 0 -8px; overflow-x: auto; scrollbar-width: none; }
  .nav nav::-webkit-scrollbar { display: none; }
  .nav ul { width: max-content; padding-bottom: 6px; }
  .nav nav a { padding: 10px; white-space: nowrap; }
  html { scroll-padding-top: 128px; }
}
.actions { display: flex; gap: 8px; align-items: center; }

/* Hero */
.hero { position: relative; overflow: hidden; background: linear-gradient(135deg, var(--php-900) 0%, #343A6B 55%, var(--php-600) 100%); color: #fff; }
.hero::before { content: ""; position: absolute; right: -12%; top: -30%; width: 70%; aspect-ratio: 1.8; border-radius: 50%; background: rgb(136 146 191 / 0.16); transform: rotate(-8deg); pointer-events: none; }
.hero .wrap { position: relative; display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.05fr); gap: 48px; align-items: center; padding-block: 72px 80px; }
.tag { display: inline-flex; align-items: center; gap: 8px; font: 500 0.85rem var(--mono); color: var(--php-100); background: rgb(255 255 255 / 0.1); border: 1px solid rgb(255 255 255 / 0.2); border-radius: 999px; padding: 6px 12px; }
.tag b { color: var(--green); font-weight: 700; }
.hero h1 { font-size: clamp(2.2rem, 5vw, 3.5rem); margin-top: 20px; }
.hero h1 i { color: var(--php-200); }
.lead { font-size: 1.15rem; color: var(--php-100); margin-top: 20px; max-width: 34em; }
.cta { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 28px; }
.btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 48px; padding: 0 20px; border-radius: 10px; font: 600 1rem var(--sans); text-decoration: none; border: 1px solid transparent; cursor: pointer; transition: background-color 150ms ease, border-color 150ms ease; }
.btn svg { width: 18px; height: 18px; flex: none; }
.btn-light { background: #fff; color: var(--php-900); }
.btn-light:hover { background: var(--php-100); }
.btn-ghost { background: transparent; color: #fff; border-color: rgb(255 255 255 / 0.4); }
.btn-ghost:hover { background: rgb(255 255 255 / 0.12); }
.hero :focus-visible { outline-color: #fff; }
.hero-demo img { border-radius: 12px; box-shadow: 0 24px 60px rgb(10 12 30 / 0.45); width: 100%; }
@media (max-width: 960px) { .hero .wrap { grid-template-columns: minmax(0, 1fr); padding-block: 48px 56px; gap: 36px; } }

/* Command box */
.cmd { position: relative; margin-top: 28px; background: rgb(15 17 36 / 0.6); border: 1px solid rgb(255 255 255 / 0.18); border-radius: 10px; }
.cmd pre { margin: 0; padding: 16px 64px 16px 18px; white-space: pre-wrap; overflow-wrap: anywhere; color: #fff; line-height: 1.7; }
.cmd pre .p { color: var(--green); user-select: none; }
.copy { position: absolute; top: 8px; right: 8px; width: 44px; height: 44px; display: grid; place-items: center; border-radius: 8px; border: 1px solid rgb(255 255 255 / 0.22); background: rgb(255 255 255 / 0.06); color: #fff; cursor: pointer; transition: background-color 150ms ease; }
.copy:hover { background: rgb(255 255 255 / 0.18); }
.copy svg { width: 18px; height: 18px; }
.copy .done, .copy.is-done .idle { display: none; }
.copy.is-done .done { display: block; color: var(--green); }
.copy:focus-visible { outline-color: #fff; }

/* Stats */
.stats { background: var(--surface); border-bottom: 1px solid var(--border); }
.stats ul { list-style: none; margin: 0; padding: 28px 0; display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; text-align: center; }
.stats b { display: block; font: 700 2rem/1.1 var(--mono); color: var(--primary); }
.stats span { color: var(--muted); font-size: 0.95rem; }
@media (max-width: 640px) { .stats ul { grid-template-columns: repeat(2, 1fr); } }

/* Sections */
section { padding-block: 80px; }
section.alt { background: var(--surface); border-block: 1px solid var(--border); }
.eyebrow { font: 500 0.9rem var(--mono); color: var(--link); margin-bottom: 12px; }
.intro { color: var(--muted); font-size: 1.1rem; margin-top: 14px; max-width: 42em; }
.head { margin-bottom: 40px; }
@media (max-width: 640px) { section { padding-block: 56px; } }

/* How it works */
.flow img { width: 100%; border-radius: 12px; }
.steps { list-style: none; margin: 32px 0 0; padding: 0; display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; counter-reset: step; }
.steps li { counter-increment: step; }
.steps h3::before { content: counter(step); display: inline-grid; place-items: center; width: 28px; height: 28px; margin-right: 10px; border-radius: 50%; background: var(--primary); color: var(--on-primary); font: 700 0.85rem var(--mono); vertical-align: 2px; }
.steps p { color: var(--muted); margin-top: 8px; }
@media (max-width: 820px) { .steps { grid-template-columns: 1fr; } }

/* Feature grid */
.grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
.card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; }
.card .ico { width: 44px; height: 44px; border-radius: 10px; display: grid; place-items: center; background: var(--php-100); color: var(--php-600); margin-bottom: 16px; }
.card .ico svg { width: 22px; height: 22px; }
.card p { color: var(--muted); margin-top: 8px; }
@media (max-width: 960px) { .grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px) { .grid { grid-template-columns: 1fr; } }

/* Table */
.table-wrap { overflow-x: auto; border: 1px solid var(--border); border-radius: var(--radius); background: var(--card); }
table { border-collapse: collapse; width: 100%; min-width: 640px; }
caption { text-align: left; padding: 14px 16px; color: var(--muted); font-size: 0.95rem; border-bottom: 1px solid var(--border); }
th, td { text-align: left; padding: 12px 16px; border-bottom: 1px solid var(--border); vertical-align: top; }
thead th { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted); background: var(--surface); }
tbody tr:last-child > * { border-bottom: 0; }
tbody th { font-weight: 600; white-space: nowrap; }
td code { white-space: nowrap; }
.pill { display: inline-block; font-size: 0.8rem; font-weight: 600; padding: 2px 10px; border-radius: 999px; background: var(--surface); border: 1px solid var(--border); color: var(--muted); white-space: nowrap; }

/* Tabs and code */
.tabs { display: flex; gap: 4px; flex-wrap: wrap; }
.tabs button { min-height: 44px; padding: 0 16px; border: 1px solid var(--border); border-bottom: 0; border-radius: 10px 10px 0 0; background: var(--surface); color: var(--muted); font: 600 0.95rem var(--sans); cursor: pointer; transition: background-color 150ms ease, color 150ms ease; }
.tabs button:hover { color: var(--text); }
.tabs button[aria-selected="true"] { background: var(--code-bg); color: #fff; border-color: var(--code-bg); }
.panel { position: relative; background: var(--code-bg); border-radius: 0 var(--radius) var(--radius) var(--radius); }
.panel .file { display: block; padding: 12px 64px 12px 18px; color: var(--php-200); font: 500 0.85rem var(--mono); border-bottom: 1px solid rgb(255 255 255 / 0.1); }
.panel pre, .code pre { margin: 0; padding: 18px; overflow: auto; color: var(--code-text); line-height: 1.65; tab-size: 2; }
.panel pre { max-height: 520px; }
.c { color: #8C93BD; }
.k { color: #AFC0FF; }
.s { color: var(--yellow); }
.code { position: relative; background: var(--code-bg); border-radius: var(--radius); }
.code pre { padding-right: 64px; }
.code .p { color: var(--green); user-select: none; }

/* Commands */
.commands { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
.commands .card { display: flex; flex-direction: column; gap: 12px; }
.commands .card p { margin: 0; }
.commands .code { margin-top: auto; }
.commands .code pre { font-size: 0.8rem; padding-right: 18px; }
.commands h3 code { font-size: 1rem; background: none; border: 0; padding: 0; color: var(--link); }
@media (max-width: 960px) { .commands { grid-template-columns: 1fr; } }

.split { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 48px; align-items: center; }
.split ul { margin: 20px 0 0; padding-left: 20px; color: var(--muted); }
.split li + li { margin-top: 8px; }
@media (max-width: 960px) { .split { grid-template-columns: 1fr; gap: 28px; } }

/* Install */
.install ol { list-style: none; margin: 0; padding: 0; display: grid; gap: 20px; counter-reset: step; }
.install li { counter-increment: step; display: grid; grid-template-columns: 40px minmax(0, 1fr); gap: 16px; }
.install li::before { content: counter(step); width: 40px; height: 40px; border-radius: 50%; display: grid; place-items: center; background: var(--primary); color: var(--on-primary); font: 700 1rem var(--mono); }
.install h3 { margin-bottom: 10px; padding-top: 8px; }
.install .code .copy { top: 6px; right: 6px; }
.install .code pre { white-space: pre-wrap; overflow-wrap: anywhere; }

/* Final call */
.final { background: linear-gradient(135deg, var(--php-900), var(--php-600)); color: #fff; text-align: center; }
.final p { color: var(--php-100); margin: 14px auto 0; max-width: 36em; font-size: 1.1rem; }
.final .cta { justify-content: center; }
.final :focus-visible { outline-color: #fff; }

footer { padding: 32px 0; color: var(--muted); font-size: 0.95rem; border-top: 1px solid var(--border); }
footer .wrap { display: flex; flex-wrap: wrap; gap: 12px 24px; justify-content: space-between; }
footer ul { display: flex; flex-wrap: wrap; gap: 4px 20px; list-style: none; margin: 0; padding: 0; }

@media (prefers-reduced-motion: reduce) {
  html { scroll-behavior: auto; }
  * { transition: none !important; }
}
</style>
<script>
// Applied before first paint so a stored theme does not flash.
try {
  var stored = localStorage.getItem('axonphp-theme');
  if (stored === 'light' || stored === 'dark') document.documentElement.dataset.theme = stored;
} catch (e) {}
</script>
</head>
<body>
<a class="skip" href="#main">Skip to content</a>

<header class="nav">
  <div class="wrap">
    <a class="brand" href="#top"><img src="logo.svg" alt="" width="34" height="34"><span>Axon<i>PHP</i> CLI</span></a>
    <nav aria-label="Sections">
      <ul>
        <li><a href="#how">How it works</a></li>
        <li><a href="#features">Features</a></li>
        <li><a href="#output">Output</a></li>
        <li><a href="#commands">Commands</a></li>
        <li><a href="#install">Install</a></li>
      </ul>
    </nav>
    <div class="actions">
      <button class="icon-btn" type="button" id="theme" aria-label="Switch between light and dark theme">
        <svg class="sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
        <svg class="moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
      </button>
      <a class="icon-btn" href="<?= e($repository) ?>" aria-label="AxonPHP CLI on GitHub">
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 .5a11.5 11.5 0 0 0-3.64 22.41c.58.1.79-.25.79-.56v-2c-3.2.7-3.88-1.36-3.88-1.36-.52-1.33-1.28-1.69-1.28-1.69-1.05-.71.08-.7.08-.7 1.16.08 1.77 1.19 1.77 1.19 1.03 1.77 2.7 1.26 3.36.96.1-.75.4-1.26.73-1.55-2.55-.29-5.24-1.28-5.24-5.69 0-1.26.45-2.29 1.19-3.09-.12-.29-.52-1.46.11-3.05 0 0 .97-.31 3.18 1.18a11 11 0 0 1 5.78 0c2.2-1.49 3.17-1.18 3.17-1.18.63 1.59.23 2.76.11 3.05.74.8 1.19 1.83 1.19 3.09 0 4.42-2.7 5.4-5.26 5.68.41.36.78 1.06.78 2.14v3.17c0 .31.21.67.8.56A11.5 11.5 0 0 0 12 .5z"/></svg>
      </a>
    </div>
  </div>
</header>

<main id="main">
  <div class="hero" id="top">
    <div class="wrap">
      <div>
        <span class="tag"><b>&lt;?php</b> zero config · MIT licensed</span>
        <h1>One command. A CI pipeline that fits your <i>PHP</i> project.</h1>
        <p class="lead">AxonPHP CLI reads your <code style="background:rgb(255 255 255 / 0.12);border-color:rgb(255 255 255 / 0.2)">composer.json</code>, works out which PHP versions and tools you use, and writes the pipeline for GitHub Actions, GitLab CI or Bitbucket Pipelines.</p>
        <div class="cmd">
          <pre id="hero-cmd"><span class="p">$ </span>vendor/bin/axonphp ci:init github</pre>
          <button class="copy" type="button" data-copy="vendor/bin/axonphp ci:init github" aria-label="Copy command">
            <svg class="idle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
            <svg class="done" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5 9-10"/></svg>
          </button>
        </div>
        <div class="cta">
          <a class="btn btn-light" href="#install">Get started</a>
          <a class="btn btn-ghost" href="<?= e($repository) ?>">View on GitHub</a>
        </div>
      </div>
      <div class="hero-demo">
        <img src="demo.svg" width="860" height="496" alt="Terminal: axonphp ci:init github lists the detected PHP versions 8.2 to 8.5, the extensions intl and pdo, PHPUnit, PHPStan and PHP-CS-Fixer, then reports that .github/workflows/ci.yml was created.">
      </div>
    </div>
  </div>

  <div class="stats">
    <div class="wrap">
      <ul>
        <li><b><?= count($examples) ?></b><span>CI providers</span></li>
        <li><b><?= count($tools) ?></b><span>tools detected</span></li>
        <li><b><?= $commandCount ?></b><span>commands</span></li>
        <li><b>0</b><span>config files needed</span></li>
      </ul>
    </div>
  </div>

  <section id="how">
    <div class="wrap">
      <div class="head">
        <p class="eyebrow">// how it works</p>
        <h2>Your composer.json already says what CI should run</h2>
        <p class="intro">Every project copies a pipeline from the last one and fixes it up by hand. AxonPHP derives it from what the project declares instead.</p>
      </div>
      <div class="flow">
        <img src="how-it-works.svg" width="860" height="300" loading="lazy" alt="Diagram: AxonPHP reads composer.json, detects the PHP version matrix, extensions and tools, and writes the pipeline file for GitHub Actions, GitLab CI or Bitbucket Pipelines.">
      </div>
      <ol class="steps">
        <li><h3>Read</h3><p>The <code>php</code> constraint, every <code>ext-*</code> requirement and the packages in <code>require-dev</code>.</p></li>
        <li><h3>Detect</h3><p>Which PHP versions to test, which extensions to install and which tools to run.</p></li>
        <li><h3>Write</h3><p>One pipeline file for your CI service. It is plain YAML and yours to edit.</p></li>
      </ol>
    </div>
  </section>

  <section id="features" class="alt">
    <div class="wrap">
      <div class="head">
        <p class="eyebrow">// features</p>
        <h2>The defaults you would set up yourself, on day one</h2>
      </div>
      <div class="grid">
        <article class="card">
          <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg></div>
          <h3>A matrix that matches your constraint</h3>
          <p><code>"php": "^8.2"</code> becomes a test matrix of 8.2, 8.3, 8.4 and 8.5. Change the constraint and the matrix follows.</p>
        </article>
        <article class="card">
          <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18v3h3l6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4z"/></svg></div>
          <h3>Runs the tools you already use</h3>
          <p>PHPUnit, Pest or Codeception. PHPStan, Psalm, Rector or Deptrac. PHP-CS-Fixer, Pint, ECS or PHP_CodeSniffer.</p>
        </article>
        <article class="card">
          <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg></div>
          <h3>Stays in sync</h3>
          <p><code>ci:check</code> fails when the committed pipeline no longer matches the project, and shows the difference.</p>
        </article>
        <article class="card">
          <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l8 3v6c0 4.5-3.2 8-8 9-4.8-1-8-4.500-8-9V6z"/></svg></div>
          <h3>Coverage, audit, lowest dependencies</h3>
          <p>Opt in to a coverage job, a <code>composer audit</code> step and a run against the lowest versions you allow.</p>
        </article>
        <article class="card">
          <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13 2L4 14h7l-1 8 9-12h-7z"/></svg></div>
          <h3>Pipeline hygiene built in</h3>
          <p>Dependency caching, least-privilege permissions, cancelled superseded runs and no duplicate runs for pull requests.</p>
        </article>
        <article class="card">
          <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 15V3M7 8l5-5 5 5M5 15v4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4"/></svg></div>
          <h3>Safe to run</h3>
          <p>An existing pipeline is never overwritten without asking, and <code>--dry-run</code> prints the result first.</p>
        </article>
      </div>
    </div>
  </section>

  <section id="detection">
    <div class="wrap">
      <div class="head">
        <p class="eyebrow">// detection</p>
        <h2>What AxonPHP looks for</h2>
        <p class="intro">Tests run on every PHP version in the matrix. Static analysis and code style run once, on the newest version.</p>
      </div>
      <div class="table-wrap" tabindex="0" role="region" aria-label="Detected tools">
        <table>
          <caption>Packages in <code>require</code> or <code>require-dev</code> and the command they add to the pipeline</caption>
          <thead>
            <tr><th scope="col">Tool</th><th scope="col">Type</th><th scope="col">Package</th><th scope="col">Command</th></tr>
          </thead>
          <tbody>
<?php foreach ($tools as $tool) { ?>
            <tr>
              <th scope="row"><?= e($tool['name']) ?></th>
              <td><span class="pill"><?= e($tool['type']) ?></span></td>
              <td><?= implode('<br>', array_map(static fn (string $package): string => '<code>'.e($package).'</code>', $tool['packages'])) ?></td>
              <td><code><?= e($tool['command']) ?></code></td>
            </tr>
<?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <section id="output" class="alt">
    <div class="wrap">
      <div class="head">
        <p class="eyebrow">// output</p>
        <h2>Readable YAML, not a black box</h2>
        <p class="intro">This is the real output for a project that requires PHP ^8.2 and <code>ext-intl</code> and uses PHPUnit, PHPStan and PHP-CS-Fixer, generated with <code>--coverage</code>.</p>
      </div>
      <div class="tabs" role="tablist" aria-label="CI provider">
<?php foreach ($examples as $index => $example) { ?>
        <button type="button" role="tab" id="tab-<?= e($example['name']) ?>" aria-controls="panel-<?= e($example['name']) ?>" aria-selected="<?= 0 === $index ? 'true' : 'false' ?>"<?= 0 === $index ? '' : ' tabindex="-1"' ?>><?= e($example['label']) ?></button>
<?php } ?>
      </div>
<?php foreach ($examples as $index => $example) { ?>
      <div class="panel" role="tabpanel" id="panel-<?= e($example['name']) ?>" aria-labelledby="tab-<?= e($example['name']) ?>"<?= 0 === $index ? '' : ' hidden' ?>>
        <span class="file"><?= e($example['path']) ?></span>
        <pre tabindex="0"><code><?= $example['yaml'] ?></code></pre>
      </div>
<?php } ?>
    </div>
  </section>

  <section id="commands">
    <div class="wrap">
      <div class="head">
        <p class="eyebrow">// commands</p>
        <h2>Generate, verify, inspect</h2>
      </div>
      <div class="commands">
        <article class="card">
          <h3><code>ci:init</code></h3>
          <p>Generate the pipeline. Leave the provider out and it asks which one you use.</p>
          <div class="code"><pre><span class="p">$ </span>axonphp ci:init gitlab
<span class="p">$ </span>axonphp ci:init github --dry-run
<span class="p">$ </span>axonphp ci:init github \
    --coverage --lowest --audit</pre></div>
        </article>
        <article class="card">
          <h3><code>ci:check</code></h3>
          <p>Compare the committed pipeline with what the project needs now. Exits with 1 on drift.</p>
          <div class="code"><pre><span class="p">$ </span>axonphp ci:check
 <span style="color:#ff8f8f">✗</span> .github/workflows/ci.yml
   is out of date
<span style="color:#7EE0A1">+   quality:</span></pre></div>
        </article>
        <article class="card">
          <h3><code>inspect</code></h3>
          <p>See what was detected without writing anything. JSON output for scripts.</p>
          <div class="code"><pre><span class="p">$ </span>axonphp inspect
<span class="p">$ </span>axonphp inspect --format json \
    | jq '.php.versions'</pre></div>
        </article>
      </div>
    </div>
  </section>

  <section id="config" class="alt">
    <div class="wrap split">
      <div>
        <p class="eyebrow">// configuration</p>
        <h2>Optional, and it lives in composer.json</h2>
        <p class="intro">AxonPHP needs no configuration. When you want choices to stick, put them under <code>extra.axonphp</code>. Command line options still win.</p>
        <ul>
          <li><code>php</code> pins the versions to test.</li>
          <li><code>branches</code> lists the branches whose pushes trigger the pipeline.</li>
          <li><code>coverage</code>, <code>lowest</code> and <code>audit</code> switch on the extra jobs.</li>
        </ul>
      </div>
      <div class="code"><pre><span class="c">// composer.json</span>
{
  <span class="k">"extra"</span>: {
    <span class="k">"axonphp"</span>: {
      <span class="k">"branches"</span>: [<span class="s">"main"</span>, <span class="s">"develop"</span>],
      <span class="k">"coverage"</span>: true,
      <span class="k">"lowest"</span>: true,
      <span class="k">"audit"</span>: true
    }
  }
}</pre></div>
    </div>
  </section>

  <section id="install" class="install">
    <div class="wrap">
      <div class="head">
        <p class="eyebrow">// install</p>
        <h2>Up and running in a minute</h2>
        <p class="intro">Requires PHP 8.2 or newer. Install it as a development dependency of the project you want a pipeline for.</p>
      </div>
      <ol>
        <li>
          <div>
            <h3>Add the repository</h3>
            <div class="code">
              <pre><span class="p">$ </span>composer config repositories.axonphp vcs <?= e($repository) ?></pre>
              <button class="copy" type="button" data-copy="composer config repositories.axonphp vcs <?= e($repository) ?>" aria-label="Copy command">
                <svg class="idle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
                <svg class="done" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5 9-10"/></svg>
              </button>
            </div>
          </div>
        </li>
        <li>
          <div>
            <h3>Require the package</h3>
            <div class="code">
              <pre><span class="p">$ </span>composer require --dev maxim/axonphp-cli:dev-main</pre>
              <button class="copy" type="button" data-copy="composer require --dev maxim/axonphp-cli:dev-main" aria-label="Copy command">
                <svg class="idle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
                <svg class="done" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5 9-10"/></svg>
              </button>
            </div>
          </div>
        </li>
        <li>
          <div>
            <h3>Generate your pipeline</h3>
            <div class="code">
              <pre><span class="p">$ </span>vendor/bin/axonphp ci:init</pre>
              <button class="copy" type="button" data-copy="vendor/bin/axonphp ci:init" aria-label="Copy command">
                <svg class="idle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
                <svg class="done" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5 9-10"/></svg>
              </button>
            </div>
          </div>
        </li>
      </ol>
    </div>
  </section>

  <section class="final">
    <div class="wrap">
      <h2>Stop copying pipelines between repositories</h2>
      <p>AxonPHP CLI is open source under the MIT license. Issues, new tool detections and new providers are welcome.</p>
      <div class="cta">
        <a class="btn btn-light" href="<?= e($repository) ?>">Star on GitHub</a>
        <a class="btn btn-ghost" href="<?= e($repository) ?>/blob/main/CONTRIBUTING.md">Contribute</a>
      </div>
    </div>
  </section>
</main>

<footer>
  <div class="wrap">
    <span>AxonPHP CLI · MIT License</span>
    <ul>
      <li><a href="<?= e($repository) ?>">GitHub</a></li>
      <li><a href="<?= e($repository) ?>/releases">Releases</a></li>
      <li><a href="<?= e($repository) ?>/blob/main/CHANGELOG.md">Changelog</a></li>
      <li><a href="<?= e($repository) ?>/issues">Issues</a></li>
    </ul>
  </div>
</footer>

<p class="sr-only" role="status" aria-live="polite" id="status"></p>

<script>
(function () {
  var root = document.documentElement;
  var status = document.getElementById('status');

  document.getElementById('theme').addEventListener('click', function () {
    var dark = root.dataset.theme
      ? root.dataset.theme === 'dark'
      : window.matchMedia('(prefers-color-scheme: dark)').matches;
    var next = dark ? 'light' : 'dark';
    root.dataset.theme = next;
    try { localStorage.setItem('axonphp-theme', next); } catch (e) {}
    status.textContent = next === 'dark' ? 'Dark theme on' : 'Light theme on';
  });

  document.querySelectorAll('.copy').forEach(function (button) {
    button.addEventListener('click', function () {
      var text = button.dataset.copy;
      var done = function () {
        button.classList.add('is-done');
        status.textContent = 'Copied to clipboard';
        setTimeout(function () { button.classList.remove('is-done'); }, 1600);
      };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(done, function () {
          status.textContent = 'Copy failed. Select the command and copy it manually.';
        });
      } else {
        status.textContent = 'Copy is not available. Select the command and copy it manually.';
      }
    });
  });

  var tabs = Array.prototype.slice.call(document.querySelectorAll('[role="tab"]'));
  function select(tab, focus) {
    tabs.forEach(function (other) {
      var active = other === tab;
      other.setAttribute('aria-selected', active ? 'true' : 'false');
      other.tabIndex = active ? 0 : -1;
      document.getElementById(other.getAttribute('aria-controls')).hidden = !active;
    });
    if (focus) tab.focus();
  }
  tabs.forEach(function (tab, index) {
    tab.addEventListener('click', function () { select(tab, false); });
    tab.addEventListener('keydown', function (event) {
      var target = null;
      if (event.key === 'ArrowRight') target = tabs[(index + 1) % tabs.length];
      if (event.key === 'ArrowLeft') target = tabs[(index - 1 + tabs.length) % tabs.length];
      if (event.key === 'Home') target = tabs[0];
      if (event.key === 'End') target = tabs[tabs.length - 1];
      if (target) { event.preventDefault(); select(target, true); }
    });
  });
})();
</script>
</body>
</html>
