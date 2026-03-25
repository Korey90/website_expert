<x-filament-panels::page>
    @php
        // All permissions in DB, keyed by name
        $allPermissions = \Spatie\Permission\Models\Permission::orderBy('name')->get()->keyBy('name');

        // Permissions this role currently has
        $granted = $record->permissions->pluck('name')->flip(); // flip for O(1) lookup

        // ── Group definitions ─────────────────────────────────────────────────
        // Each entry: ['label', 'icon', 'dot' (tailwind color class), 'suffixes' (array of name suffixes)]
        $groups = [
            'CRM' => [
                'label'    => 'CRM',
                'icon'     => 'heroicon-o-building-office-2',
                'dot'      => 'bg-indigo-500',
                'suffixes' => ['clients', 'leads', 'contracts'],
            ],
            'Finance' => [
                'label'    => 'Finance',
                'icon'     => 'heroicon-o-banknotes',
                'dot'      => 'bg-green-500',
                'suffixes' => ['quotes', 'invoices'],
            ],
            'Projects' => [
                'label'    => 'Projects',
                'icon'     => 'heroicon-o-briefcase',
                'dot'      => 'bg-blue-500',
                'suffixes' => ['projects'],
            ],
            'Templates' => [
                'label'    => 'Templates',
                'icon'     => 'heroicon-o-document-duplicate',
                'dot'      => 'bg-purple-500',
                'suffixes' => ['contract_templates', 'email_templates', 'sms_templates'],
            ],
            'Automations' => [
                'label'    => 'Automations',
                'icon'     => 'heroicon-o-bolt',
                'dot'      => 'bg-yellow-500',
                'suffixes' => ['automations'],
            ],
            'Website CMS' => [
                'label'    => 'Website CMS',
                'icon'     => 'heroicon-o-globe-alt',
                'dot'      => 'bg-cyan-500',
                'suffixes' => ['pages', 'site_sections'],
            ],
            'Users' => [
                'label'    => 'Users',
                'icon'     => 'heroicon-o-users',
                'dot'      => 'bg-orange-500',
                'suffixes' => ['users'],
            ],
            'Reports' => [
                'label'    => 'Reports',
                'icon'     => 'heroicon-o-chart-bar',
                'dot'      => 'bg-teal-500',
                'suffixes' => ['reports'],
            ],
            'System' => [
                'label'    => 'System',
                'icon'     => 'heroicon-o-cog-6-tooth',
                'dot'      => 'bg-rose-500',
                'suffixes' => ['settings', 'roles', 'pipeline', 'calculator', 'project_templates'],
            ],
        ];

        // For each group, collect the permissions that belong to it
        $grouped = [];
        $seen    = [];

        foreach ($groups as $key => $group) {
            $perms = [];
            foreach ($allPermissions as $name => $perm) {
                foreach ($group['suffixes'] as $suffix) {
                    if (str_ends_with($name, '_' . $suffix) || $name === $suffix) {
                        $perms[] = $name;
                        $seen[$name] = true;
                        break;
                    }
                }
            }
            // Sort: view → create → edit → delete → manage → rest
            usort($perms, function ($a, $b) {
                $order = ['view' => 0, 'create' => 1, 'edit' => 2, 'delete' => 3, 'export' => 4, 'manage' => 5];
                $prefixA = explode('_', $a)[0] ?? '';
                $prefixB = explode('_', $b)[0] ?? '';
                $ia = $order[$prefixA] ?? 9;
                $ib = $order[$prefixB] ?? 9;
                return $ia !== $ib ? $ia - $ib : strcmp($a, $b);
            });
            $grouped[$key] = $perms;
        }

        // Catch-all: permissions not matched by any group
        $ungrouped = $allPermissions->keys()->filter(fn ($n) => ! isset($seen[$n]))->values()->all();
        if (! empty($ungrouped)) {
            $grouped['Other'] = $ungrouped;
            $groups['Other'] = [
                'label'    => 'Other',
                'icon'     => 'heroicon-o-ellipsis-horizontal-circle',
                'dot'      => 'bg-gray-400',
                'suffixes' => [],
            ];
        }

        // Tab list: only groups that have at least one permission in DB
        $tabs = array_filter(array_keys($grouped), fn ($k) => ! empty($grouped[$k]));
        $tabs = array_values($tabs);

        // Count granted per group
        $grantedCount = [];
        foreach ($tabs as $key) {
            $grantedCount[$key] = count(array_filter($grouped[$key], fn ($n) => isset($granted[$n])));
        }

        // Total stats
        $totalGranted = $granted->count();
        $totalAll     = $allPermissions->count();

        // Users with this role
        $users = $record->users()->with([])->get();
    @endphp

    <div class="space-y-6">

        {{-- ── Role header card ─────────────────────────────────────────────── --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-5">

                {{-- Name + guard --}}
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900/40">
                        <x-heroicon-m-shield-check class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <div>
                        <h2 class="text-xl font-bold capitalize text-gray-900 dark:text-white">{{ $record->name }}</h2>
                        <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                            guard: {{ $record->guard_name }}
                        </span>
                    </div>
                </div>

                {{-- Stats --}}
                <div class="flex items-center gap-6">
                    <div class="text-center">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalGranted }}</p>
                        <p class="text-xs text-gray-400">of {{ $totalAll }} permissions</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $users->count() }}</p>
                        <p class="text-xs text-gray-400">{{ Str::plural('user', $users->count()) }}</p>
                    </div>
                </div>

            </div>

            {{-- Progress bar --}}
            @if($totalAll > 0)
            <div class="px-6 pb-5">
                <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                    <div class="h-full rounded-full bg-indigo-500 transition-all"
                         style="width: {{ round(($totalGranted / $totalAll) * 100) }}%"></div>
                </div>
                <p class="mt-1 text-right text-xs text-gray-400">
                    {{ round(($totalGranted / $totalAll) * 100) }}% of all permissions granted
                </p>
            </div>
            @endif
        </div>

        {{-- ── Permissions card with Alpine tabs ────────────────────────────── --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
             x-data="{ tab: '{{ $tabs[0] ?? '' }}' }">

            {{-- Tab bar --}}
            <div class="overflow-x-auto border-b border-gray-100 dark:border-gray-800">
                <nav class="flex min-w-max gap-0 px-4">
                    @foreach($tabs as $key)
                        @php
                            $grp       = $groups[$key];
                            $total     = count($grouped[$key]);
                            $grCnt     = $grantedCount[$key];
                            $hasAny    = $grCnt > 0;
                        @endphp
                        <button type="button"
                                @click="tab = '{{ $key }}'"
                                :class="tab === '{{ $key }}'
                                    ? 'border-b-2 border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                    : 'border-b-2 border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                                class="flex items-center gap-2 whitespace-nowrap px-4 py-3.5 text-sm font-medium transition-colors">
                            <x-dynamic-component :component="$grp['icon']" class="h-4 w-4 flex-shrink-0" />
                            {{ $grp['label'] }}
                            <span :class="tab === '{{ $key }}'
                                      ? 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400'
                                      : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400'"
                                  class="rounded-full px-2 py-0.5 text-xs font-semibold transition-colors">
                                {{ $grCnt }}/{{ $total }}
                            </span>
                        </button>
                    @endforeach
                </nav>
            </div>

            {{-- Tab panels --}}
            @foreach($tabs as $key)
                @php $grp = $groups[$key]; @endphp
                <div x-show="tab === '{{ $key }}'" x-cloak>
                    @if(! empty($grouped[$key]))
                        @php
                            // Sub-group by resource (e.g. clients, leads) within this tab
                            $subGroups = [];
                            foreach ($grouped[$key] as $permName) {
                                // resource = everything after first verb prefix
                                $parts    = explode('_', $permName, 2);
                                $resource = count($parts) > 1 ? $parts[1] : $permName;
                                $subGroups[$resource][] = $permName;
                            }
                        @endphp

                        <div class="divide-y divide-gray-50 dark:divide-gray-800/60">
                            @foreach($subGroups as $resource => $perms)
                                <div class="px-6 py-5">
                                    <h4 class="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $grp['dot'] }}"></span>
                                        {{ ucwords(str_replace('_', ' ', $resource)) }}
                                    </h4>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($perms as $permName)
                                            @php
                                                $isGranted = isset($granted[$permName]);
                                                $verb      = explode('_', $permName)[0];
                                                $verbConfig = match($verb) {
                                                    'view'    => ['icon' => 'heroicon-m-eye',          'granted' => 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:ring-blue-700/30'],
                                                    'create'  => ['icon' => 'heroicon-m-plus-circle',   'granted' => 'bg-green-50 text-green-700 ring-green-200 dark:bg-green-900/20 dark:text-green-400 dark:ring-green-700/30'],
                                                    'edit'    => ['icon' => 'heroicon-m-pencil-square',  'granted' => 'bg-yellow-50 text-yellow-700 ring-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400 dark:ring-yellow-700/30'],
                                                    'delete'  => ['icon' => 'heroicon-m-trash',          'granted' => 'bg-red-50 text-red-700 ring-red-200 dark:bg-red-900/20 dark:text-red-400 dark:ring-red-700/30'],
                                                    'export'  => ['icon' => 'heroicon-m-arrow-down-tray','granted' => 'bg-teal-50 text-teal-700 ring-teal-200 dark:bg-teal-900/20 dark:text-teal-400 dark:ring-teal-700/30'],
                                                    'manage'  => ['icon' => 'heroicon-m-cog-6-tooth',    'granted' => 'bg-purple-50 text-purple-700 ring-purple-200 dark:bg-purple-900/20 dark:text-purple-400 dark:ring-purple-700/30'],
                                                    default   => ['icon' => 'heroicon-m-key',             'granted' => 'bg-gray-50 text-gray-700 ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700'],
                                                };
                                            @endphp
                                            <span class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium ring-1 transition-all
                                                {{ $isGranted
                                                    ? $verbConfig['granted']
                                                    : 'bg-gray-50 text-gray-300 ring-gray-100 dark:bg-gray-800/30 dark:text-gray-600 dark:ring-gray-700/40' }}">
                                                @if($isGranted)
                                                    <x-dynamic-component :component="$verbConfig['icon']" class="h-3.5 w-3.5 flex-shrink-0" />
                                                @else
                                                    <x-heroicon-m-minus-small class="h-3.5 w-3.5 flex-shrink-0" />
                                                @endif
                                                {{ $permName }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="py-12 text-center">
                            <x-heroicon-o-shield-exclamation class="mx-auto mb-2 h-8 w-8 text-gray-300 dark:text-gray-600" />
                            <p class="text-sm text-gray-400">No permissions in this category.</p>
                        </div>
                    @endif
                </div>
            @endforeach

        </div>

        {{-- ── Users with this role ─────────────────────────────────────────── --}}
        @if($users->count())
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <div class="flex items-center gap-2 border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                <x-heroicon-m-users class="h-4 w-4 text-indigo-500" />
                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Users with this role</h2>
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500 dark:bg-gray-800">{{ $users->count() }}</span>
            </div>
            <div class="flex flex-wrap gap-3 px-6 py-4">
                @foreach($users as $user)
                    <div class="flex items-center gap-2 rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 dark:border-gray-800 dark:bg-gray-800/40">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400">
                            {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                        </span>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->name }}</p>
                            <p class="text-xs text-gray-400">{{ $user->email }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</x-filament-panels::page>
