<?php

test('shadcn components.json exists with correct configuration', function () {
    $path = base_path('components.json');

    expect(file_exists($path))->toBeTrue();

    $config = json_decode(file_get_contents($path), true);

    expect($config)
        ->toHaveKey('style', 'new-york')
        ->toHaveKey('tsx', true)
        ->and($config['tailwind']['baseColor'])->toBe('zinc')
        ->and($config['tailwind']['cssVariables'])->toBeTrue()
        ->and($config['aliases']['utils'])->toBe('@/lib/utils')
        ->and($config['aliases']['ui'])->toBe('@/components/ui');
});

test('cn utility exists at resources/js/lib/utils.ts', function () {
    $path = resource_path('js/lib/utils.ts');

    expect(file_exists($path))->toBeTrue();

    $content = file_get_contents($path);

    expect($content)
        ->toContain('clsx')
        ->toContain('twMerge')
        ->toContain('export function cn');
});

test('Circles design tokens are configured for light and dark mode', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)
        ->toContain(':root')
        ->toContain('data-theme=\'dark\'')
        ->toContain('--bg')
        ->toContain('--fg1')
        ->toContain('--accent')
        ->toContain('--surface')
        ->toContain('--line')
        ->toContain('--critical');
});

test('shadcn button component still exists for legacy dialogs', function () {
    $path = resource_path('js/components/ui/button.tsx');

    expect(file_exists($path))->toBeTrue();

    $content = file_get_contents($path);

    expect($content)
        ->toContain('export')
        ->toContain('Button')
        ->toContain('buttonVariants');
});
