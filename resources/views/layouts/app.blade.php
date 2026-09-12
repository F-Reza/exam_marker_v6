<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>
        @yield('title', $systemSettings['site_title'] ?? $systemSettings['app_name'] ?? 'Exam Marker')
    </title>

    <meta name="description" content="{{ $systemSettings['meta_description'] ?? 'AI Paper Checking Platform' }}">
    <meta name="keywords" content="{{ $systemSettings['meta_keywords'] ?? '' }}">

    @if(!empty($systemSettings['favicon']))
    <link rel="icon" href="{{Storage::url($systemSettings['favicon'])}}">
    @else
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="/assets/backend/css/style.css">
    <link rel="stylesheet" href="{{ asset('assets/backend/css/style.css') }}">
    <link rel="stylesheet" href="{{ url('public/assets/backend/css/style.css') }}">

    @stack('styles')
</head>

<body>

    @auth
    <div class="app-shell">
        <aside class="sidebar" id="sidebar">
            <a href="{{route('dashboard')}}" class="brand">
                @if(!empty($systemSettings['site_logo']))
                <img src="{{Storage::url($systemSettings['site_logo'])}}"
                    style="width:38px;height:38px;border-radius:10px;object-fit:cover;">
                @else
                <span class="brand-icon">✓</span>
                @endif
                <span>
                    <b>{{$systemSettings['site_title'] ?? $systemSettings['app_name'] ?? 'Exam Marker'}}</b>
                    <small>AI Paper Checker</small>
                </span>
            </a>

            <nav>
                @if(Route::has('dashboard'))
                <a href="{{route('dashboard')}}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    ☰ Dashboard
                </a>
                @endif

                @if(Route::has('assessments.create'))
                <a href="{{route('assessments.create')}}"
                    class="{{ request()->routeIs('assessments.create') ? 'active' : '' }}">
                    🚀 Check New Papers
                </a>
                @endif

                @if(Route::has('assessments.bulk.create'))
                <a href="{{route('assessments.bulk.create')}}"
                    class="{{ request()->routeIs('assessments.bulk.create') ? 'active' : '' }}">
                    🔼 Bulk Check Papers
                </a>
                @endif

                @if(Route::has('assessments.index'))
                <a href="{{route('assessments.index')}}"
                    class="{{ request()->routeIs('assessments.index') || request()->routeIs('assessments.show') || request()->routeIs('assessments.processing') ? 'active' : '' }}">
                    📚 All Papers
                </a>
                @endif

                @if(Route::has('students.index'))
                <a href="{{route('students.index')}}"
                    class="{{ request()->routeIs('students.index') || request()->routeIs('students.create') ? 'active' : '' }}">
                    👨‍🎓 Students
                </a>
                @endif

                @if(Route::has('communications.index'))
                <a href="{{route('communications.index')}}"
                    class="{{ request()->routeIs('communications.index') ? 'active' : '' }}">
                    💬 Parent Messages
                </a>
                @endif

                @if(Route::has('team.index'))
                <a href="{{route('team.index')}}" class="{{ request()->routeIs('team.index') ? 'active' : '' }}">
                    👨‍🏫 Teachers & Team
                </a>
                @endif

                @if(Route::has('reports.index'))
                <a href="{{route('reports.index')}}" class="{{ request()->routeIs('reports.index') ? 'active' : '' }}">
                    📊 Reports
                </a>
                @endif

                @if(Route::has('analytics'))
                <a href="{{route('analytics')}}" class="{{ request()->routeIs('analytics') ? 'active' : '' }}">
                    📈 Analytics
                </a>
                @endif

                @if(Route::has('notifications.index'))
                <a href="{{route('notifications.index')}}"
                    class="{{ request()->routeIs('notifications.index') ? 'active' : '' }}">
                    🔔 Notifications
                </a>
                @endif

                @if(Route::has('admin.support.index'))
                <a href="{{route('admin.support.index')}}"
                    class="{{ request()->routeIs('admin.support.index') ? 'active' : '' }}">
                    📩 Support Center
                </a>
                @endif

                {{-- PLATFORM ADMIN --}}
                @if(auth()->user()->isPlatformAdmin())
                @if(Route::has('admin.plans.index'))
                <a href="{{route('admin.plans.index')}}"
                    class="{{ request()->routeIs('admin.plans.index') ? 'active' : '' }}">
                    💼 Plans & Billing
                </a>
                @endif

                @if(Route::has('admin.payment-gateways.index'))
                <a href="{{route('admin.payment-gateways.index')}}"
                    class="{{ request()->routeIs('admin.payment-gateways.index') ? 'active' : '' }}">
                    🏛️ Payment Gateway
                </a>
                @endif

                @if(Route::has('admin.transactions.index'))
                <a href="{{route('admin.transactions.index')}}"
                    class="{{ request()->routeIs('admin.transactions.index') ? 'active' : '' }}">
                    💳 Transactions History
                </a>
                @endif

                @if(Route::has('admin.users.index'))
                <a href="{{route('admin.users.index')}}"
                    class="{{ request()->routeIs('admin.users.index') ? 'active' : '' }}">
                    👨‍👦‍👦 Users Management
                </a>
                @endif

                @if(Route::has('settings.index'))
                <a href="{{route('settings.index')}}"
                    class="{{ request()->routeIs('settings.index') ? 'active' : '' }}">
                    ⚙️ Profile Settings
                </a>
                @endif

                @if(Route::has('admin.config.index'))
                <a href="{{route('admin.config.index')}}"
                    class="{{ request()->routeIs('admin.config.index') ? 'active' : '' }}">
                    🛠️ System Configuration
                </a>
                @endif

                {{-- AI PROCESSING --}}
                @if(auth()->user()->isPlatformAdmin())
                @if(Route::has('admin.ai-processing.index'))
                <a href="{{route('admin.ai-processing.index')}}"
                    class="{{ request()->routeIs('admin.ai-processing.index') ? 'active' : '' }}">
                    ✨ AI Processing
                </a>
                @endif

                @if(Route::has('admin.ai-settings.index'))
                <a href="{{route('admin.ai-settings.index')}}"
                    class="{{ request()->routeIs('admin.ai-settings.index') ? 'active' : '' }}">
                    🤖 AI Provider Settings
                </a>
                @endif
                @endif

                @if(Route::has('admin.audit.index'))
                <a href="{{route('admin.audit.index')}}"
                    class="{{ request()->routeIs('admin.audit.index') ? 'active' : '' }}">
                    📋 Audit Logs
                </a>
                @endif

                @else
                @if(Route::has('billing.index'))
                <a href="{{route('billing.index')}}" class="{{ request()->routeIs('billing.index') ? 'active' : '' }}">
                    ◇ Plan & Billing
                </a>
                @endif

                @if(Route::has('help.index'))
                <a href="{{route('help.index')}}" class="{{ request()->routeIs('help.index') ? 'active' : '' }}">
                    ❓ Help & Support
                </a>
                @endif
                @endif
            </nav>

            <div class="side-plan">
                @if(auth()->user()->isPlatformAdmin())
                <span class="plan-chip">Platform Admin</span>
                <p>Unlimited System Access</p>
                @else
                <span class="plan-chip">{{auth()->user()->plan?->name}}</span>
                <p>{{auth()->user()->limit('paper_check_limit',1)}} checks per cycle</p>
                @endif
            </div>
        </aside>

        <div class="main-wrap">
            <header class="topbar">
                <button class="menu-btn" onclick="document.getElementById('sidebar').classList.toggle('open')">
                    ☰
                </button>

                <div class="top-title">
                    @yield('page-title', 'Dashboard')
                </div>

                @if(Route::has('notifications.index'))
                <a class="notification-link" href="{{route('notifications.index')}}">
                    🔔
                    <span>{{auth()->user()->unreadNotifications()->count()}}</span>
                </a>
                @endif

                <div class="user-menu">
                    <span class="avatar">{{strtoupper(substr(auth()->user()->name,0,2))}}</span>
                    <div>
                        <b>{{auth()->user()->name}}</b>
                        <small>{{ucfirst(auth()->user()->user_type)}}</small>
                    </div>
                    <form method="POST" action="{{route('logout')}}">
                        @csrf
                        <button class="icon-btn">↪</button>
                    </form>
                </div>
            </header>

            <main class="content">
                @else
                <header class="public-nav">
                    <a href="/" class="brand">
                        @if(!empty($systemSettings['site_logo']))
                        <img src="{{Storage::url($systemSettings['site_logo'])}}"
                            style="width:38px;height:38px;border-radius:10px;object-fit:cover;">
                        @else
                        <span class="brand-icon">✓</span>
                        @endif
                        <span>
                            <b>{{$systemSettings['site_title'] ?? $systemSettings['app_name'] ?? 'Exam Marker'}}</b>
                            <small>AI Paper Checker</small>
                        </span>
                    </a>

                    <nav>
                        <a href="{{route('how')}}">How It Works</a>
                        <a href="/#features">Features</a>
                        <a href="{{route('pricing')}}">Plans & Pricing</a>
                        <a href="{{route('login')}}">Login</a>
                        <a style="color: ghostwhite !important; 
                        font-weight: bold !important;
                        padding: 0.55rem .8rem !important;" 
                        class="btn small small-btn" href="{{route('register')}}">Register</a>
                    </nav>
                </header>

                <main>
                    @endauth

                    @if(session('success'))
                    <div class="alert success">✓ {{session('success')}}</div>
                    @endif

                    @if(session('error'))
                    <div class="alert danger">! {{session('error')}}</div>
                    @endif

                    @if($errors->any())
                    <div class="alert danger">
                        <b>Please correct:</b>
                        <ul>
                            @foreach($errors->all() as $e)
                            <li>{{$e}}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    @yield('content')

                </main>

                @auth
        </div>
    </div>
    @endauth

    <footer class="site-footer">
        <span>© {{date('Y')}} {{$systemSettings['site_title'] ?? $systemSettings['app_name'] ?? 'Exam Marker'}}</span>
        <span>AI suggestions require human review.</span>
    </footer>

    @stack('scripts')
</body>

</html>