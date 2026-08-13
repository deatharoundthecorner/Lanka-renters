<?php

if (!function_exists('customer_icon')) {
    /**
     * Returns a small, local, decorative SVG icon from a fixed allow-list.
     */
    function customer_icon(string $name): string
    {
        $paths = [
            'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
            'shield' => '<path d="M12 3 5 6v5c0 4.6 2.9 8 7 10 4.1-2 7-5.4 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-4"/>',
            'car' => '<path d="m5 11 1.5-4h11l1.5 4"/><path d="M3 12h18v6H3z"/><path d="M6 18v2M18 18v2M7 15h.01M17 15h.01"/>',
            'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M8 14h.01M12 14h.01M16 14h.01"/>',
            'clipboard' => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V2h6v2M8 10h8M8 14h8M8 18h5"/>',
            'credit-card' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h3"/>',
            'chat' => '<path d="M21 12a8 8 0 0 1-9 8 9 9 0 0 1-4-.9L3 21l1.7-4A8 8 0 1 1 21 12Z"/>',
            'alert' => '<path d="M12 3 2.8 20h18.4L12 3Z"/><path d="M12 9v5M12 17h.01"/>',
            'switch' => '<path d="M4 7h13M14 4l3 3-3 3M20 17H7M10 14l-3 3 3 3"/>',
            'return' => '<path d="M9 7 4 12l5 5M4 12h10a6 6 0 0 1 6 6"/>',
            'star' => '<path d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6-5.4-2.9-5.4 2.9 1-6-4.4-4.3 6.1-.9L12 3Z"/>',
            'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
            'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
            'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
            'close' => '<path d="m6 6 12 12M18 6 6 18"/>',
            'chevron-down' => '<path d="m7 10 5 5 5-5"/>',
            'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
            'check' => '<path d="m5 12 4 4L19 6"/>',
            'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
            'arrow-right' => '<path d="M5 12h14M14 7l5 5-5 5"/>',
            'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/>',
            'logout' => '<path d="M10 4H5v16h5M14 8l4 4-4 4M18 12H9"/>',
        ];

        $path = $paths[$name] ?? $paths['info'];

        return '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">'
            . $path
            . '</svg>';
    }
}
