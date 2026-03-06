<?php

/**
 * ApiCrumbs: The Foundry Portal Generator v2.0
 * Generates 250+ Interactive, SEO-optimized Documentation Pages
 * 
 * Local:
 * php build-docs.php url=http://localhost/ApiCrumbs/Registry/docs 
 * 
 */
// 1. Get Command Line Arguments
$urlPrefix = '';

if (isset($argv[1])) {
    parse_str($argv[1], $arg);

    if (isset($arg['url'])) {
        $urlPrefix = $arg['url'];
    }
}

// 2. Get Manifest
$manifestPath = __DIR__ . '/../manifest.json';
if (!file_exists($manifestPath)) {
    die("❌ Error: manifest.json not found.\n");
}

$manifest = json_decode(file_get_contents($manifestPath), true);
$docsDir = __DIR__ . '/../docs';

// 3. Ensure Directory Structure
if (!is_dir($docsDir . '/providers')) mkdir($docsDir . '/providers', 0755, true);
if (!is_dir($docsDir . '/category')) mkdir($docsDir . '/category', 0755, true);

// 4. Group Data by Sector
$categories = [];
foreach ($manifest['providers'] as $p) {
    $categories[$p['category']][] = $p;
}
ksort($categories);

// 4. Pre-generate Sidebar HTML
$sidebarHtml = "";
foreach ($categories as $cat => $providers) {
    $catSlug = strtolower(str_replace(' ', '-', $cat));
    $sidebarHtml .= "<div class='mb-6'><a href='{$urlPrefix}/category/{$catSlug}.html' class='text-[10px] font-black text-blue-500 uppercase tracking-widest mb-3 hover:underline block'>$cat</a>";
    foreach ($providers as $p) {
        $slug = str_replace('/', '-', $p['id']);
        $lock = ($p['tier'] === 'pro') ? "🔒 " : "";
        $sidebarHtml .= "<a href='{$urlPrefix}/providers/{$slug}.html' class='block py-1 text-sm text-slate-400 hover:text-white transition'>$lock{$p['name']}</a>";
    }
    $sidebarHtml .= "</div>";
}


// 5. The Master Layout Wrapper
$buildLayout = function($urlPrefix, $title, $content, $meta = []) use ($sidebarHtml) {
    $version = $meta['version'] ?? '1.0.0';
    $updated = $meta['updated'] ?? date('Y-m-d');
    $status  = $meta['status'] ?? '✅ ACTIVE';
    $statusColor = str_contains($status, 'COMPLIANT') ? 'text-emerald-400' : 'text-blue-500';

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>$title | ApiCrumbs</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: #0b0f1a; scroll-behavior: smooth; }
        .copy-success { color: #10b981 !important; }
    </style>
</head>
<body class="text-slate-300 flex">
    <!-- Sidebar -->
    <nav class="w-72 h-screen sticky top-0 border-r border-slate-900 p-8 overflow-y-auto bg-[#0d111d]">
        <a href="{$urlPrefix}/" class="text-white font-black text-2xl mb-10 block italic tracking-tighter">ApiCrumbs<span class="text-blue-500">.</span></a>
        <input type="text" id="cSearch" placeholder="Filter 250+ crumbs..." class="w-full bg-slate-800 border border-slate-700 rounded-lg p-2.5 text-xs mb-8 outline-none focus:ring-1 ring-blue-500">
        <div id="sLinks">$sidebarHtml</div>
    </nav>
    
    <main class="flex-1 min-h-screen flex flex-col">
        <header class="border-b border-slate-900 bg-slate-900/30 px-16 py-4 flex justify-between items-center text-[10px] font-mono tracking-widest uppercase">
            <div class="flex gap-8">
                <span>VER: <span class="text-white">$version</span></span>
                <span>LAST UPDATED: <span class="text-white">$updated</span></span>
            </div>
            <div class="font-black $statusColor">$status</div>
        </header>

        <div class="p-16 max-w-6xl flex-1">
            $content
        </div>

        <footer class="p-8 border-t border-slate-900 text-center text-[10px] text-slate-600 uppercase tracking-widest">
            &copy; 2026 ApiCrumbs Foundry • Built for the UK Compliance Roadmap
        </footer>
    </main>

    <script>
        // 1. Instant Search
        document.getElementById('cSearch').addEventListener('input', (e) => {
            const t = e.target.value.toLowerCase();
            document.querySelectorAll('#sLinks a').forEach(l => {
                l.style.display = l.textContent.toLowerCase().includes(t) ? 'block' : 'none';
            });
        });

        // 2. Copy to Clipboard Utility
        async function copyToClipboard(button, textId) {
            const text = document.getElementById(textId).innerText;
            try {
                await navigator.clipboard.writeText(text);
                const original = button.innerHTML;
                button.innerHTML = "COPIED!";
                button.classList.add('copy-success');
                setTimeout(() => {
                    button.innerHTML = original;
                    button.classList.remove('copy-success');
                }, 2000);
            } catch (err) {
                console.error('Failed to copy: ', err);
            }
        }
    </script>
</body></html>
HTML;
};

// 6. Generate GLOBAL HOME (index.html)
$homeContent = "<h1 class='text-6xl font-black text-white mb-4 italic tracking-tighter'>DATA REGISTRY</h1>";
$homeContent .= "<div class='grid md:grid-cols-2 gap-6 mt-12'>";
foreach ($categories as $cat => $providers) {
    $catSlug = strtolower(str_replace(' ', '-', $cat));
    $homeContent .= "
    <a href='{$urlPrefix}/category/{$catSlug}.html' class='p-8 rounded-2xl bg-slate-900/50 border border-slate-800 hover:border-blue-500 transition group'>
        <div class='text-blue-500 font-bold text-xs mb-2 tracking-widest'>SECTOR</div>
        <div class='text-3xl font-black text-white group-hover:text-blue-400'>$cat</div>
        <div class='text-slate-500 mt-2'>" . count($providers) . " Verified Adapters →</div>
    </a>";
}
$homeContent .= "</div>";
file_put_contents("{$docsDir}/index.html", $buildLayout($urlPrefix, "Global Registry", $homeContent));

// 7. Generate CATEGORY PORTALS
foreach ($categories as $cat => $providers) {
    $catSlug = strtolower(str_replace(' ', '-', $cat));
    $catContent = "<h1 class='text-5xl font-black text-white mb-8'>$cat <span class='text-blue-500'>Pack</span></h1>";
    $catContent .= "<div class='grid gap-4'>";
    foreach ($providers as $p) {
        $slug = str_replace('/', '-', $p['id']);
        $isPro = ($p['tier'] === 'pro');
        $catContent .= "
        <a href='{$urlPrefix}/providers/{$slug}.html' class='p-6 bg-slate-900 border border-slate-800 rounded-xl flex justify-between items-center hover:bg-slate-800 transition'>
            <div><div class='font-bold text-white'>{$p['name']}</div><div class='text-xs text-slate-500'>{$p['capabilities']}</div></div>
            " . ($isPro ? "<span class='text-[10px] font-black bg-yellow-500/20 text-yellow-500 px-2 py-1 rounded'>PRO</span>" : "<span class='text-[10px] font-black bg-blue-500/20 text-blue-400 px-2 py-1 rounded'>FREE</span>") . "
        </a>";
    }
    $catContent .= "</div>";
    file_put_contents("{$docsDir}/category/{$catSlug}.html", $buildLayout($urlPrefix, "$cat Portal", $catContent));
}

// 8. Generate PROVIDER PAGES
foreach ($manifest['providers'] as $p) {    
    $slug = str_replace('/', '-', $p['id']);
    $isPro = ($p['tier'] === 'pro');
    $className = str_replace(' ', '', $p['name']) . "Provider";
    $namespace = "ApiCrumbs\Providers\\" . str_replace(' ', '', $p['category']);

    $badge = $isPro ? 
        "<span class='bg-yellow-500/10 text-yellow-500 border border-yellow-500/20 px-4 py-1 rounded-full text-xs font-black'>BUSINESS PRO</span>" : 
        "<span class='bg-blue-500/10 text-blue-500 border border-blue-500/20 px-4 py-1 rounded-full text-xs font-black'>CORE FREE</span>";
    
    $detail = "
    <div class='flex items-center gap-4 mb-8'>$badge <span class='text-slate-500 font-mono text-sm'>{$p['id']}</span></div>
    <h1 class='text-7xl font-black text-white mb-6 tracking-tighter'>{$p['name']}</h1>
    <p class='text-2xl text-slate-400 mb-12 leading-relaxed max-w-3xl'>{$p['capabilities']}</p>
    <p class='text-xl text-slate-400 mb-12 leading-relaxed max-w-3xl'>{$p['description']}</p>
    
    <div class='grid md:grid-cols-2 gap-8 mb-12'>
        <!-- CLI Install with Copy -->
        <div class='bg-black border border-slate-800 p-8 rounded-2xl relative group'>
            <button onclick=\"copyToClipboard(this, 'cli-{$slug}')\" class='absolute top-4 right-4 text-[10px] font-black text-slate-500 hover:text-white uppercase tracking-widest transition'>Copy</button>
            <div class='text-blue-500 font-bold text-xs uppercase mb-4 tracking-widest'>CLI Installation</div>
            <code id='cli-{$slug}' class='text-lg text-emerald-400 font-mono'>php foundry install {$p['id']}</code>
        </div>
        <div class='bg-slate-900/50 border border-slate-800 p-8 rounded-2xl'>
            <div class='text-slate-500 font-bold text-xs uppercase mb-4 tracking-widest'>Class Reference</div>
            <div class='text-white font-mono text-sm'>{$namespace}\\{$className}</div>
        </div>
    </div>

    <!-- Implementation Example with Copy -->
    <div class='mb-12'>
        <h2 class='text-2xl font-black text-white mb-6 uppercase italic tracking-tight'>Implementation Example</h2>
        <div class='bg-[#0d1117] border border-slate-800 rounded-2xl overflow-hidden relative'>
            <button onclick=\"copyToClipboard(this, 'code-{$slug}')\" class='absolute top-3 right-4 text-[10px] font-black text-slate-500 hover:text-white uppercase tracking-widest transition'>Copy Code</button>
            <div class='bg-slate-800/50 px-4 py-2 text-[10px] text-slate-500 font-mono uppercase tracking-widest border-b border-slate-800'>PHP 8.4+ Context Injection</div>
            <pre id='code-{$slug}' class='p-8 text-sm leading-relaxed overflow-x-auto'><code class='text-blue-300'>use</code> <code class='text-white'>ApiCrumbs\Core\ApiCrumbs;</code>
<code class='text-blue-300'>use</code> <code class='text-white'>{$namespace}\\{$className};</code>

<code class='text-white'>\$crumbs = new ApiCrumbs();</code>
<code class='text-white'>\$crumbs->registerProvider(new {$className}());</code>

<code class='text-blue-300'>echo</code> <code class='text-white'>\$crumbs->build('{$p['example_id']}')->toMarkdown();</code></pre>
        </div>
    </div>";

    if ($isPro) {
        $detail .= "
        <div class='p-8 rounded-2xl bg-yellow-500/5 border border-yellow-500/20'>
            <div class='text-yellow-500 font-black mb-2 uppercase italic tracking-widest'>🔒 Private Access Required</div>
            <p class='text-slate-400 mb-6'>This provider belongs to the <strong>{$p['pack']}</strong>. Licensed access via GitHub Sponsorship.</p>
            <a href='https://github.com' class='bg-yellow-600 text-black font-black px-8 py-4 rounded-xl hover:bg-yellow-500 transition inline-block'>Unlock Pro Adapter</a>
        </div>";
    }

    $meta = [
        'version' => $p['version'] ?? '1.0.0',
        'updated' => $p['last_updated'] ?? date('Y-m-d'),
        'status'  => (str_contains($p['id'], 'hmrc') || str_contains($p['id'], 'biz')) ? '✅ 2026 COMPLIANT' : '✅ ACTIVE'
    ];

    file_put_contents("{$docsDir}/providers/{$slug}.html", $buildLayout($urlPrefix, $p['name'], $detail, $meta));
}

echo "🚀 [BAKE COMPLETE] 250+ Pages with Copy-to-Clipboard logic enabled.\n";
