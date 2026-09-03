<?php

test('the score colour steps from green through amber to red', function () {
    $content = file_get_contents(resource_path('js/components/kit/Score.tsx'));

    expect($content)
        ->toContain('export function scoreColor(value: number): string {')
        ->toContain("    if (value >= 3.5) {\n        return 'var(--accent)';\n    }")
        ->toContain("    if (value >= 2) {\n        return 'var(--warning)';\n    }")
        ->toContain("    return 'var(--critical)';");
});

test('both numeric score readouts take their colour from the band', function () {
    expect(file_get_contents(resource_path('js/components/kit/Score.tsx')))
        ->toContain('color: scoreColor(value),');

    expect(file_get_contents(resource_path('js/components/kit/StarRating.tsx')))
        ->toContain("import { scoreColor } from './Score';")
        ->toContain("color: hasValue ? scoreColor(numeric) : 'var(--fg3)',");
});

test('the score band tokens are defined for both themes', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)
        ->toContain('--warning: #e0a124')
        ->toContain('--critical: #da3b2a')
        ->toContain('--accent: var(--aqua-500)');
});
