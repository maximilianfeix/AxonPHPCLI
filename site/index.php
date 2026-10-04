<?php
/**
 * Template for the GitHub Pages site. Rendered by site/build.php.
 *
 * @var list<array{name: string, label: string, path: string, yaml: string}>             $examples
 * @var list<array{name: string, type: string, packages: list<string>, command: string}> $tools
 * @var list<array{string, string, string}>                                              $summary  label, value, note
 * @var string                                                                           $repository
 */
$copyIcon = '<svg class="idle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>'
    .'<svg class="done" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5 9-10"/></svg>';
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
<meta property="og:description" content="A CI pipeline that fits your PHP project, generated from composer.json.">
<meta property="og:type" content="website">
<meta property="og:image" content="https://maximilianfeix.github.io/AxonPHPCLI/og.png">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" href="favicon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fira+Mono:wght@400;500;700&family=Fira+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --php-ink: #1E2140;
  --php-600: #4F5B93;
  --php-500: #777BB4;
  --php-400: #8892BF;
  --php-200: #C7CBEA;
  --php-100: #E2E4EF;

  --bg: #FFFFFF;
  --surface: #F5F6FB;
  --text: #1E2140;
  --muted: #555B7E;
  --border: #D5D8EA;
  --link: #4F5B93;
  --primary: #4F5B93;
  --ring: #4F5B93;

  --code-bg: #F5F6FB;
  --code-text: #1E2140;
  --code-comment: #5F6588;
  --code-key: #4F5B93;
  --code-string: #8A5200;

  --term-bg: #14162B;
  --term-text: #E2E4EF;
  --term-dim: #A9B0DD;
  --term-green: #6FDC8C;
  --term-yellow: #F0C674;

  --sans: "Fira Sans", -apple-system, "Segoe UI", Helvetica, Arial, sans-serif;
  --mono: "Fira Mono", ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  --radius: 6px;
  --wrap: 1080px;
}

:root[data-theme="dark"] {
  --bg: #14162B;
  --surface: #1B1D36;
  --text: #ECEEFA;
  --muted: #B4B9DA;
  --border: #363A66;
  --link: #B7BFEE;
  --primary: #8892BF;
  --ring: #C7CBEA;
  --code-bg: #1B1D36;
  --code-text: #E2E4EF;
  --code-comment: #969DC6;
  --code-key: #AFC0FF;
  --code-string: #F0C674;
  --term-bg: #0F1124;
}

@media (prefers-color-scheme: dark) {
  :root:not([data-theme="light"]) {
    --bg: #14162B;
    --surface: #1B1D36;
    --text: #ECEEFA;
    --muted: #B4B9DA;
    --border: #363A66;
    --link: #B7BFEE;
    --primary: #8892BF;
    --ring: #C7CBEA;
    --code-bg: #1B1D36;
    --code-text: #E2E4EF;
    --code-comment: #969DC6;
    --code-key: #AFC0FF;
    --code-string: #F0C674;
    --term-bg: #0F1124;
  }
}

*, *::before, *::after { box-sizing: border-box; }

html { scroll-behavior: smooth; scroll-padding-top: 76px; }

body {
  margin: 0;
  background: var(--bg);
  color: var(--text);
  font: 400 1.0625rem/1.6 var(--sans);
}

img, svg { max-width: 100%; height: auto; display: block; }

a { color: var(--link); text-underline-offset: 3px; }
a:hover { text-decoration-thickness: 2px; }

:focus-visible { outline: 3px solid var(--ring); outline-offset: 2px; border-radius: 3px; }

h1, h2, h3 { margin: 0; line-height: 1.2; font-weight: 600; text-wrap: balance; }
h2 { font-size: clamp(1.5rem, 2.6vw, 1.875rem); }
h3 { font-size: 1.0625rem; }
p { margin: 0; }

code, pre { font-family: var(--mono); font-size: 0.875em; }
:not(pre) > code { background: var(--surface); border: 1px solid var(--border); border-radius: 4px; padding: 0.05em 0.35em; white-space: nowrap; }

.wrap { width: min(100% - 32px, var(--wrap)); margin-inline: auto; }

.skip { position: absolute; left: 16px; top: -100px; background: #fff; color: var(--php-ink); padding: 10px 16px; border-radius: var(--radius); z-index: 100; }
.skip:focus { top: 10px; }

.sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip-path: inset(50%); white-space: nowrap; }

/* Top bar */
.nav { position: sticky; top: 0; z-index: 50; background: var(--php-600); color: #fff; }
.nav .wrap { display: flex; align-items: center; gap: 24px; min-height: 60px; }
.brand { display: flex; align-items: center; gap: 10px; color: #fff; text-decoration: none; font-weight: 600; font-size: 1.125rem; }
.brand img { width: 36px; height: 36px; }
.nav nav { margin-left: auto; }
.nav ul { display: flex; gap: 2px; list-style: none; margin: 0; padding: 0; }
.nav nav a { display: block; padding: 10px 12px; border-radius: var(--radius); color: #fff; text-decoration: none; font-size: 1rem; }
.nav nav a:hover { background: rgb(255 255 255 / 0.16); }
.nav :focus-visible { outline-color: #fff; }
.actions { display: flex; gap: 4px; align-items: center; }
.icon-btn { display: inline-grid; place-items: center; width: 44px; height: 44px; border-radius: var(--radius); border: 0; background: transparent; color: #fff; cursor: pointer; }
.icon-btn:hover { background: rgb(255 255 255 / 0.16); }
.icon-btn svg { width: 20px; height: 20px; }
.icon-btn .moon, :root[data-theme="dark"] .icon-btn .sun { display: none; }
:root[data-theme="dark"] .icon-btn .moon { display: block; }
@media (prefers-color-scheme: dark) {
  :root:not([data-theme="light"]) .icon-btn .sun { display: none; }
  :root:not([data-theme="light"]) .icon-btn .moon { display: block; }
}
@media (max-width: 860px) {
  .nav .wrap { flex-wrap: wrap; gap: 0 16px; }
  .nav .actions { margin-left: auto; }
  .nav nav { order: 3; width: 100%; margin: 0 -10px; overflow-x: auto; scrollbar-width: none; }
  .nav nav::-webkit-scrollbar { display: none; }
  .nav ul { width: max-content; padding-bottom: 6px; }
  .nav nav a { white-space: nowrap; }
  html { scroll-padding-top: 124px; }
}

/* Hero */
.hero { background: var(--php-ink); color: #fff; }
.hero .wrap { display: grid; grid-template-columns: minmax(0, 0.9fr) minmax(0, 1.1fr); gap: 56px; align-items: center; padding-block: 72px; }
.hero h1 { font-size: clamp(2rem, 4.4vw, 3rem); font-weight: 700; letter-spacing: -0.01em; }
.lead { font-size: 1.1875rem; color: var(--php-100); margin-top: 20px; }
.lead code { background: transparent; border-color: rgb(255 255 255 / 0.3); color: #fff; }
.cta { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 28px; }
.btn { display: inline-flex; align-items: center; justify-content: center; min-height: 46px; padding: 0 20px; border-radius: var(--radius); font: 600 1rem var(--sans); text-decoration: none; border: 1px solid transparent; }
.btn-solid { background: #fff; color: var(--php-ink); }
.btn-solid:hover { background: var(--php-100); }
.btn-line { color: #fff; border-color: rgb(255 255 255 / 0.5); }
.btn-line:hover { background: rgb(255 255 255 / 0.1); }
.hero :focus-visible { outline-color: #fff; }
@media (max-width: 960px) { .hero .wrap { grid-template-columns: minmax(0, 1fr); gap: 36px; padding-block: 48px; } }

/* Terminal: real text, so it can be read, zoomed and selected */
.term { margin: 0; background: var(--term-bg); border: 1px solid rgb(255 255 255 / 0.22); border-radius: var(--radius); color: var(--term-text); }
.term-bar { display: flex; align-items: center; gap: 6px; padding: 10px 14px; border-bottom: 1px solid rgb(255 255 255 / 0.14); font: 400 0.8125rem var(--mono); color: var(--term-dim); }
.term-bar i { width: 10px; height: 10px; border-radius: 50%; background: rgb(255 255 255 / 0.28); }
.term-bar span { margin-left: 8px; }
.term pre { margin: 0; padding: 16px 18px 18px; overflow-x: auto; font-size: 0.875rem; line-height: 1.65; }
.term .p, .term .ok { color: var(--term-green); }
.term .ok { font-weight: 700; }
.term .t { color: var(--term-yellow); }
.term .l { color: var(--term-dim); }
.term :focus-visible { outline-color: #fff; outline-offset: -3px; }

/* Sections */
section { padding-block: 72px; border-bottom: 1px solid var(--border); }
section.alt { background: var(--surface); }
.head { margin-bottom: 32px; max-width: 46em; }
.head p { color: var(--muted); margin-top: 12px; }
@media (max-width: 640px) { section { padding-block: 48px; } }

/* How it works */
.flow img { width: 100%; border-radius: var(--radius); }
.steps { list-style: none; margin: 28px 0 0; padding: 0; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 32px; counter-reset: step; }
.steps li { counter-increment: step; border-top: 2px solid var(--primary); padding-top: 14px; }
.steps h3::before { content: counter(step) ". "; color: var(--link); }
.steps p { color: var(--muted); margin-top: 6px; }
@media (max-width: 760px) {
  .steps { grid-template-columns: minmax(0, 1fr); gap: 20px; margin-top: 0; }
  /* The diagram's labels would be too small to read here; the steps below say the same thing. */
  .flow { display: none; }
}

/* Features: a plain two-column list */
.features { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 56px; margin: 0; }
.features > div { padding: 18px 0; border-top: 1px solid var(--border); }
.features dt { font-weight: 600; }
.features dd { margin: 4px 0 0; color: var(--muted); }
@media (max-width: 760px) { .features { grid-template-columns: minmax(0, 1fr); } }

/* Table */
.table-wrap { overflow-x: auto; border: 1px solid var(--border); border-radius: var(--radius); background: var(--bg); }
table { border-collapse: collapse; width: 100%; min-width: 680px; font-size: 1rem; }
th, td { text-align: left; padding: 10px 16px; border-bottom: 1px solid var(--border); vertical-align: top; }
thead th { font-size: 0.875rem; font-weight: 600; color: var(--muted); background: var(--surface); }
tbody tr:last-child > * { border-bottom: 0; }
tbody th { font-weight: 600; white-space: nowrap; }
td code { white-space: nowrap; }
td.type { color: var(--muted); white-space: nowrap; }

/* Code */
.code { position: relative; background: var(--code-bg); border: 1px solid var(--border); border-radius: var(--radius); color: var(--code-text); }
.code pre { margin: 0; padding: 14px 16px; overflow-x: auto; line-height: 1.6; tab-size: 2; }
.code.has-copy pre { padding-right: 60px; white-space: pre-wrap; overflow-wrap: anywhere; }
.code pre:focus-visible { outline-offset: -3px; }
.code .p { color: var(--code-comment); user-select: none; }
.c { color: var(--code-comment); }
.k { color: var(--code-key); }
.s { color: var(--code-string); }
.copy { position: absolute; top: 4px; right: 4px; width: 44px; height: 44px; display: grid; place-items: center; border: 0; border-radius: var(--radius); background: transparent; color: var(--muted); cursor: pointer; }
.copy:hover { color: var(--text); background: var(--border); }
.copy svg { width: 18px; height: 18px; }
.copy .done, .copy.is-done .idle { display: none; }
.copy.is-done .done { display: block; }

/* Tabs */
.tabs { display: flex; flex-wrap: wrap; gap: 0 4px; border-bottom: 1px solid var(--border); }
.tabs button { min-height: 44px; padding: 0 16px; margin-bottom: -1px; border: 0; border-bottom: 3px solid transparent; background: none; color: var(--muted); font: 500 1rem var(--sans); cursor: pointer; }
.tabs button:hover { color: var(--text); }
.tabs button[aria-selected="true"] { color: var(--text); border-bottom-color: var(--primary); font-weight: 600; }
.panel { margin-top: 16px; }
.panel .file { font: 400 0.875rem var(--mono); color: var(--muted); margin-bottom: 8px; }
.panel .code { background: var(--bg); }
.panel pre { max-height: 520px; overflow: auto; }

/* Commands */
.commands { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 32px; }
.commands > div { min-width: 0; display: flex; flex-direction: column; gap: 10px; border-top: 2px solid var(--primary); padding-top: 14px; }
.commands h3 code { font-size: 1.0625rem; background: none; border: 0; padding: 0; }
.commands p { color: var(--muted); }
.commands .code { margin-top: auto; min-width: 0; }
.commands .code pre { font-size: 0.8125rem; }
@media (max-width: 960px) { .commands { grid-template-columns: minmax(0, 1fr); } }

.split { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 56px; align-items: start; }
.split .head { margin-bottom: 0; }
.split ul { margin: 16px 0 0; padding-left: 20px; color: var(--muted); }
.split li + li { margin-top: 6px; }
.split .code { background: var(--bg); }
@media (max-width: 960px) { .split { grid-template-columns: minmax(0, 1fr); gap: 24px; } }

/* Install */
.install ol { margin: 0; padding: 0; list-style: none; display: grid; gap: 20px; max-width: 760px; counter-reset: step; }
.install li { counter-increment: step; }
.install h3 { margin-bottom: 8px; }
.install h3::before { content: counter(step) ". "; color: var(--link); }
.note { color: var(--muted); margin-top: 20px; max-width: 760px; }

footer { padding: 28px 0 40px; color: var(--muted); font-size: 1rem; }
footer .wrap { display: flex; flex-wrap: wrap; gap: 8px 24px; justify-content: space-between; }
footer ul { display: flex; flex-wrap: wrap; gap: 4px 20px; list-style: none; margin: 0; padding: 0; }

@media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } }
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
    <a class="brand" href="#top"><img src="logo.svg" alt="" width="36" height="36"><span>AxonPHP CLI</span></a>
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
        <h1>A CI pipeline that fits your PHP project</h1>
        <p class="lead">AxonPHP CLI reads your <code>composer.json</code>, works out which PHP versions and tools you use, and writes the pipeline for GitHub Actions, GitLab CI or Bitbucket Pipelines.</p>
        <div class="cta">
          <a class="btn btn-solid" href="#install">Install</a>
          <a class="btn btn-line" href="<?= e($repository) ?>">View on GitHub</a>
        </div>
      </div>
      <figure class="term">
        <div class="term-bar" aria-hidden="true"><i></i><i></i><i></i><span>acme-app</span></div>
        <pre tabindex="0" aria-label="Example terminal session"><span class="p">$</span> vendor/bin/axonphp ci:init github --coverage

<span class="t">AxonPHP CLI · GitHub Actions</span>
<span class="t">============================</span>

<?php foreach ($summary as [$label, $value, $note]) { ?>
  <span class="l"><?= e(str_pad($label, 17)) ?></span><?= e($value) ?><?= '' === $note ? '' : ' <span class="t">'.e($note).'</span>' ?>

<?php } ?>

<span class="ok"> [OK] Created .github/workflows/ci.yml</span></pre>
        <figcaption class="sr-only">The command lists what it detected in the project and reports that the workflow file was created.</figcaption>
      </figure>
    </div>
  </div>

  <section id="how">
    <div class="wrap">
      <div class="head">
        <h2>Your composer.json already says what CI should run</h2>
        <p>Most pipelines start as a copy of the one from the last project, fixed up by hand. AxonPHP derives it from what the project declares.</p>
      </div>
      <div class="flow">
        <img src="how-it-works.svg" width="960" height="360" loading="lazy" alt="Diagram: AxonPHP reads composer.json, derives the PHP version matrix, extensions and tools, and writes the pipeline file for GitHub Actions, GitLab CI or Bitbucket Pipelines.">
      </div>
      <ol class="steps">
        <li><h3>Read</h3><p>The <code>php</code> constraint, every <code>ext-*</code> requirement and the packages in <code>require-dev</code>.</p></li>
        <li><h3>Derive</h3><p>Which PHP versions to test, which extensions to install and which tools to run.</p></li>
        <li><h3>Write</h3><p>One pipeline file for your CI service. It is plain YAML and yours to edit.</p></li>
      </ol>
    </div>
  </section>

  <section id="features" class="alt">
    <div class="wrap">
      <div class="head">
        <h2>What you get</h2>
      </div>
      <dl class="features">
        <div><dt>A matrix that matches your constraint</dt><dd><code>"php": "^8.2"</code> becomes a test matrix of 8.2, 8.3, 8.4 and 8.5. Change the constraint and the matrix follows.</dd></div>
        <div><dt>The tools you already use</dt><dd>PHPUnit, Pest or Codeception. PHPStan, Psalm, Rector or Deptrac. PHP-CS-Fixer, Pint, ECS or PHP_CodeSniffer.</dd></div>
        <div><dt>A pipeline that stays in sync</dt><dd><code>ci:check</code> fails when the committed pipeline no longer matches the project, and prints the difference.</dd></div>
        <div><dt>Coverage, audit and lowest dependencies</dt><dd>Opt in to a coverage job, a <code>composer audit</code> step and a run against the lowest versions you allow.</dd></div>
        <div><dt>Sensible pipeline defaults</dt><dd>Dependency caching, least-privilege permissions, cancelled superseded runs, no duplicate runs for pull requests.</dd></div>
        <div><dt>Nothing overwritten by surprise</dt><dd>An existing pipeline is only replaced when you confirm it, and <code>--dry-run</code> prints the result first.</dd></div>
      </dl>
    </div>
  </section>

  <section id="detection">
    <div class="wrap">
      <div class="head">
        <h2>What AxonPHP looks for</h2>
        <p>A package in <code>require</code> or <code>require-dev</code> adds its command to the pipeline. Tests run on every PHP version in the matrix; static analysis and code style run once, on the newest.</p>
      </div>
      <div class="table-wrap" tabindex="0" role="region" aria-label="Detected tools">
        <table>
          <thead>
            <tr><th scope="col">Tool</th><th scope="col">Type</th><th scope="col">Package</th><th scope="col">Command</th></tr>
          </thead>
          <tbody>
<?php foreach ($tools as $tool) { ?>
            <tr>
              <th scope="row"><?= e($tool['name']) ?></th>
              <td class="type"><?= e($tool['type']) ?></td>
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
        <h2>Readable YAML</h2>
        <p>The real output for a project that requires PHP ^8.2 and <code>ext-intl</code> and uses PHPUnit, PHPStan and PHP-CS-Fixer, generated with <code>--coverage</code>.</p>
      </div>
      <div class="tabs" role="tablist" aria-label="CI provider">
<?php foreach ($examples as $index => $example) { ?>
        <button type="button" role="tab" id="tab-<?= e($example['name']) ?>" aria-controls="panel-<?= e($example['name']) ?>" aria-selected="<?= 0 === $index ? 'true' : 'false' ?>"<?= 0 === $index ? '' : ' tabindex="-1"' ?>><?= e($example['label']) ?></button>
<?php } ?>
      </div>
<?php foreach ($examples as $index => $example) { ?>
      <div class="panel" role="tabpanel" id="panel-<?= e($example['name']) ?>" aria-labelledby="tab-<?= e($example['name']) ?>"<?= 0 === $index ? '' : ' hidden' ?>>
        <p class="file"><?= e($example['path']) ?></p>
        <div class="code"><pre tabindex="0"><code><?= $example['yaml'] ?></code></pre></div>
      </div>
<?php } ?>
    </div>
  </section>

  <section id="commands">
    <div class="wrap">
      <div class="head">
        <h2>Commands</h2>
      </div>
      <div class="commands">
        <div>
          <h3><code>ci:init</code></h3>
          <p>Generates the pipeline. Leave the provider out and it asks which one you use.</p>
          <div class="code"><pre tabindex="0"><span class="p">$ </span>axonphp ci:init gitlab
<span class="p">$ </span>axonphp ci:init github --dry-run
<span class="p">$ </span>axonphp ci:init github --coverage --lowest --audit</pre></div>
        </div>
        <div>
          <h3><code>ci:check</code></h3>
          <p>Compares the committed pipeline with what the project needs now. Exits with 1 when they differ.</p>
          <div class="code"><pre tabindex="0"><span class="p">$ </span>axonphp ci:check
 ✗ .github/workflows/ci.yml is out of date
   + quality:
   +   name: Code quality</pre></div>
        </div>
        <div>
          <h3><code>inspect</code></h3>
          <p>Shows what was detected without writing anything. JSON output for scripts.</p>
          <div class="code"><pre tabindex="0"><span class="p">$ </span>axonphp inspect
<span class="p">$ </span>axonphp inspect --format json | jq '.php.versions'</pre></div>
        </div>
      </div>
    </div>
  </section>

  <section id="config" class="alt">
    <div class="wrap split">
      <div class="head">
        <h2>Configuration is optional</h2>
        <p>AxonPHP needs no configuration. When you want choices to stick, put them under <code>extra.axonphp</code> in <code>composer.json</code>. Command line options still win.</p>
        <ul>
          <li><code>php</code> pins the versions to test.</li>
          <li><code>branches</code> lists the branches whose pushes trigger the pipeline.</li>
          <li><code>coverage</code>, <code>lowest</code> and <code>audit</code> switch on the extra jobs.</li>
        </ul>
      </div>
      <div class="code"><pre tabindex="0">{
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
        <h2>Install</h2>
        <p>Requires PHP 8.2 or newer. Install it as a development dependency of the project you want a pipeline for.</p>
      </div>
      <ol>
<?php foreach ([
    ['Add the repository', 'composer config repositories.axonphp vcs '.$repository],
    ['Require the package', 'composer require --dev maxim/axonphp-cli:dev-main'],
    ['Generate your pipeline', 'vendor/bin/axonphp ci:init'],
] as [$title, $command]) { ?>
        <li>
          <h3><?= e($title) ?></h3>
          <div class="code has-copy">
            <pre><span class="p">$ </span><?= e($command) ?></pre>
            <button class="copy" type="button" data-copy="<?= e($command) ?>" aria-label="Copy command: <?= e($title) ?>"><?= $copyIcon ?></button>
          </div>
        </li>
<?php } ?>
      </ol>
      <p class="note">The package is not on Packagist yet, which is why the repository is added first. <code>dev-main</code> installs the current development version described on this page.</p>
    </div>
  </section>
</main>

<footer>
  <div class="wrap">
    <span>AxonPHP CLI is open source under the MIT license.</span>
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
