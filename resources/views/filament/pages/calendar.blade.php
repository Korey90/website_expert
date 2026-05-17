<x-filament-panels::page>
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    @endpush

    @php $googleConnected = $this->getGoogleConnected(); @endphp

    <div
        x-data="calendarPage()"
        x-init="init()"
        @calendar-refresh.window="refetch()"
        class="space-y-5"
    >

        {{-- ── Stats bar ─────────────────────────────────────────── --}}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4" id="cal-stats">
            <template x-if="!stats">
                <template x-for="i in [1,2,3,4]" :key="i">
                    <div class="h-16 animate-pulse rounded-xl border border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-800"></div>
                </template>
            </template>
            <template x-if="stats">
                <template x-for="s in stats" :key="s.label">
                    <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-xs dark:border-gray-700 dark:bg-gray-900">
                        <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg" :class="s.bg">
                            <svg class="h-5 w-5" :class="s.ic" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="s.path" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="s.label"></p>
                            <p class="text-xl font-bold leading-tight text-gray-900 dark:text-white" x-text="s.value"></p>
                        </div>
                    </div>
                </template>
            </template>
        </div>

        {{-- ── Calendar card ──────────────────────────────────────── --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs dark:border-gray-700 dark:bg-gray-900">

            {{-- Legend --}}
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2 border-b border-gray-100 px-5 py-3 dark:border-gray-800">
                <template x-for="leg in legend" :key="leg.label">
                    <span class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                        <span class="inline-block h-2.5 w-2.5 flex-shrink-0 rounded-full" :style="'background:'+leg.color"></span>
                        <span x-text="leg.label"></span>
                    </span>
                </template>
                <span class="ml-auto flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500">
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5"/></svg>
                    Click day to add · Drag to reschedule
                </span>
            </div>

            {{-- Loading --}}
            <div x-show="loading" class="flex items-center justify-center gap-2 py-24 text-sm text-gray-400">
                <svg class="h-5 w-5 animate-spin text-indigo-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                Loading…
            </div>

            <div x-show="!loading" class="p-4">
                <div id="calendar"></div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════ --}}
        {{-- QUICK CREATE MODAL                                         --}}
        {{-- ══════════════════════════════════════════════════════════ --}}
        <div
            x-show="cm.open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            style="display:none"
            @keydown.escape.window="cm.open=false"
        >
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="cm.open=false"></div>
            <div
                x-show="cm.open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative z-10 w-full max-w-md overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-900"
                @click.stop
            >
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">New Event</h3>
                    <button @click="cm.open=false" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="space-y-4 px-6 py-5">
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Title <span class="text-red-500">*</span></label>
                        <input x-model="cm.title" x-ref="cmTitle" type="text" placeholder="Event title…" maxlength="255"
                            @keydown.enter="submitCreate()"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500" />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Type</label>
                        <div class="grid grid-cols-5 gap-2">
                            <template x-for="t in eventTypes" :key="t.value">
                                <button type="button" @click="cm.type=t.value"
                                    class="flex flex-col items-center gap-1.5 rounded-xl border-2 px-1 py-2.5 transition"
                                    :class="cm.type===t.value ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30' : 'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-gray-600'">
                                    <span class="inline-block h-3 w-3 rounded-full" :style="'background:'+t.color"></span>
                                    <span class="text-xs font-medium"
                                        :class="cm.type===t.value ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400'"
                                        x-text="t.label"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <div class="flex-1">
                            <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Date &amp; time</label>
                            <input x-model="cm.startsAt" :type="cm.allDay ? 'date' : 'datetime-local'"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
                        </div>
                        <div class="flex flex-col justify-end pb-1.5">
                            <label class="flex cursor-pointer items-center gap-2 text-xs text-gray-600 dark:text-gray-400 select-none">
                                <input type="checkbox" x-model="cm.allDay" class="rounded text-indigo-600 focus:ring-indigo-500" />
                                All day
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-gray-100 px-6 py-4 dark:border-gray-800">
                    <button @click="cm.open=false"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                        Cancel
                    </button>
                    <button @click="submitCreate()" :disabled="!cm.title.trim()"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-40">
                        Create Event
                    </button>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════ --}}
        {{-- EVENT DETAIL MODAL                                         --}}
        {{-- ══════════════════════════════════════════════════════════ --}}
        <div
            x-show="dm.open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            style="display:none"
            @keydown.escape.window="dm.open=false"
        >
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="dm.open=false"></div>
            <div
                x-show="dm.open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative z-10 w-full max-w-sm overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-900"
                @click.stop
            >
                <div class="h-1.5 w-full" :style="'background:'+dm.color"></div>

                <div class="px-6 py-5">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <span class="mb-2 inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize text-white" :style="'background:'+dm.color" x-text="dm.type"></span>
                            <h3 class="truncate text-base font-bold text-gray-900 dark:text-white" x-text="dm.title"></h3>
                        </div>
                        <button @click="dm.open=false" class="mt-1 flex-shrink-0 rounded-lg p-1 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <div class="flex items-center gap-2">
                            <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span x-text="dm.dateFormatted"></span>
                        </div>
                        <div class="flex items-center gap-2" x-show="dm.status">
                            <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span x-text="dm.status" class="capitalize"></span>
                        </div>
                        <div x-show="dm.description" class="mt-1 rounded-lg bg-gray-50 px-3 py-2.5 text-xs dark:bg-gray-800" x-text="dm.description"></div>
                        <div x-show="dm.synced" class="flex items-center gap-1.5 text-xs text-green-600 dark:text-green-400">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/></svg>
                            Synced to Google Calendar
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-2 border-t border-gray-100 px-5 py-3 dark:border-gray-800">
                    <button x-show="!dm.virtual" @click="confirmDelete()"
                        class="flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 transition">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Delete
                    </button>
                    <span x-show="dm.virtual" class="text-xs text-gray-400 dark:text-gray-500 italic">Auto-generated from project / invoice</span>

                    <div class="flex items-center gap-2">
                        @if($googleConnected)
                        <button x-show="!dm.virtual && !dm.synced" @click="syncGoogle()"
                            class="flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800 transition">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/></svg>
                            Sync
                        </button>
                        @endif
                        <a :href="dm.editUrl" class="flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-medium text-white hover:bg-indigo-700 transition">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Open
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
    function calendarPage() {
        return {
            calendar : null,
            loading  : true,
            stats    : null,

            legend: [
                { color:'#3b82f6', label:'Meeting' },
                { color:'#10b981', label:'Call / Lead close' },
                { color:'#ef4444', label:'Deadline' },
                { color:'#f59e0b', label:'Reminder / Invoice' },
                { color:'#8b5cf6', label:'Task / Contract' },
            ],

            eventTypes: [
                { value:'meeting',  label:'Meet',   color:'#3b82f6' },
                { value:'call',     label:'Call',   color:'#10b981' },
                { value:'deadline', label:'Dead.',  color:'#ef4444' },
                { value:'reminder', label:'Remind', color:'#f59e0b' },
                { value:'task',     label:'Task',   color:'#8b5cf6' },
            ],

            cm: { open:false, title:'', type:'meeting', startsAt:'', allDay:false },
            dm: { open:false, id:null, title:'', type:'', color:'#6b7280',
                  dateFormatted:'', description:'', status:'',
                  synced:false, virtual:false, editUrl:'#' },

            init() {
                const self = this;
                const el   = document.getElementById('calendar');

                this.calendar = new FullCalendar.Calendar(el, {
                    initialView : 'dayGridMonth',
                    firstDay    : 1,
                    nowIndicator: true,
                    dayMaxEvents: 3,
                    editable    : true,
                    height      : 'auto',

                    headerToolbar: {
                        left  : 'prev,next today',
                        center: 'title',
                        right : 'dayGridMonth,timeGridWeek,listWeek',
                    },
                    buttonText: { today:'Today', month:'Month', week:'Week', list:'List' },

                    events(info, ok, fail) {
                        self.loading = true;
                        const u = new URL('/admin/calendar/events', location.origin);
                        u.searchParams.set('start', info.startStr);
                        u.searchParams.set('end',   info.endStr);
                        fetch(u, { headers:{'X-Requested-With':'XMLHttpRequest'} })
                            .then(r => r.json())
                            .then(data => { self.loading=false; self.buildStats(data); ok(data); })
                            .catch(e  => { self.loading=false; fail(e); });
                    },

                    eventAllow(drop, dragged) {
                        return !dragged.extendedProps?.virtual;
                    },

                    eventDidMount(info) {
                        const v = info.event.extendedProps?.virtual;
                        info.el.style.cssText += 'border-radius:6px;border:none;font-size:.72rem;font-weight:500;cursor:pointer;transition:opacity .15s,transform .1s;';
                        if (v) info.el.style.opacity = '0.8';
                        info.el.title = info.event.extendedProps?.description || info.event.title;
                        info.el.addEventListener('mouseenter', () => { info.el.style.opacity='.75'; info.el.style.transform='scale(1.015)'; });
                        info.el.addEventListener('mouseleave', () => { info.el.style.opacity=v?'.8':'1'; info.el.style.transform=''; });
                    },

                    eventContent(arg) {
                        const type   = arg.event.extendedProps?.type || 'meeting';
                        const icons  = { meeting:'●', call:'●', deadline:'⚑', reminder:'◆', task:'✔' };
                        const cloud  = arg.event.extendedProps?.synced ? '<span style="opacity:.6;margin-left:3px;font-size:.6rem">☁</span>' : '';
                        const link   = arg.event.extendedProps?.virtual ? '<span style="opacity:.5;margin-left:2px;font-size:.6rem">↗</span>' : '';
                        return { html:
                            `<div style="display:flex;align-items:center;gap:3px;padding:1px 5px;overflow:hidden;max-width:100%">
                                <span style="flex-shrink:0;font-size:.5rem;opacity:.7">${icons[type]||'●'}</span>
                                <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${arg.event.title}</span>
                                ${cloud}${link}
                             </div>`
                        };
                    },

                    moreLinkContent(arg) { return `+${arg.num} more`; },

                    dateClick(info) {
                        self.openCreateModal(info.dateStr, info.allDay);
                    },

                    eventClick(info) {
                        info.jsEvent.preventDefault();
                        self.openDetailModal(info.event);
                    },

                    eventDrop(info) {
                        const id = parseInt(info.event.id);
                        if (isNaN(id)) { info.revert(); return; }
                        self.$wire.moveCalendarEvent(id, info.event.startStr, info.event.endStr||null)
                            .catch(() => info.revert());
                    },

                    eventResize(info) {
                        const id = parseInt(info.event.id);
                        if (isNaN(id)) { info.revert(); return; }
                        self.$wire.moveCalendarEvent(id, info.event.startStr, info.event.endStr||null)
                            .catch(() => info.revert());
                    },
                });

                this.calendar.render();
            },

            refetch() { this.calendar?.refetchEvents(); },

            buildStats(events) {
                const now = new Date();
                const ms  = new Date(now.getFullYear(), now.getMonth(), 1);
                const me  = new Date(now.getFullYear(), now.getMonth()+1, 0);
                const w7  = new Date(+now + 7*864e5);
                const tm  = events.filter(e => { const d=new Date(e.start); return d>=ms&&d<=me; }).length;
                const n7  = events.filter(e => { const d=new Date(e.start); return d>=now&&d<=w7; }).length;
                const dl  = events.filter(e => e.extendedProps?.type==='deadline').length;
                const sy  = events.filter(e => e.extendedProps?.synced).length;
                this.stats = [
                    { label:'This month', value:tm, bg:'bg-blue-50 dark:bg-blue-900/30',   ic:'text-blue-600 dark:text-blue-400',   path:'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
                    { label:'Next 7 days', value:n7, bg:'bg-amber-50 dark:bg-amber-900/30', ic:'text-amber-600 dark:text-amber-400', path:'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
                    { label:'Deadlines',   value:dl, bg:'bg-red-50 dark:bg-red-900/30',     ic:'text-red-600 dark:text-red-400',     path:'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z' },
                    { label:'Google sync', value:sy, bg:'bg-green-50 dark:bg-green-900/30', ic:'text-green-600 dark:text-green-400', path:'M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z' },
                ];
            },

            openCreateModal(dateStr, allDay) {
                const dt = allDay ? dateStr : (dateStr.length===10 ? dateStr+'T09:00' : dateStr.substring(0,16));
                this.cm = { open:true, title:'', type:'meeting', startsAt:dt, allDay };
                this.$nextTick(() => this.$refs.cmTitle?.focus());
            },

            async submitCreate() {
                if (!this.cm.title.trim()) return;
                await this.$wire.quickCreate(this.cm.title.trim(), this.cm.type, this.cm.startsAt, this.cm.allDay);
                this.cm.open = false;
            },

            openDetailModal(ev) {
                const p  = ev.extendedProps || {};
                const fmt = dt => {
                    if (!dt) return '—';
                    return new Date(dt).toLocaleString('en-GB', { day:'numeric',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit' });
                };
                this.dm = {
                    open         : true,
                    id           : parseInt(ev.id) || null,
                    title        : ev.title,
                    type         : p.type || 'event',
                    color        : ev.backgroundColor || '#6b7280',
                    dateFormatted: fmt(ev.start) + (ev.end ? ' → '+fmt(ev.end) : ''),
                    description  : p.description || '',
                    status       : p.status || '',
                    synced       : p.synced || false,
                    virtual      : p.virtual || false,
                    editUrl      : p.editUrl || '#',
                };
            },

            confirmDelete() {
                if (!confirm(`Delete "${this.dm.title}"?`)) return;
                this.$wire.deleteCalendarEvent(this.dm.id);
                this.dm.open = false;
            },

            syncGoogle() {
                this.$wire.syncEventToGoogle(this.dm.id);
                this.dm.open = false;
            },
        };
    }
    </script>

    <style>
    #calendar .fc { font-family:inherit; }
    #calendar .fc .fc-toolbar-title { font-size:1.05rem; font-weight:700; letter-spacing:-.01em; }
    #calendar .fc .fc-col-header-cell-cushion { font-size:.7rem; font-weight:600; text-transform:uppercase; letter-spacing:.06em; padding:8px 4px; text-decoration:none!important; color:#6b7280; }
    #calendar .fc .fc-daygrid-day-number { font-size:.8rem; font-weight:500; padding:6px 8px; text-decoration:none!important; color:#374151; }

    #calendar .fc .fc-button { font-size:.8rem; font-weight:500; padding:5px 13px; border-radius:8px; border:1px solid #d1d5db; background:#f9fafb; color:#374151; box-shadow:none; transition:background .15s,border-color .15s; text-transform:capitalize; }
    #calendar .fc .fc-button:hover { background:#f3f4f6; border-color:#9ca3af; }
    #calendar .fc .fc-button:focus { outline:none; box-shadow:none; }
    #calendar .fc .fc-button-primary:not(:disabled).fc-button-active,
    #calendar .fc .fc-button-primary:not(:disabled):active { background:#6366f1; border-color:#6366f1; color:#fff; }
    #calendar .fc .fc-button-group .fc-button { border-radius:0; }
    #calendar .fc .fc-button-group .fc-button:first-child { border-radius:8px 0 0 8px; }
    #calendar .fc .fc-button-group .fc-button:last-child  { border-radius:0 8px 8px 0; }

    #calendar .fc .fc-day-today { background:#eef2ff!important; }
    #calendar .fc .fc-day-today .fc-daygrid-day-number { color:#6366f1; font-weight:700; }
    #calendar .fc .fc-daygrid-day:not(.fc-day-other):hover { background:#f5f3ff; cursor:pointer; }

    #calendar .fc-theme-standard td, #calendar .fc-theme-standard th { border-color:#e5e7eb; }
    #calendar .fc-theme-standard .fc-scrollgrid { border-color:transparent; }
    #calendar .fc .fc-daygrid-more-link { font-size:.7rem; color:#6366f1; font-weight:600; text-decoration:none; }
    #calendar .fc .fc-list-day-cushion { font-size:.72rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; padding:8px 14px; background:#f9fafb; }
    #calendar .fc .fc-list-event-title a { text-decoration:none; color:inherit; }
    #calendar .fc .fc-list-event:hover td { background:#f5f3ff; }
    #calendar .fc .fc-popover { border-radius:12px; box-shadow:0 8px 30px rgba(0,0,0,.12); border-color:#e5e7eb; overflow:hidden; }
    #calendar .fc .fc-popover-header { padding:8px 12px; font-size:.8rem; font-weight:600; background:#f9fafb; }

    .dark #calendar .fc { color:#d1d5db; }
    .dark #calendar .fc .fc-toolbar-title { color:#f9fafb; }
    .dark #calendar .fc .fc-col-header-cell-cushion { color:#9ca3af; }
    .dark #calendar .fc .fc-daygrid-day-number { color:#9ca3af; }
    .dark #calendar .fc .fc-day-today { background:rgba(99,102,241,.1)!important; }
    .dark #calendar .fc .fc-day-today .fc-daygrid-day-number { color:#a5b4fc; }
    .dark #calendar .fc .fc-daygrid-day:not(.fc-day-other):hover { background:rgba(99,102,241,.07); }
    .dark #calendar .fc-theme-standard td, .dark #calendar .fc-theme-standard th, .dark #calendar .fc-theme-standard .fc-scrollgrid { border-color:#374151; }
    .dark #calendar .fc .fc-button { background:#1f2937; border-color:#374151; color:#d1d5db; }
    .dark #calendar .fc .fc-button:hover { background:#374151; border-color:#4b5563; }
    .dark #calendar .fc .fc-button-primary:not(:disabled).fc-button-active,
    .dark #calendar .fc .fc-button-primary:not(:disabled):active { background:#6366f1; border-color:#6366f1; color:#fff; }
    .dark #calendar .fc .fc-daygrid-more-link { color:#818cf8; }
    .dark #calendar .fc .fc-list-day-cushion { background:#1f2937; color:#9ca3af; }
    .dark #calendar .fc .fc-list-event:hover td { background:#1f2937; }
    .dark #calendar .fc .fc-list-event-title a { color:#d1d5db; }
    .dark #calendar .fc .fc-popover { background:#1f2937; border-color:#374151; box-shadow:0 8px 30px rgba(0,0,0,.4); }
    .dark #calendar .fc .fc-popover-header { background:#111827; color:#d1d5db; }
    </style>
</x-filament-panels::page>