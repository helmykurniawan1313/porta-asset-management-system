<div id="sidebar">
    <div class="sidebar-wrapper active">
        <div class="sidebar-header position-relative">
            <div class="d-flex justify-content-between align-items-center">
                <div class="logo">
                    <a href="{{ route('dashboard') }}">
                        <span class="fs-4 text-primary font-bold tracking-tight uppercase">Porta IT</span>
                    </a>
                </div>
                <div class="theme-toggle d-flex gap-2  align-items-center mt-2">
                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true" role="img" class="iconify iconify--system-uicons" width="20" height="20" preserveAspectRatio="xMidYMid meet" viewBox="0 0 21 21"><g fill="none" fill-rule="evenodd" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M10.5 14.5c2.219 0 4-1.763 4-3.982a4.003 4.003 0 0 0-4-4.018c-2.219 0-4 1.781-4 4c0 2.219 1.781 4 4 4zM4.136 4.136L5.55 5.55m9.9 9.9l1.414 1.414M1.5 10.5h2m14 0h2M4.135 16.863L5.55 15.45m9.899-9.9l1.414-1.415M10.5 19.5v-2m0-14v-2" opacity=".3"></path><g transform="translate(-210 -1)"><path d="M220.5 2.5v2m6.5.5l-1.5 1.5"></path><circle cx="220.5" cy="11.5" r="4"></circle><path d="m214 5l1.5 1.5m5 14v-2m6.5-.5l-1.5-1.5M214 18l1.5-1.5m-4-5h2m14 0h2"></path></g></g></svg>
                    <div class="form-check form-switch fs-6">
                        <input class="form-check-input  me-0" type="checkbox" id="toggle-dark" style="cursor: pointer">
                        <label class="form-check-label" for="toggle-dark"></label>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true" role="img" class="iconify iconify--mdi" width="20" height="20" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24"><path fill="currentColor" d="m17.75 4.09l-2.53 1.94l.91 3.06l-2.63-1.81l-2.63 1.81l.91-3.06l-2.53-1.94L12.44 4l1.06-3l1.06 3l3.19.09m3.5 6.91l-1.64 1.25l.59 1.98l-1.7-1.17l-1.7 1.17l.59-1.98L15.75 11l2.06-.05L18.5 9l.69 1.95l2.06.05m-2.28 4.95c.83-.08 1.72 1.1 1.19 1.85c-.32.45-.66.87-1.08 1.27C15.17 23 8.84 23 4.94 19.07c-3.91-3.9-3.91-10.24 0-14.14c.4-.4.82-.76 1.27-1.08c.75-.53 1.93.36 1.85 1.19c-.27 2.86.69 5.83 2.89 8.02a9.96 9.96 0 0 0 8.02 2.89m-1.64 2.02a12.08 12.08 0 0 1-7.8-3.47c-2.17-2.19-3.33-5-3.49-7.82c-2.81 3.14-2.7 7.96.31 10.98c3.02 3.01 7.84 3.12 10.98.31Z"></path></svg>
                </div>
                <div class="sidebar-toggler  x">
                    <a href="#" class="sidebar-hide d-xl-none d-block"><i class="bi bi-x bi-middle"></i></a>
                </div>
            </div>
        </div>
        <div class="sidebar-menu">
            <ul class="menu">
                <li class="sidebar-title">Menu</li>

                <li class="sidebar-item {{ request()->routeIs('dashboard') || request()->is('/') ? 'active' : '' }}">
                    <a href="{{ route('dashboard') }}" class='sidebar-link'>
                        <i class="bi bi-grid-fill"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                @if(auth()->user()->hasAnyRole(['IT Admin', 'Super Admin']) || auth()->user()->isInDepartment('IT'))
                @php
                    $isMainDeviceActive = request()->is('inventory/PC', 'inventory/Laptop', 'inventory/Phone');
                @endphp
                <li class="sidebar-item has-sub {{ $isMainDeviceActive ? 'active' : '' }}">
                    <a href="#" class='sidebar-link'>
                        <i class="bi bi-pc-display"></i>
                        <span>Main Devices</span>
                    </a>
                    <ul class="submenu {{ $isMainDeviceActive ? 'active' : '' }}">
                        @foreach(['PC', 'Laptop', 'Phone'] as $nav)
                        <li class="submenu-item {{ request()->is('inventory/'.$nav) ? 'active' : '' }}">
                            <a href="{{ route('inventory.index', $nav) }}" class="submenu-link">{{ $nav }}</a>
                        </li>
                        @endforeach
                    </ul>
                </li>

                @php
                    $isCoreComponentActive = request()->is(
                        'inventory/Motherboard', 'inventory/Processor', 'inventory/RAM', 
                        'inventory/SSD', 'inventory/HDD', 'inventory/GPU', 'inventory/Monitor', 
                        'inventory/UPS', 'inventory/Power Supply', 'inventory/Battery',
                        'inventory/Keyboard', 'inventory/Mouse'
                    );
                @endphp
                <li class="sidebar-item has-sub {{ $isCoreComponentActive ? 'active' : '' }}">
                    <a href="#" class='sidebar-link'>
                        <i class="bi bi-cpu-fill"></i>
                        <span>Core Components</span>
                    </a>
                    <ul class="submenu {{ $isCoreComponentActive ? 'active' : '' }}">
                        @foreach(['Motherboard', 'Processor', 'RAM', 'SSD', 'HDD', 'GPU', 'Monitor', 'UPS', 'Power Supply', 'Battery', 'Keyboard', 'Mouse'] as $nav)
                        <li class="submenu-item {{ request()->is('inventory/'.$nav) ? 'active' : '' }}">
                            <a href="{{ route('inventory.index', $nav) }}" class="submenu-link">{{ $nav }}</a>
                        </li>
                        @endforeach
                    </ul>
                </li>

                @php
                    $isCloudActive = request()->routeIs('subscriptions.*') || request()->routeIs('accounts.*');
                @endphp
                <li class="sidebar-item has-sub {{ $isCloudActive ? 'active' : '' }}">
                    <a href="#" class='sidebar-link'>
                        <i class="bi bi-cloud-fill"></i>
                        <span>Cloud & SaaS</span>
                    </a>
                    <ul class="submenu {{ $isCloudActive ? 'active' : '' }}">
                        <li class="submenu-item {{ request()->routeIs('subscriptions.*') ? 'active' : '' }}">
                            <a href="{{ route('subscriptions.index') }}" class="submenu-link">Subscriptions</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('accounts.*') ? 'active' : '' }}">
                            <a href="{{ route('accounts.index') ?? '#' }}" class="submenu-link">Master Accounts</a>
                        </li>
                    </ul>
                </li>

                <li class="sidebar-item {{ request()->is('inventory/logs') ? 'active' : '' }}">
                    <a href="/inventory/logs" class='sidebar-link'>
                        <i class="bi bi-journal-text"></i>
                        <span>Activity History</span>
                    </a>
                </li>
                @endif

                @can('access-client-ops')
                @php
                    $isClientOpsActive = request()->routeIs('clients.*') || request()->routeIs('invoices.*');
                @endphp
                <li class="sidebar-title">Client Ops</li>
                <li class="sidebar-item has-sub {{ $isClientOpsActive ? 'active' : '' }}">
                    <a href="#" class='sidebar-link'>
                        <i class="bi bi-briefcase-fill"></i>
                        <span>Client Ops</span>
                    </a>
                    <ul class="submenu {{ $isClientOpsActive ? 'active' : '' }}">
                        <li class="submenu-item {{ request()->routeIs('clients.*') ? 'active' : '' }}">
                            <a href="{{ route('clients.index') }}" class="submenu-link">Clients & Contracts</a>
                        </li>
                        @hasanyrole('Finance|Super Admin')
                        <li class="submenu-item {{ request()->routeIs('invoices.*') ? 'active' : '' }}">
                            <a href="{{ route('invoices.index') }}" class="submenu-link">Invoices</a>
                        </li>
                        @endhasanyrole
                    </ul>
                </li>
                @endcan

                @hasanyrole('HR Admin|IT Admin|Super Admin')
                <li class="sidebar-title">Management</li>

                @php
                    $isHrActive = request()->routeIs('employees.*') || request()->routeIs('departments.*');
                @endphp
                <li class="sidebar-item has-sub {{ $isHrActive ? 'active' : '' }}">
                    <a href="#" class='sidebar-link'>
                        <i class="bi bi-people-fill"></i>
                        <span>HR & Staff</span>
                    </a>
                    <ul class="submenu {{ $isHrActive ? 'active' : '' }}">
                        <li class="submenu-item {{ request()->routeIs('employees.*') ? 'active' : '' }}">
                            <a href="{{ route('employees.index') }}" class="submenu-link">Employees</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('departments.*') ? 'active' : '' }}">
                            <a href="{{ route('departments.index') }}" class="submenu-link">Departments</a>
                        </li>
                    </ul>
                </li>
                @endhasanyrole

                @hasanyrole('HR Admin|Super Admin')
                @php
                    $isReviewsActive = request()->routeIs('hr.*');
                @endphp
                <li class="sidebar-title">Performance Reviews</li>
                <li class="sidebar-item has-sub {{ $isReviewsActive ? 'active' : '' }}">
                    <a href="#" class='sidebar-link'>
                        <i class="bi bi-clipboard-check"></i>
                        <span>HR & Reviews</span>
                    </a>
                    <ul class="submenu {{ $isReviewsActive ? 'active' : '' }}">
                        <li class="submenu-item {{ request()->routeIs('hr.review-cycles.*') && !request()->routeIs('hr.review-cycles.templates.*') && !request()->routeIs('hr.review-cycles.assignments.*') ? 'active' : '' }}">
                            <a href="{{ route('hr.review-cycles.index') }}" class="submenu-link">Review Cycles</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('hr.employee-reviews.*') ? 'active' : '' }}">
                            <a href="{{ route('hr.employee-reviews.index') }}" class="submenu-link">Employee Reviews</a>
                        </li>
                    </ul>
                </li>
                @endhasanyrole

                <li class="sidebar-title">Personal</li>
                @php
                    $pendingReviewCount = auth()->check() ? auth()->user()->reviewAssignmentsAsReviewer()->where('status', '!=', 'submitted')->count() : 0;
                @endphp
                <li class="sidebar-item {{ request()->routeIs('my-reviews.*') ? 'active' : '' }}">
                    <a href="{{ route('my-reviews.index') }}" class='sidebar-link d-flex align-items-center'>
                        <i class="bi bi-person-check"></i>
                        <span>My Reviews</span>
                        @if($pendingReviewCount > 0)
                            <span class="badge rounded-pill sidebar-pending-badge ms-auto">{{ $pendingReviewCount }}</span>
                        @endif
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>