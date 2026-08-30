<!--begin::Search-->
<div id="salon_global_search" class="header-search d-flex align-items-stretch"
    data-salon-search-url="{{ route('global-search') }}">
    <!--begin::Search toggle-->
    <div class="d-flex align-items-center" id="salon_global_search_toggle" data-kt-menu-trigger="{default: 'click', lg: 'click'}"
        data-kt-menu-target="#salon_global_search_menu" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
        <div
            class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-35px h-35px w-md-40px h-md-40px">
            {!! getIcon('magnifier', 'fs-2') !!}
        </div>
    </div>
    <!--end::Search toggle-->

    <!--begin::Menu-->
    <div id="salon_global_search_menu" class="menu menu-sub menu-sub-dropdown p-7 w-325px w-md-425px"
        data-kt-menu="true" data-kt-menu-overflow="false" data-kt-menu-permanent="true">
        <div data-salon-search-wrapper>
            @include(config('settings.KT_THEME_LAYOUT_DIR').'/partials/sidebar-layout/search/partials/_form-dropdown')

            @include(config('settings.KT_THEME_LAYOUT_DIR').'/partials/sidebar-layout/search/partials/_results')

            @include(config('settings.KT_THEME_LAYOUT_DIR').'/partials/sidebar-layout/search/partials/_main')

            @include(config('settings.KT_THEME_LAYOUT_DIR').'/partials/sidebar-layout/search/partials/_empty')
        </div>

        @include(config('settings.KT_THEME_LAYOUT_DIR').'/partials/sidebar-layout/search/partials/_advanced-options')
    </div>
    <!--end::Menu-->
</div>
<!--end::Search-->

@push('scripts')
    <script>
        KTUtil.onDOMContentLoaded(function() {
            const root = document.getElementById('salon_global_search');

            if (!root) {
                return;
            }

            if (window.KTMenu) {
                KTMenu.createInstances('#salon_global_search_menu');
            }

            const input = root.querySelector('[data-salon-search-input]');
            const form = root.querySelector('[data-salon-search-form]');
            const spinner = root.querySelector('[data-salon-search-spinner]');
            const reset = root.querySelector('[data-salon-search-clear]');
            const toolbar = root.querySelector('[data-salon-search-toolbar]');
            const main = root.querySelector('[data-salon-search-main]');
            const results = root.querySelector('[data-salon-search-results]');
            const resultsBody = root.querySelector('[data-salon-search-results-body]');
            const empty = root.querySelector('[data-salon-search-empty]');
            const emptyText = root.querySelector('[data-salon-search-empty-text]');
            const advanced = root.querySelector('[data-salon-search-advanced]');
            const advancedToggle = root.querySelector('[data-salon-search-advanced-toggle]');
            const advancedCancel = root.querySelector('[data-salon-search-advanced-cancel]');
            const typeSelect = root.querySelector('[data-salon-search-type]');
            const searchUrl = root.dataset.salonSearchUrl;
            const icons = {
                appointment: 'bi-calendar-check',
                branch: 'bi-shop',
                customer: 'bi-person-heart',
                expense: 'bi-receipt',
                invoice: 'bi-file-earmark-text',
                membership: 'bi-award',
                product: 'bi-box-seam',
                promotion: 'bi-percent',
                service: 'bi-scissors',
                staff: 'bi-person-badge',
                tenant: 'bi-buildings',
                user: 'bi-people'
            };
            let controller = null;
            let debounce = null;
            let firstResultUrl = null;

            const setLoading = function(isLoading) {
                spinner?.classList.toggle('d-none', !isLoading);
                reset?.classList.toggle('d-none', isLoading || input.value.trim().length === 0);
                toolbar?.classList.toggle('d-none', isLoading || input.value.trim().length > 0);
            };

            const setPanel = function(panel) {
                main?.classList.toggle('d-none', panel !== 'main');
                results?.classList.toggle('d-none', panel !== 'results');
                empty?.classList.toggle('d-none', panel !== 'empty');
                advanced?.classList.toggle('d-none', panel !== 'advanced');
            };

            const escapeHtml = function(value) {
                return String(value ?? '').replace(/[&<>"']/g, function(match) {
                    return {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#039;'
                    } [match];
                });
            };

            const renderResults = function(groups) {
                firstResultUrl = null;
                resultsBody.innerHTML = groups.map(function(group) {
                    const items = group.items.map(function(item) {
                        firstResultUrl = firstResultUrl || item.url;
                        const iconClass = icons[item.type] || 'bi-search';

                        return `
                            <a href="${escapeHtml(item.url)}" class="d-flex align-items-center text-gray-900 text-hover-primary mb-5">
                                <div class="symbol symbol-40px me-4">
                                    <span class="symbol-label bg-light-${escapeHtml(item.tone || 'primary')}">
                                        <i class="bi ${iconClass} fs-3 text-${escapeHtml(item.tone || 'primary')}"></i>
                                    </span>
                                </div>
                                <div class="d-flex flex-column min-w-0 flex-grow-1">
                                    <span class="fs-6 fw-bold text-truncate">${escapeHtml(item.title)}</span>
                                    <span class="fs-7 fw-semibold text-muted text-truncate">${escapeHtml(item.subtitle || item.type)}</span>
                                </div>
                                ${item.badge ? `<span class="badge badge-light-${escapeHtml(item.tone || 'primary')} ms-3">${escapeHtml(item.badge)}</span>` : ''}
                            </a>
                        `;
                    }).join('');

                    return `
                        <div class="mb-2">
                            <h3 class="fs-7 text-muted text-uppercase fw-bold m-0 pb-4">${escapeHtml(group.label)}</h3>
                            ${items}
                        </div>
                    `;
                }).join('');
            };

            const performSearch = function() {
                const query = input.value.trim();

                if (query.length < 2) {
                    firstResultUrl = null;
                    setLoading(false);
                    setPanel('main');
                    return;
                }

                if (controller) {
                    controller.abort();
                }

                controller = new AbortController();
                setLoading(true);

                const url = new URL(searchUrl, window.location.origin);
                url.searchParams.set('q', query);

                if (typeSelect?.value) {
                    url.searchParams.set('type', typeSelect.value);
                }

                fetch(url.toString(), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        signal: controller.signal
                    })
                    .then(function(response) {
                        if (!response.ok) {
                            throw new Error('Search failed');
                        }

                        return response.json();
                    })
                    .then(function(payload) {
                        setLoading(false);

                        if (!payload.total) {
                            emptyText.textContent = `No records found for "${query}"`;
                            setPanel('empty');
                            return;
                        }

                        renderResults(payload.groups);
                        setPanel('results');
                    })
                    .catch(function(error) {
                        if (error.name === 'AbortError') {
                            return;
                        }

                        setLoading(false);
                        emptyText.textContent = 'Search is unavailable right now. Please try again.';
                        setPanel('empty');
                    });
            };

            input?.addEventListener('input', function() {
                clearTimeout(debounce);
                setLoading(input.value.trim().length >= 2);
                debounce = setTimeout(performSearch, 250);
            });

            form?.addEventListener('submit', function(event) {
                event.preventDefault();

                if (firstResultUrl) {
                    window.location.href = firstResultUrl;
                    return;
                }

                performSearch();
            });

            reset?.addEventListener('click', function() {
                input.value = '';
                resultsBody.innerHTML = '';
                firstResultUrl = null;
                setLoading(false);
                setPanel('main');
                input.focus();
            });

            advancedToggle?.addEventListener('click', function() {
                setPanel('advanced');
            });

            advancedCancel?.addEventListener('click', function(event) {
                event.preventDefault();
                setPanel(input.value.trim().length >= 2 && firstResultUrl ? 'results' : 'main');
            });

            typeSelect?.addEventListener('change', function() {
                setPanel('main');
                performSearch();
            });

            root.querySelectorAll('[data-salon-search-preset]').forEach(function(link) {
                link.addEventListener('click', function() {
                    input.value = link.dataset.salonSearchPreset || '';
                    performSearch();
                });
            });

            setPanel('main');
            setLoading(false);
        });
    </script>
@endpush
