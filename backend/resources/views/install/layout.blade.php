<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '安装向导') · 泡泡栈</title>
    <style>
        :root {
            --ink: #172033;
            --muted: #3d4d66;
            --line: #d7e1ee;
            --bg: #dfe7f2;
            --card: #ffffff;
            --rail: #f4f7fb;
            --primary: #1a56db;
            --primary-dark: #1548b8;
            --ok: #0d7a45;
            --ok-bg: #e7f6ee;
            --bad: #b4232c;
            --bad-bg: #fdecee;
            --dock: #f7f9fc;
        }
        * { box-sizing: border-box; }
        html, body {
            height: 100%;
            max-width: 100%;
            overflow: hidden;
        }
        body {
            margin: 0;
            display: grid;
            place-items: center;
            padding: 20px 16px;
            color: var(--ink);
            background-color: var(--bg);
            background-image:
                linear-gradient(180deg, rgba(255, 255, 255, 0.62), rgba(255, 255, 255, 0) 240px),
                radial-gradient(880px 380px at 50% -80px, #c9daf8 0%, transparent 68%);
            font-family: "WenQuanYi Micro Hei", "PingFang SC", "Noto Sans SC", "Microsoft YaHei", sans-serif;
            caret-color: var(--primary);
        }
        ::selection { background: #d6e4ff; color: var(--ink); }
        :focus-visible { outline: 2px solid var(--primary); outline-offset: 2px; }
        .shell {
            width: min(920px, 100%);
            height: min(820px, calc(100dvh - 40px));
            min-width: 0;
            min-height: 0;
        }
        .card {
            height: 100%;
            min-width: 0;
            min-height: 0;
            display: grid;
            grid-template-columns: 232px minmax(0, 1fr);
            background: var(--card);
            border: 1px solid rgba(255, 255, 255, 0.85);
            border-radius: 18px;
            overflow: hidden;
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.75),
                0 18px 44px rgba(22, 40, 72, 0.14);
        }
        .rail {
            min-width: 0;
            min-height: 0;
            display: flex;
            flex-direction: column;
            gap: 16px;
            padding: 22px 18px 18px;
            background: var(--rail);
            border-right: 1px solid var(--line);
            overflow: hidden;
        }
        .brand { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .mark { flex: none; width: 36px; height: 36px; }
        .brand-text { min-width: 0; }
        h1 {
            margin: 0;
            font-size: 20px;
            line-height: 1.25;
            font-weight: 700;
            letter-spacing: -0.02em;
            overflow-wrap: anywhere;
        }
        .step-label {
            margin: 3px 0 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.4;
            overflow-wrap: anywhere;
        }
        ol.steps {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-width: 0;
            min-height: 0;
            overflow: auto;
        }
        ol.steps li {
            position: relative;
            display: grid;
            grid-template-columns: 28px minmax(0, 1fr);
            gap: 10px;
            align-items: center;
            min-width: 0;
            padding: 9px 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.35;
        }
        ol.steps li::before {
            content: "";
            position: absolute;
            left: 13px;
            top: 0;
            bottom: 0;
            width: 1px;
            background: #d3deeb;
        }
        ol.steps li:first-child::before { top: 50%; }
        ol.steps li:last-child::before { bottom: 50%; }
        ol.steps li .n {
            position: relative;
            z-index: 1;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            border: 1px solid #c5d3e4;
            background: #fff;
            font-size: 12px;
            font-weight: 700;
        }
        ol.steps li .label { min-width: 0; overflow-wrap: anywhere; }
        ol.steps li.on { color: var(--ink); font-weight: 700; }
        ol.steps li.on .n { background: var(--primary); color: #fff; border-color: var(--primary); }
        ol.steps li.done { color: var(--ink); }
        ol.steps li.done .n { background: var(--ok); color: #fff; border-color: var(--ok); }
        .progress {
            flex: none;
            height: 6px;
            background: #e1e8f3;
            border-radius: 99px;
            overflow: hidden;
        }
        .progress > span {
            display: block;
            height: 100%;
            width: 0;
            background: var(--primary);
            border-radius: inherit;
            transition: width 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .hint {
            margin: 0;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }
        .card-main {
            min-width: 0;
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .panel {
            flex: 1 1 auto;
            min-width: 0;
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .panel[hidden] { display: none !important; }
        .panel-scroll {
            flex: 1 1 auto;
            min-width: 0;
            min-height: 0;
            overflow-x: hidden;
            overflow-y: auto;
            padding: 26px 28px 18px;
            scrollbar-width: thin;
            scrollbar-color: #b7c4d8 transparent;
        }
        .panel-scroll::-webkit-scrollbar { width: 10px; }
        .panel-scroll::-webkit-scrollbar-thumb {
            background: #b7c4d8;
            border-radius: 99px;
            border: 2px solid #fff;
        }
        .dock {
            flex: none;
            min-width: 0;
            border-top: 1px solid var(--line);
            background: var(--dock);
        }
        h2 {
            margin: 0 0 8px;
            font-size: 22px;
            line-height: 1.3;
            letter-spacing: -0.02em;
            overflow-wrap: anywhere;
        }
        .lead {
            margin: 0 0 18px;
            max-width: 68ch;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.65;
            overflow-wrap: anywhere;
        }
        .checks { display: flex; flex-direction: column; gap: 8px; min-width: 0; }
        .check {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: 12px;
            align-items: start;
            min-width: 0;
            padding: 12px 14px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: #fff;
        }
        .check.bad { background: var(--bad-bg); border-color: #f3c2c7; }
        .pill {
            font-size: 12px;
            font-weight: 700;
            line-height: 1;
            padding: 6px 8px;
            border-radius: 999px;
            white-space: nowrap;
        }
        .pill.ok { color: var(--ok); background: var(--ok-bg); }
        .pill.bad { color: var(--bad); background: #f8d5d8; }
        .check .name {
            display: block;
            margin-bottom: 3px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }
        .check .reason {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.55;
            overflow-wrap: anywhere;
        }
        .check.bad .reason { color: var(--bad); font-weight: 700; }
        label {
            display: block;
            margin: 14px 0 6px;
            color: var(--ink);
            font-size: 13px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }
        input:not([type="checkbox"]),
        select,
        textarea {
            display: block;
            width: 100%;
            max-width: 100%;
            height: 44px;
            margin: 0;
            padding: 0 12px;
            border: 1px solid #c5d2e4;
            border-radius: 8px;
            background: #fff;
            color: var(--ink);
            font: inherit;
            font-size: 15px;
            box-shadow: inset 0 1px 2px rgba(20, 40, 70, 0.04);
        }
        textarea { height: auto; padding: 10px 12px; }
        input::placeholder { color: #52657f; }
        input:not([type="checkbox"]):hover,
        select:hover,
        textarea:hover { border-color: #9aafc8; }
        input:not([type="checkbox"]):focus,
        select:focus,
        textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(26, 86, 219, 0.16);
        }
        .grid, .grid3 {
            display: grid;
            gap: 12px;
            min-width: 0;
        }
        .grid { grid-template-columns: minmax(0, 1.4fr) minmax(0, 0.8fr); }
        .grid3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .grid > div, .grid3 > div { min-width: 0; }
        button, .link-btn {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            height: 44px;
            margin: 0;
            padding: 0 14px;
            border-radius: 8px;
            border: 1px solid transparent;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font: inherit;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.25;
            text-align: center;
            text-decoration: none;
            white-space: normal;
            overflow-wrap: anywhere;
            cursor: pointer;
        }
        button.primary, a.primary {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary-dark);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.22), 0 1px 2px rgba(16, 42, 92, 0.18);
        }
        button.primary:hover, a.primary:hover { background: var(--primary-dark); }
        button.ghost, a.ghost {
            background: #fff;
            color: var(--ink);
            border-color: #c9d5e6;
        }
        button.ghost:hover, a.ghost:hover { background: #eef3fb; }
        button:disabled { opacity: 0.46; cursor: not-allowed; }
        .msg {
            margin: 12px 22px 0;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 14px;
            line-height: 1.55;
            max-height: 7.5rem;
            overflow-x: hidden;
            overflow-y: auto;
            overflow-wrap: anywhere;
        }
        .msg.ok { color: var(--ok); background: var(--ok-bg); border: 1px solid #b7e4c7; }
        .msg.bad { color: var(--bad); background: var(--bad-bg); border: 1px solid #f3c2c4; font-weight: 700; }
        .choice {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: 10px;
            align-items: start;
            margin-top: 16px;
            padding: 12px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: #fbfcfe;
        }
        .choice input { width: 16px; height: 16px; margin-top: 2px; }
        .choice span { font-size: 14px; line-height: 1.55; overflow-wrap: anywhere; }
        .summary { display: grid; gap: 0; min-width: 0; margin: 0; }
        .summary div {
            display: grid;
            grid-template-columns: 88px minmax(0, 1fr);
            gap: 12px;
            align-items: start;
            padding: 11px 0;
            border-bottom: 1px solid var(--line);
            font-size: 14px;
            min-width: 0;
        }
        .summary dt { margin: 0; color: var(--muted); }
        .summary dd { margin: 0; overflow-wrap: anywhere; }
        .log { margin: 16px 0 0; padding: 0; list-style: none; min-width: 0; }
        .log li {
            padding: 9px 0;
            border-bottom: 1px solid var(--line);
            font-size: 14px;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }
        .actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 9.5rem), 1fr));
            gap: 10px;
            min-width: 0;
            padding: 14px 22px 16px;
        }
        @media (max-width: 760px) {
            body { padding: 10px; }
            .shell { height: min(820px, calc(100dvh - 20px)); }
            .card {
                grid-template-columns: minmax(0, 1fr);
                grid-template-rows: auto minmax(0, 1fr);
                border-radius: 14px;
            }
            .rail {
                gap: 10px;
                padding: 14px 14px 12px;
                border-right: 0;
                border-bottom: 1px solid var(--line);
                overflow: visible;
            }
            ol.steps {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 6px 8px;
                overflow: visible;
            }
            ol.steps li { padding: 0; font-size: 12px; }
            ol.steps li::before { display: none; }
            .panel-scroll { padding: 18px 16px 12px; }
            .actions { padding: 12px 14px 14px; }
            .msg { margin: 12px 14px 0; }
            .grid, .grid3 { grid-template-columns: minmax(0, 1fr); }
            .summary div { grid-template-columns: 72px minmax(0, 1fr); }
        }
    </style>
</head>
<body>
<div class="shell">
    <section class="card">
        <aside class="rail">
            <div class="brand">
                <svg class="mark" viewBox="0 0 36 36" aria-hidden="true">
                    <rect width="36" height="36" rx="10" fill="#1a56db"/>
                    <circle cx="14" cy="18" r="6" fill="none" stroke="#fff" stroke-width="1.8"/>
                    <circle cx="22" cy="18" r="6" fill="none" stroke="#fff" stroke-width="1.8"/>
                </svg>
                <div class="brand-text">
                    <h1>泡泡栈</h1>
                    <p class="step-label" id="step-label">@yield('step_label', '第 1 步，共 6 步')</p>
                </div>
            </div>
            <ol class="steps" id="step-list">
                @foreach (['环境检测', '数据库', '管理员', '站点', '安装', '完成'] as $index => $name)
                    <li class="@yield('step_class_'.$index, '')"><span class="n">{{ $index + 1 }}</span><span class="label">{{ $name }}</span></li>
                @endforeach
            </ol>
            <div class="progress" aria-hidden="true"><span id="progress-bar" style="width: @yield('progress', '8%')"></span></div>
            <p class="hint">完成后写入 install.lock，不能再次安装。</p>
        </aside>
        <div class="card-main">
            @yield('content')
        </div>
    </section>
</div>
@yield('script')
</body>
</html>
