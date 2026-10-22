<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '安装向导') · 泡泡栈</title>
    <link rel="stylesheet" href="/fonts/noto/noto-sans-sc.css">
    <style>
        :root {
            --ink: #111827;
            --muted: #4b5563;
            --hint: #6b7280;
            --line: #e5e7eb;
            --line-strong: #d1d5db;
            --bg: #f3f4f6;
            --card: #ffffff;
            --rail: #f9fafb;
            --primary: #111827;
            --primary-hover: #1f2937;
            --ok: #067647;
            --ok-bg: #ecfdf3;
            --ok-line: #abefc6;
            --bad: #b42318;
            --bad-bg: #fef3f2;
            --bad-line: #fecdca;
            --dock: #ffffff;
            --control: 40px;
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
            padding: 24px 16px;
            color: var(--ink);
            background: var(--bg);
            font-family: "Noto Sans SC", "PingFang SC", "Microsoft YaHei", "Noto Sans CJK SC", sans-serif;
            font-size: 14px;
            line-height: 1.5;
            font-weight: 400;
            -webkit-font-smoothing: antialiased;
            caret-color: var(--ink);
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
            border: 1px solid var(--line);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(16, 24, 40, 0.05), 0 16px 40px rgba(16, 24, 40, 0.08);
        }
        .rail {
            min-width: 0;
            min-height: 0;
            display: flex;
            flex-direction: column;
            gap: 20px;
            padding: 24px 20px 20px;
            background: var(--rail);
            border-right: 1px solid var(--line);
            overflow: hidden;
        }
        .brand { display: flex; align-items: center; gap: 10px; min-width: 0; flex: none; }
        .mark { flex: none; width: 28px; height: 28px; }
        .brand-text { min-width: 0; }
        h1 {
            margin: 0;
            font-size: 16px;
            line-height: 24px;
            font-weight: 600;
            letter-spacing: 0;
            overflow-wrap: anywhere;
        }
        .step-label {
            margin: 0;
            color: var(--hint);
            font-size: 12px;
            line-height: 18px;
            font-weight: 400;
            overflow-wrap: anywhere;
        }
        ol.steps {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-width: 0;
            min-height: 0;
            overflow: auto;
        }
        ol.steps li {
            position: relative;
            display: grid;
            grid-template-columns: 22px minmax(0, 1fr);
            gap: 10px;
            align-items: center;
            min-width: 0;
            min-height: 36px;
            padding: 0;
            color: var(--hint);
            font-size: 13px;
            line-height: 20px;
            font-weight: 500;
        }
        ol.steps li::before {
            content: "";
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 1px;
            background: var(--line-strong);
        }
        ol.steps li:first-child::before { top: 50%; }
        ol.steps li:last-child::before { bottom: 50%; }
        ol.steps li .n {
            position: relative;
            z-index: 1;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--line-strong);
            background: #fff;
            color: var(--muted);
            font-size: 12px;
            font-weight: 600;
            line-height: 1;
        }
        ol.steps li .label { min-width: 0; overflow-wrap: anywhere; }
        ol.steps li.on { color: var(--ink); font-weight: 600; }
        ol.steps li.on .n { background: var(--primary); color: #fff; border-color: var(--primary); }
        ol.steps li.done { color: var(--ink); font-weight: 500; }
        ol.steps li.done .n { background: var(--ok); color: #fff; border-color: var(--ok); }
        .progress {
            flex: none;
            height: 4px;
            background: var(--line);
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
            color: var(--hint);
            font-size: 12px;
            line-height: 18px;
            font-weight: 400;
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
            padding: 24px 24px 20px;
            scrollbar-width: thin;
            scrollbar-color: #d1d5db transparent;
        }
        .panel-scroll::-webkit-scrollbar { width: 10px; }
        .panel-scroll::-webkit-scrollbar-thumb {
            background: #d1d5db;
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
            margin: 0 0 4px;
            font-size: 18px;
            line-height: 28px;
            font-weight: 600;
            letter-spacing: 0;
            overflow-wrap: anywhere;
        }
        .lead {
            margin: 0 0 20px;
            max-width: 66ch;
            color: var(--muted);
            font-size: 13px;
            line-height: 20px;
            font-weight: 400;
            overflow-wrap: anywhere;
        }
        .checks {
            display: flex;
            flex-direction: column;
            min-width: 0;
            border: 1px solid var(--line);
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
        }
        .check {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: 12px;
            align-items: center;
            min-width: 0;
            padding: 12px 14px;
            border: 0;
            border-bottom: 1px solid var(--line);
            border-radius: 0;
            background: #fff;
        }
        .check:last-child { border-bottom: 0; }
        .check.bad { background: var(--bad-bg); }
        .pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 22px;
            padding: 0 8px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 500;
            line-height: 1;
            white-space: nowrap;
        }
        .pill.ok { color: var(--ok); background: var(--ok-bg); }
        .pill.bad { color: var(--bad); background: #fee4e2; }
        .check .name {
            display: block;
            margin: 0 0 2px;
            font-size: 13px;
            line-height: 20px;
            font-weight: 600;
            overflow-wrap: anywhere;
        }
        .check .reason {
            color: var(--muted);
            font-size: 12px;
            line-height: 18px;
            font-weight: 400;
            overflow-wrap: anywhere;
        }
        .check.bad .reason { color: var(--bad); font-weight: 500; }
        label {
            display: block;
            margin: 16px 0 6px;
            color: var(--ink);
            font-size: 13px;
            line-height: 20px;
            font-weight: 500;
            overflow-wrap: anywhere;
        }
        .lead + label,
        .lead + .grid,
        .lead + .grid3 { margin-top: 0; }
        .grid label,
        .grid3 label { margin: 0 0 6px; }
        input:not([type="checkbox"]),
        select,
        textarea {
            display: block;
            width: 100%;
            max-width: 100%;
            height: var(--control);
            margin: 0;
            padding: 0 12px;
            border: 1px solid var(--line-strong);
            border-radius: 8px;
            background: #fff;
            color: var(--ink);
            font-family: inherit;
            font-size: 14px;
            font-weight: 400;
            line-height: var(--control);
            box-shadow: none;
        }
        textarea { height: auto; line-height: 20px; padding: 10px 12px; }
        input::placeholder { color: #6b7280; }
        input:not([type="checkbox"]):hover,
        select:hover,
        textarea:hover { border-color: #9ca3af; }
        input:not([type="checkbox"]):focus,
        select:focus,
        textarea:focus {
            border-color: var(--ink);
            outline: none;
            box-shadow: 0 0 0 3px rgba(17, 24, 39, 0.12);
        }
        .grid, .grid3 {
            display: grid;
            gap: 12px;
            min-width: 0;
        }
        .grid { grid-template-columns: minmax(0, 1.4fr) minmax(0, 0.8fr); }
        .grid3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .grid > div, .grid3 > div { min-width: 0; }
        button, .link-btn, a.primary {
            box-sizing: border-box;
            width: auto;
            max-width: 100%;
            min-width: 96px;
            min-height: var(--control);
            height: var(--control);
            margin: 0;
            padding: 1px 16px 0;
            border-radius: 8px;
            border: 1px solid transparent;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            line-height: 14px;
            letter-spacing: 0;
            text-align: center;
            text-decoration: none;
            white-space: nowrap;
            cursor: pointer;
        }
        button.primary, a.primary {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }
        button.primary:hover, a.primary:hover { background: var(--primary-hover); border-color: var(--primary-hover); }
        button.ghost, a.ghost {
            background: #fff;
            color: var(--ink);
            border-color: var(--line-strong);
            font-weight: 500;
        }
        button.ghost:hover, a.ghost:hover { background: #f9fafb; border-color: #9ca3af; }
        button:disabled,
        button.primary:disabled {
            background: #f3f4f6;
            color: #9ca3af;
            border-color: var(--line);
            box-shadow: none;
            cursor: not-allowed;
        }
        .msg {
            margin: 0 24px;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 13px;
            line-height: 20px;
            font-weight: 500;
            max-height: 7.5rem;
            overflow-x: hidden;
            overflow-y: auto;
            overflow-wrap: anywhere;
        }
        .dock .msg { margin-top: 12px; }
        .msg.ok { color: var(--ok); background: var(--ok-bg); border: 1px solid var(--ok-line); }
        .msg.bad { color: var(--bad); background: var(--bad-bg); border: 1px solid var(--bad-line); font-weight: 500; }
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
        .choice span { font-size: 13px; line-height: 20px; color: var(--muted); overflow-wrap: anywhere; }
        .summary { display: grid; gap: 0; min-width: 0; margin: 0; border-top: 1px solid var(--line); }
        .summary div {
            display: grid;
            grid-template-columns: 88px minmax(0, 1fr);
            gap: 12px;
            align-items: start;
            padding: 12px 0;
            border-bottom: 1px solid var(--line);
            min-width: 0;
        }
        .summary dt { margin: 0; color: var(--muted); font-size: 13px; line-height: 20px; font-weight: 400; }
        .summary dd { margin: 0; color: var(--ink); font-size: 14px; line-height: 20px; font-weight: 500; overflow-wrap: anywhere; }
        .log { margin: 8px 0 0; padding: 0; list-style: none; min-width: 0; }
        .log li {
            padding: 10px 0;
            border-bottom: 1px solid var(--line);
            color: var(--muted);
            font-size: 13px;
            line-height: 20px;
            overflow-wrap: anywhere;
        }
        .actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            min-width: 0;
            padding: 12px 24px;
        }
        .actions [data-back] { margin-right: auto; }
        @media (max-width: 760px) {
            body { padding: 10px; }
            .shell { height: min(820px, calc(100dvh - 20px)); }
            .card {
                grid-template-columns: minmax(0, 1fr);
                grid-template-rows: auto minmax(0, 1fr);
                border-radius: 12px;
            }
            .rail {
                gap: 12px;
                padding: 14px 14px 12px;
                border-right: 0;
                border-bottom: 1px solid var(--line);
                overflow: visible;
            }
            ol.steps {
                flex: none;
                display: grid;
                grid-template-columns: repeat(6, minmax(0, 1fr));
                gap: 4px;
                overflow: visible;
            }
            ol.steps li {
                min-height: 0;
                grid-template-columns: minmax(0, 1fr);
                justify-items: center;
                gap: 4px;
                font-size: 10px;
                line-height: 14px;
                font-weight: 500;
                text-align: center;
            }
            ol.steps li::before { display: none; }
            ol.steps li .label {
                width: 100%;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .hint { display: none; }
            .panel-scroll { padding: 16px 16px 12px; }
            .actions {
                flex-direction: column;
                align-items: stretch;
                padding: 12px 16px;
            }
            .actions [data-back] { margin-right: 0; }
            .actions .primary { order: -1; }
            button, .link-btn, a.primary { width: 100%; }
            .dock .msg { margin-right: 16px; margin-left: 16px; }
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
                    <rect width="36" height="36" rx="10" fill="#111827"/>
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
