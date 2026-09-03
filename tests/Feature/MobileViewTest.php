<?php

test('the app agrees on a single mobile breakpoint', function () {
    $content = file_get_contents(resource_path('js/hooks/use-is-mobile.ts'));

    expect($content)
        ->toContain("export const MOBILE_QUERY = '(max-width: 760px)'")
        ->toContain('export function useIsMobile')
        ->toContain('addEventListener')
        ->toContain('removeEventListener');
});

test('the sidebar becomes an off-canvas drawer on mobile', function () {
    $content = file_get_contents(resource_path('js/components/circles/Sidebar.tsx'));

    expect($content)
        ->toContain('useIsMobile')
        ->toContain("transform: open ? 'none' : 'translateX(-101%)'")
        ->toContain("position: 'fixed'")
        ->toContain('var(--overlay)')
        ->toContain('Close menu');
});

test('the mobile drawer locks page scroll and closes on escape', function () {
    $content = file_get_contents(resource_path('js/components/circles/Sidebar.tsx'));

    expect($content)
        ->toContain("document.body.style.overflow = 'hidden'")
        ->toContain("event.key === 'Escape'");
});

test('the top bar exposes a menu button on mobile', function () {
    $content = file_get_contents(resource_path('js/components/circles/TopBar.tsx'));

    expect($content)
        ->toContain('useIsMobile')
        ->toContain('useMobileMenu')
        ->toContain('Open menu')
        ->toContain('mobileMenu.openMenu')
        ->toContain('crumbs.slice(-1)');
});

test('the layout owns the drawer state and shares it with the top bar', function () {
    $content = file_get_contents(resource_path('js/Layouts/AuthenticatedLayout.tsx'));

    expect($content)
        ->toContain('MobileMenuProvider')
        ->toContain('menuOpen')
        ->toContain('onClose={closeMenu}')
        ->toContain("height: '100dvh'");
});

test('the drawer never survives a navigation or a resize back to desktop', function () {
    $content = file_get_contents(resource_path('js/Layouts/AuthenticatedLayout.tsx'));

    expect($content)
        ->toContain('if (!isMobile) setMenuOpen(false)')
        ->toContain('}, [url]);');
});

test('the home page reflows for mobile', function () {
    $content = file_get_contents(resource_path('js/Pages/Home.tsx'));

    expect($content)
        ->toContain('useIsMobile')
        ->toContain("gridTemplateColumns: isMobile ? '1fr 1fr' : 'repeat(4, 1fr)'")
        ->toContain("'repeat(auto-fill, minmax(min(100%, 248px), 1fr))'")
        ->toContain("'26px 18px calc(56px + env(safe-area-inset-bottom))'")
        ->toContain("isMobile ? 'clamp(30px, 9vw, 38px)' : 46");
});

test('the document opts into the safe area and hides touch scrollbars', function () {
    expect(file_get_contents(resource_path('views/app.blade.php')))
        ->toContain('viewport-fit=cover');

    expect(file_get_contents(resource_path('css/app.css')))
        ->toContain('overscroll-behavior-y: none')
        ->toContain('@media (max-width: 760px)');
});
