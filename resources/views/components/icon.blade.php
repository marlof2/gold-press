@props(['name'=>'grid'])
@php($paths = [
'grid'=>'M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z',
'orders'=>'M8 3H5a2 2 0 0 0-2 2v15a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V5a2 2 0 0 0-2-2h-3 M9 2h6v4H9z M7 11h10 M7 16h7',
'users'=>'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M22 21v-2a4 4 0 0 0-3-3.87 M9 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8 M16 3a4 4 0 0 1 0 8',
'status'=>'M9 5h12 M9 12h12 M9 19h12 M3 5h.01 M3 12h.01 M3 19h.01',
'shield'=>'M12 3l8 3v6c0 4.5-3.2 7.9-8 9-4.8-1.1-8-4.5-8-9V6z M9 12l2 2 4-4',
'building'=>'M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16 M16 9h2a2 2 0 0 1 2 2v10 M2 21h20 M8 7h4 M8 11h4 M8 15h4',
'box'=>'M21 8l-9-5-9 5 9 5 9-5z M3 8v8l9 5 9-5V8 M12 13v8',
'trash'=>'M3 6h18 M8 6V4h8v2 M19 6l-1 15H6L5 6 M10 11v6 M14 11v6',
'copy'=>'M9 9h11v11H9z M5 15H4V4h11v1',
'plus'=>'M12 5v14 M5 12h14', 'search'=>'M21 21l-5-5 M10 3a7 7 0 1 0 0 14 7 7 0 0 0 0-14',
'menu'=>'M3 6h18 M3 12h18 M3 18h18', 'close'=>'M6 6l12 12 M6 18L18 6',
'logout'=>'M9 21H4V3h5 M16 17l5-5-5-5 M8 12h13',
'user'=>'M20 21v-2a7 7 0 0 0-14 0v2 M13 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8',
'arrow'=>'M5 12h14 M13 6l6 6-6 6', 'edit'=>'M12 20H4v-8 M14 5l5 5 M10 19l-5 1 1-5L17 4a2 2 0 0 1 3 3z',
'check'=>'M5 12l4 4L19 6', 'clock'=>'M12 8v4l3 2 M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20',
'download'=>'M12 3v12 M7 10l5 5 5-5 M5 16v5h14v-5',
'alert'=>'M12 8v5 M12 17h.01 M10 3L2 19a1 1 0 0 0 1 2h18a1 1 0 0 0 1-2L14 3a2 2 0 0 0-4 0',
'lock'=>'M5 11h14v10H5z M8 11V7a4 4 0 0 1 8 0v4',
])
<svg {{ $attributes->merge(['class'=>'icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $paths[$name] ?? $paths['grid'] }}"/></svg>
