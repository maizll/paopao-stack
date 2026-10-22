<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '安装向导') · 泡泡栈</title>
    <style>
        :root {
            --ink: #1c1d21;
            --muted: #5c6370;
            --line: #e4e7ee;
            --bg: #f3f5f8;
            --card: #ffffff;
            --ok: #0f7b4c;
            --ok-bg: #f1faf4;
            --bad: #c4272f;
            --bad-bg: #fff1f1;
            --warn: #8a5a00;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background: var(--bg);
            color: var(--ink);
            font-family: "PingFang SC", "Noto Sans SC", "Source Han Sans SC", "Microsoft YaHei", sans-serif;
        }
        .wrap { max-width: 880px; margin: 0 auto; padding: 32px 20px 64px; }
        header.top { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; margin-bottom: 18px; }
        .brand { font-size: 13px; letter-spacing: 0.12em; color: var(--muted); }
        h1 { margin: 4px 0 0; font-size: 28px; font-weight: 700; }
        .step-label { color: var(--muted); font-size: 14px; }
        .progress { height: 4px; background: #e1e5ee; border-radius: 99px; overflow: hidden; margin: 14px 0 18px; }
        .progress > span { display: block; height: 100%; background: var(--ink); width: 0; }
        ol.steps { display: flex; gap: 8px; padding: 0; margin: 0 0 18px; list-style: none; flex-wrap: wrap; }
        ol.steps li {
            display: flex; align-items: center; gap: 8px;
            color: var(--muted); font-size: 13px;
        }
        ol.steps li .n {
            width: 22px; height: 22px; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            border: 1px solid var(--line); background: #fff; font-size: 12px;
        }
        ol.steps li.on { color: var(--ink); font-weight: 650; }
        ol.steps li.on .n, ol.steps li.done .n { background: var(--ink); color: #fff; border-color: var(--ink); }
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
            box-shadow: 0 12px 32px rgba(22, 27, 38, 0.05);
            padding: 28px;
        }
        h2 { margin: 0 0 8px; font-size: 20px; }
        .lead { margin: 0 0 18px; color: var(--muted); line-height: 1.6; font-size: 14px; }
        .checks { display: flex; flex-direction: column; gap: 8px; }
        .check {
            display: grid;
            grid-template-columns: 88px 1fr;
            gap: 12px;
            padding: 10px 12px;
            border: 1px solid var(--line);
            border-radius: 10px;
            align-items: start;
        }
        .pill { font-size: 12px; font-weight: 650; }
        .pill.ok { color: var(--ok); }
        .pill.bad { color: var(--bad); }
        .check .reason { font-size: 14px; line-height: 1.5; }
        .check.bad .reason { color: var(--bad); font-weight: 650; }
        .check .name { display: block; color: var(--ink); font-weight: 650; margin-bottom: 2px; }
        label { display: block; margin: 14px 0 6px; font-size: 13px; color: var(--muted); }
        input[type="text"], input[type="password"], input[type="url"], input[type="number"], input[type="email"] {
            width: 100%; height: 42px; border: 1px solid #d5dae3; border-radius: 10px;
            padding: 0 12px; font-size: 15px; color: var(--ink); background: #fff;
        }
        .grid { display: grid; grid-template-columns: 1fr 140px; gap: 12px; }
        .grid3 { display: grid; grid-template-columns: 1.4fr 0.8fr 1fr; gap: 12px; }
        .row { display: flex; gap: 10px; align-items: center; margin-top: 16px; }
        button, .link-btn {
            height: 40px; border-radius: 10px; border: 1px solid transparent;
            padding: 0 16px; font-size: 14px; font-weight: 650; cursor: pointer;
            text-decoration: none; display: inline-flex; align-items: center;
        }
        button.primary, a.primary { background: var(--ink); color: #fff; }
        button.ghost { background: #fff; color: var(--ink); border-color: var(--line); }
        button:disabled { opacity: 0.45; cursor: not-allowed; }
        .msg { margin-top: 14px; padding: 10px 12px; border-radius: 10px; font-size: 14px; line-height: 1.5; }
        .msg.ok { color: var(--ok); background: var(--ok-bg); border: 1px solid #b7e4c7; }
        .msg.bad { color: var(--bad); background: var(--bad-bg); border: 1px solid #f3c2c4; font-weight: 650; }
        .choice { display: flex; gap: 10px; align-items: flex-start; margin-top: 16px; padding: 12px; border: 1px solid var(--line); border-radius: 10px; }
        .choice input { margin-top: 3px; }
        .choice span { font-size: 14px; line-height: 1.5; }
        .summary { display: grid; gap: 8px; }
        .summary div { display: flex; justify-content: space-between; gap: 16px; padding: 10px 0; border-bottom: 1px solid var(--line); font-size: 14px; }
        .summary dt { color: var(--muted); }
        .log { margin: 16px 0 0; padding: 0; list-style: none; }
        .log li { padding: 8px 0; border-bottom: 1px solid var(--line); font-size: 14px; }
        .actions { display: flex; gap: 10px; margin-top: 22px; flex-wrap: wrap; }
        .foot { margin-top: 14px; color: var(--muted); font-size: 12px; line-height: 1.5; }
        @media (max-width: 720px) {
            .grid, .grid3, .check { grid-template-columns: 1fr; }
            header.top { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <header class="top">
        <div>
            <div class="brand">泡泡栈</div>
            <h1>@yield('heading', '安装向导')</h1>
        </div>
        <div class="step-label" id="step-label">@yield('step_label', '第 1 步，共 6 步')</div>
    </header>
    <div class="progress" aria-hidden="true"><span id="progress-bar" style="width: @yield('progress', '8%')"></span></div>
    <ol class="steps" id="step-list">
        @foreach (['环境检测', '数据库', '管理员', '站点', '安装', '完成'] as $index => $name)
            <li class="@yield('step_class_'.$index, '')"><span class="n">{{ $index + 1 }}</span>{{ $name }}</li>
        @endforeach
    </ol>
    <section class="card">
        @yield('content')
    </section>
    <p class="foot">安装完成后会生成 install.lock。之后打开本页只显示结果，不能重复安装。</p>
</div>
@yield('script')
</body>
</html>
