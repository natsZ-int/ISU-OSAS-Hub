@php
    $filters = $filters ?? [];

    $currentTab = request()->routeIs('admin.analytics.scholarships') ? 'scholarships'
        : (request()->routeIs('admin.analytics.guidance') ? 'guidance'
            : (request()->routeIs('admin.analytics.services') ? 'services' : 'overview'));

    $tabs = [
        ['key' => 'overview',   'label' => 'Overview',          'href' => route('admin.analytics.index', $filters)],
        ['key' => 'scholarships','label' => 'Scholarships',     'href' => route('admin.analytics.scholarships', $filters)],
        ['key' => 'guidance',   'label' => 'Guidance',          'href' => route('admin.analytics.guidance', $filters)],
        ['key' => 'services',   'label' => 'Student Services',  'href' => route('admin.analytics.services', $filters)],
    ];

    $tabs = array_map(fn ($tab) => [
        'label'  => $tab['label'],
        'href'   => $tab['href'],
        'active' => $tab['key'] === $currentTab,
    ], $tabs);
@endphp

<x-section-tabs :tabs="$tabs" />
