<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-ag-theme="light">
    <head>
        @include('partials.head', ['title' => 'AquaGrow'])
    </head>
    <body class="ag-landing min-h-screen antialiased" data-ag-theme="light">
        <header class="ag-landing-surface sticky top-0 z-20 border-b ag-landing-border">
            <nav class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-5 py-4 sm:px-8" aria-label="Main navigation">
                <a href="{{ route('home') }}" class="flex items-center gap-3 rounded-lg focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-teal-700">
                    <x-app-logo-icon class="size-9 ag-landing-accent" />
                    <span class="text-lg font-semibold tracking-tight ag-landing-heading">AquaGrow</span>
                </a>
                <div class="hidden items-center gap-6 text-sm font-medium md:flex">
                    <a href="#workspace" class="ag-landing-link transition focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-teal-700">Workspace</a>
                    <a href="#flow" class="ag-landing-link transition focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-teal-700">How it works</a>
                </div>
                <div class="flex items-center gap-2 sm:gap-3">
                    <button type="button" class="ag-landing-secondary inline-flex size-10 items-center justify-center rounded-lg" data-ag-theme-toggle aria-label="Switch to dark mode" title="Switch theme">
                        <flux:icon.moon data-ag-theme-icon="dark" class="size-5" aria-hidden="true" />
                        <flux:icon.sun data-ag-theme-icon="light" class="hidden size-5" aria-hidden="true" />
                    </button>
                    <a href="{{ route('login') }}" class="ag-landing-link rounded-lg px-3 py-2 text-sm font-medium transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-700">Log in</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="ag-landing-primary hidden rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-700 md:block">Create workspace</a>
                    @endif
                </div>
            </nav>
            <div class="border-t ag-landing-border md:hidden">
                <div class="mx-auto flex max-w-7xl items-center justify-center gap-2 px-5 py-3 sm:px-8">
                    <a href="#workspace" class="ag-landing-link rounded-lg px-3 py-2 text-sm font-semibold focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-700">Workspace</a>
                    <a href="#flow" class="ag-landing-link rounded-lg px-3 py-2 text-sm font-semibold focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-700">How it works</a>
                </div>
            </div>
        </header>

        <main>
            <section id="workspace" class="mx-auto grid max-w-7xl items-center gap-12 px-5 py-16 sm:px-8 sm:py-24 lg:grid-cols-[0.8fr_1.2fr] lg:gap-16 lg:py-28" aria-labelledby="hero-heading">
                <div class="ag-landing-reveal">
                    <p class="ag-landing-kicker mb-5 inline-flex rounded-full px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.14em]">A focused aquaculture workspace</p>
                    <h1 id="hero-heading" class="max-w-xl text-4xl font-semibold leading-[1.08] tracking-tight ag-landing-heading sm:text-6xl">A calmer way to run every cultivation cycle.</h1>
                    <p class="mt-6 max-w-xl text-lg leading-8 ag-landing-muted">Keep farm context, pond readiness, and stocking plans connected in one workspace your owners and operators can trust.</p>
                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="ag-landing-primary rounded-lg px-5 py-3 text-sm font-semibold shadow-sm transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-700">Start with your farm</a>
                        @endif
                        <a href="#workspace" class="ag-landing-secondary rounded-lg px-5 py-3 text-sm font-semibold shadow-sm transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-700">See the workspace</a>
                    </div>
                    <p class="mt-5 text-sm ag-landing-muted">Farm, location, pond, and cycle foundations are available today.</p>
                </div>

                <div class="ag-landing-reveal">
                    <div class="ag-landing-preview overflow-hidden rounded-3xl p-3 sm:p-5">
                        <div class="ag-landing-surface-muted rounded-2xl border ag-landing-border p-4 sm:p-6">
                            <div class="flex flex-wrap items-center justify-between gap-3 border-b ag-landing-border pb-5">
                                <div class="flex items-center gap-3"><span class="flex size-9 items-center justify-center rounded-xl ag-landing-primary text-xs font-bold">AG</span><div><p class="text-sm font-semibold ag-landing-heading">Farm workspace</p><p class="text-xs ag-landing-muted">Illustrative product preview</p></div></div>
                                <span class="ag-landing-kicker rounded-full px-2.5 py-1 text-xs font-semibold">Example view</span>
                            </div>
                            <div class="mt-5 grid gap-4 sm:grid-cols-[0.8fr_1.2fr]">
                                <div class="ag-landing-surface rounded-2xl border ag-landing-border p-4"><p class="text-xs font-semibold uppercase tracking-[0.12em] ag-landing-muted">Pond readiness</p><div class="mt-5 space-y-3"><div class="flex items-center justify-between gap-3 rounded-xl border ag-landing-border px-3 py-2.5"><span class="text-sm ag-landing-heading">Pond A-01</span><span class="text-xs font-semibold ag-landing-accent">Available</span></div><div class="flex items-center justify-between gap-3 rounded-xl border ag-landing-border px-3 py-2.5"><span class="text-sm ag-landing-heading">Pond A-02</span><span class="text-xs font-semibold ag-landing-accent">Cycle planned</span></div><div class="flex items-center justify-between gap-3 rounded-xl border ag-landing-border px-3 py-2.5"><span class="text-sm ag-landing-heading">Pond A-03</span><span class="text-xs font-semibold ag-landing-muted">Maintenance</span></div></div></div>
                                <div class="ag-landing-surface rounded-2xl border ag-landing-border p-4"><div class="flex items-start justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-[0.12em] ag-landing-muted">Cultivation cycle</p><p class="mt-2 text-xl font-semibold ag-landing-heading">NILA · Batch A-24</p></div><span class="ag-landing-kicker rounded-full px-2.5 py-1 text-xs font-semibold">PLANNED</span></div><div class="mt-6 grid grid-cols-2 gap-3"><div class="ag-landing-surface-muted rounded-xl p-3"><p class="text-xs ag-landing-muted">Pond</p><p class="mt-1 text-sm font-semibold ag-landing-heading">Pond A-02</p></div><div class="ag-landing-surface-muted rounded-xl p-3"><p class="text-xs ag-landing-muted">Start date</p><p class="mt-1 text-sm font-semibold ag-landing-heading">Not recorded</p></div></div><div class="mt-4 flex items-center justify-between gap-3 border-t ag-landing-border pt-4"><span class="text-xs ag-landing-muted">Owner workspace</span><span class="text-xs font-semibold ag-landing-accent">Review plan →</span></div></div>
                            </div>
                            <p class="mt-4 text-center text-xs ag-landing-muted">Illustrative labels only — this preview is not live farm data.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section id="flow" class="ag-landing-section-alt" aria-labelledby="flow-heading"><div class="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-20"><div class="grid gap-10 lg:grid-cols-[0.7fr_1.3fr] lg:items-end"><div class="ag-landing-reveal"><p class="text-sm font-semibold uppercase tracking-[0.14em] ag-landing-accent">The operating picture</p><h2 id="flow-heading" class="mt-3 text-3xl font-semibold tracking-tight ag-landing-heading sm:text-4xl">One connected story from farm to first stocking.</h2></div><p class="ag-landing-reveal max-w-2xl text-lg leading-8 ag-landing-muted">AquaGrow keeps the relationships visible: a farm contains locations, locations organize ponds, and a pond carries one clear open cultivation plan at a time.</p></div><div class="mt-12 grid gap-4 md:grid-cols-3"><div class="ag-landing-node ag-landing-reveal rounded-2xl p-6"><span class="text-3xl font-semibold ag-landing-accent">01</span><h3 class="mt-8 text-lg font-semibold ag-landing-heading">Farm context</h3><p class="mt-2 text-sm leading-6 ag-landing-muted">Work inside the active farm so teams see the right operational records.</p></div><div class="ag-landing-node ag-landing-reveal rounded-2xl p-6"><span class="text-3xl font-semibold ag-landing-accent">02</span><h3 class="mt-8 text-lg font-semibold ag-landing-heading">Pond readiness</h3><p class="mt-2 text-sm leading-6 ag-landing-muted">Understand whether a pond is empty, planned, active, or unavailable.</p></div><div class="ag-landing-node ag-landing-reveal rounded-2xl p-6"><span class="text-3xl font-semibold ag-landing-accent">03</span><h3 class="mt-8 text-lg font-semibold ag-landing-heading">Cultivation start</h3><p class="mt-2 text-sm leading-6 ag-landing-muted">Plan a batch, then record initial count and average weight when stocking begins.</p></div></div></div></section>

            <section class="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-20"><div class="ag-landing-primary ag-landing-reveal flex flex-col justify-between gap-8 rounded-3xl px-6 py-10 sm:px-10 lg:flex-row lg:items-center"><div class="max-w-2xl"><p class="text-sm font-semibold uppercase tracking-[0.14em] opacity-80">Ready when your plan is</p><h2 class="mt-3 text-2xl font-semibold tracking-tight sm:text-3xl">Give the next cycle a clearer beginning.</h2><p class="mt-3 leading-7 opacity-85">Set up the farm context, choose an available pond, and keep the first stocking record close to the plan.</p></div><a href="{{ route('login') }}" class="ag-landing-secondary inline-flex shrink-0 items-center justify-center rounded-lg px-5 py-3 text-sm font-semibold shadow-sm transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Open AquaGrow</a></div></section>
        </main>

        <footer class="border-t ag-landing-border ag-landing-surface"><div class="mx-auto flex max-w-7xl flex-col gap-2 px-5 py-8 text-sm sm:flex-row sm:items-center sm:justify-between sm:px-8"><p class="font-semibold ag-landing-heading">AquaGrow</p><p class="ag-landing-muted">A focused workspace for aquaculture operations.</p></div></footer>

        <script>
            (() => {
                const root = document.documentElement;
                const landing = document.body;
                const stored = window.localStorage.getItem('flux.appearance');
                const applyLandingTheme = (theme, persist = false) => {
                    root.dataset.agTheme = theme;
                    landing.dataset.agTheme = theme;
                    document.querySelectorAll('[data-ag-theme-choice]').forEach((button) => button.setAttribute('aria-pressed', button.dataset.agThemeChoice === theme ? 'true' : 'false'));
                    const themeToggle = document.querySelector('[data-ag-theme-toggle]');
                    if (themeToggle) {
                        const nextTheme = theme === 'dark' ? 'light' : 'dark';
                        themeToggle.setAttribute('aria-label', `Switch to ${nextTheme} theme`);
                        document.querySelectorAll('[data-ag-theme-icon]').forEach((icon) => icon.classList.toggle('hidden', icon.dataset.agThemeIcon !== nextTheme));
                    }
                    if (persist && window.Flux?.applyAppearance) window.Flux.applyAppearance(theme);
                };
                applyLandingTheme(stored === 'dark' ? 'dark' : 'light');
                document.querySelector('[data-ag-theme-toggle]')?.addEventListener('click', () => applyLandingTheme(root.dataset.agTheme === 'dark' ? 'light' : 'dark', true));

                const revealItems = document.querySelectorAll('.ag-landing-reveal');
                if (! window.matchMedia('(prefers-reduced-motion: reduce)').matches && 'IntersectionObserver' in window) {
                    revealItems.forEach((item) => item.classList.add('is-hidden'));
                    const observer = new IntersectionObserver((entries, currentObserver) => entries.forEach((entry) => { if (! entry.isIntersecting) return; entry.target.classList.remove('is-hidden'); entry.target.classList.add('is-visible'); currentObserver.unobserve(entry.target); }), { threshold: 0.12 });
                    revealItems.forEach((item) => observer.observe(item));
                }
            })();
        </script>
    </body>
</html>
