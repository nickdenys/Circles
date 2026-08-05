<?php

test('the list detail page reflows its hero for mobile', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)
        ->toContain('useIsMobile')
        ->toContain("flexDirection: 'column-reverse'")
        ->toContain("isMobile ? 'clamp(32px, 10vw, 42px)' : 54")
        ->toContain('size={isMobile ? 132 : 208}')
        ->toContain("'0 16px calc(64px + env(safe-area-inset-bottom))'");
});

test('the list detail stat band tightens on mobile', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)
        ->toContain('gap: isMobile ? 32 : 48')
        ->toContain("padding: isMobile ? '14px 0' : '16px 0'")
        ->toContain('size={isMobile ? 22 : 26}');
});

test('the list detail toolbar scrolls horizontally and grows its view toggle on mobile', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)
        ->toContain("className={isMobile ? 'hscroll' : undefined}")
        ->toContain("overflowX: isMobile ? 'auto' : 'visible'")
        ->toContain('boxSize={isMobile ? 40 : 34}');
});

test('the album table renders a compact single row on mobile', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)
        ->toContain('/* Mobile: one compact row, no hover-only affordances, note indented below. */')
        ->toContain('<AlbumCover album={album} size={54} radius={0} />')
        ->toContain('boxSize={44}')
        ->toContain("padding: '0 2px 12px 84px'")
        ->toContain('gap: isMobile ? 0 : 2');
});

test('the desktop table header and meta column are dropped on mobile', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)
        ->toContain('{!isMobile && (')
        ->toContain('className="meta-col"');

    expect(file_get_contents(resource_path('css/app.css')))
        ->toContain('.meta-col')
        ->toContain('display: none !important');
});

test('the album grid uses a denser mobile track', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)
        ->toContain("'repeat(auto-fill, minmax(min(100%, 140px), 1fr))'")
        ->toContain('gap: isMobile ? 14 : 26');
});

test('manual reordering stays reachable on the mobile row', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)
        ->toContain("display: draggable ? 'inline-flex' : 'none'")
        ->toContain("touchAction: 'none'");
});

test('card modal becomes a bottom sheet on mobile', function () {
    $content = file_get_contents(resource_path('js/components/hoopify/CardModal.tsx'));

    expect($content)
        ->toContain('useIsMobile')
        ->toContain("alignItems: isMobile ? 'flex-end' : 'center'")
        ->toContain("borderRadius: '16px 16px 0 0'")
        ->toContain("maxHeight: '92dvh'")
        ->toContain("animation: 'sheetIn var(--dur-base) var(--ease-out) both'")
        ->toContain('boxSize={isMobile ? 44 : 32}')
        ->toContain('env(safe-area-inset-bottom)');
});

test('the sheet animation and horizontal scroller utilities exist', function () {
    expect(file_get_contents(resource_path('css/app.css')))
        ->toContain('@keyframes sheetIn')
        ->toContain('.hscroll')
        ->toContain('scrollbar-width: none');
});
