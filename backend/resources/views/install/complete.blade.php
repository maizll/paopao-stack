@extends('install.layout')

@section('title', '安装完成')
@section('heading', '安装完成')
@section('step_label', '第 6 步，共 6 步')
@section('progress', '100%')
@section('step_class_0', 'done')
@section('step_class_1', 'done')
@section('step_class_2', 'done')
@section('step_class_3', 'done')
@section('step_class_4', 'done')
@section('step_class_5', 'on')

@section('content')
    <h2>系统已经安装</h2>
    <p class="lead">安装锁已写入。向导不能再执行，请从下面的入口进入健康检查或管理后台。</p>
    <dl class="summary">
        <div><dt>站点</dt><dd>{{ $summary['site_name'] ?? '泡泡栈' }}</dd></div>
        <div><dt>平台超管</dt><dd>{{ $summary['admin_email'] ?? '—' }}</dd></div>
        <div><dt>租户</dt><dd>{{ ($summary['tenant_name'] ?? '—').' / '.($summary['tenant_slug'] ?? '—') }}</dd></div>
        <div><dt>演示数据</dt><dd>{{ !empty($summary['seed_demo']) ? '已写入' : '未写入' }}</dd></div>
        <div><dt>完成时间</dt><dd>{{ $summary['installed_at'] ?? '—' }}</dd></div>
    </dl>
    @if (!empty($summary['log']) && is_array($summary['log']))
        <ul class="log">
            @foreach ($summary['log'] as $line)
                <li>{{ $line }}</li>
            @endforeach
        </ul>
    @endif
    <div class="actions">
        <a class="primary" href="{{ url('/api/v1/system/health') }}">查看健康检查</a>
        <a class="link-btn ghost" href="http://127.0.0.1:43124/auth/login">管理后台登录</a>
        <a class="link-btn ghost" href="http://127.0.0.1:43126">用户端</a>
    </div>
@endsection
