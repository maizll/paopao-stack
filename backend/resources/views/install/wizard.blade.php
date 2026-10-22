@extends('install.layout')

@section('content')
    <div class="panel" data-panel="0">
        <div class="panel-scroll">
            <h2>环境检测</h2>
            <p class="lead">必过项全部通过后才能继续。标成可选的项目失败时会用红字说明原因，但不会拦住安装。</p>
            <div id="checks" class="checks"></div>
        </div>
        <div class="dock">
            <p id="env-msg" class="msg" hidden></p>
            <div class="actions">
                <button class="ghost" id="recheck" type="button">重新检测</button>
                <button class="primary" id="env-next" type="button" disabled>下一步</button>
            </div>
        </div>
    </div>

    <div class="panel" data-panel="1" hidden>
        <div class="panel-scroll">
            <h2>数据库</h2>
            <p class="lead">填写 MySQL 或 MariaDB。必须先测试连接，成功后才能进入下一步。库不存在且账号有权限时，会自动创建。</p>
            <div class="grid">
                <div>
                    <label for="db-host">主机</label>
                    <input id="db-host" value="127.0.0.1" autocomplete="off">
                </div>
                <div>
                    <label for="db-port">端口</label>
                    <input id="db-port" value="3306" inputmode="numeric" autocomplete="off">
                </div>
            </div>
            <label for="db-name">数据库名</label>
            <input id="db-name" value="paopao" autocomplete="off">
            <div class="grid">
                <div>
                    <label for="db-user">账号</label>
                    <input id="db-user" value="paopao" autocomplete="off">
                </div>
                <div>
                    <label for="db-pass">密码</label>
                    <input id="db-pass" type="password" autocomplete="new-password">
                </div>
            </div>
        </div>
        <div class="dock">
            <p id="db-msg" class="msg" hidden></p>
            <div class="actions">
                <button class="ghost" type="button" data-back>上一步</button>
                <button class="ghost" id="db-test" type="button">测试连接</button>
                <button class="primary" id="db-next" type="button" disabled>下一步</button>
            </div>
        </div>
    </div>

    <div class="panel" data-panel="2" hidden>
        <div class="panel-scroll">
            <h2>管理员账号</h2>
            <p class="lead">这是平台超管，不属于任何一个租户。密码至少 8 位，请单独保管。</p>
            <label for="admin-name">昵称</label>
            <input id="admin-name" placeholder="平台超管" autocomplete="off">
            <label for="admin-email">邮箱</label>
            <input id="admin-email" type="email" placeholder="super@example.com" autocomplete="off">
            <div class="grid">
                <div>
                    <label for="admin-pass">密码</label>
                    <input id="admin-pass" type="password" autocomplete="new-password">
                </div>
                <div>
                    <label for="admin-pass2">确认密码</label>
                    <input id="admin-pass2" type="password" autocomplete="new-password">
                </div>
            </div>
        </div>
        <div class="dock">
            <p id="admin-msg" class="msg" hidden></p>
            <div class="actions">
                <button class="ghost" type="button" data-back>上一步</button>
                <button class="primary" id="admin-next" type="button">下一步</button>
            </div>
        </div>
    </div>

    <div class="panel" data-panel="3" hidden>
        <div class="panel-scroll">
            <h2>站点与租户</h2>
            <p class="lead">站点名称写入配置。租户是第一套圈子，标识会作为默认租户头。演示数据只建议在本机勾选。</p>
            <label for="site-name">站点名称</label>
            <input id="site-name" value="泡泡栈">
            <label for="site-url">站点地址</label>
            <input id="site-url" type="url" value="http://127.0.0.1:43125">
            <div class="grid">
                <div>
                    <label for="tenant-name">租户名称</label>
                    <input id="tenant-name" value="泡泡栈演示租户">
                </div>
                <div>
                    <label for="tenant-slug">租户标识</label>
                    <input id="tenant-slug" value="demo" autocomplete="off">
                </div>
            </div>
            <label class="choice">
                <input id="seed-demo" type="checkbox">
                <span>写入演示数据。包含示例成员、版块、一篇已发布帖子和一个示例软件。已有同名数据时按标识更新，不会另起一套库。已有正式内容时不要勾选，避免把版块帖子数和示例帖覆盖成种子数据。</span>
            </label>
        </div>
        <div class="dock">
            <p id="site-msg" class="msg" hidden></p>
            <div class="actions">
                <button class="ghost" type="button" data-back>上一步</button>
                <button class="primary" id="site-next" type="button">下一步</button>
            </div>
        </div>
    </div>

    <div class="panel" data-panel="4" hidden>
        <div class="panel-scroll">
            <h2>写入配置并安装</h2>
            <p class="lead">确认后会写入 .env、执行迁移、创建平台超管和租户。失败不会生成安装锁，可以返回修改后重试。</p>
            <dl class="summary" id="review"></dl>
            <ul class="log" id="run-log" hidden></ul>
        </div>
        <div class="dock">
            <p id="run-msg" class="msg" hidden></p>
            <div class="actions">
                <button class="ghost" type="button" data-back>上一步</button>
                <button class="primary" id="run" type="button">开始安装</button>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
let step = 0
let envReady = false
let dbReady = false
const draft = { database: null, admin: null, site: null }

async function post(url, body) {
  const response = await fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
    },
    body: JSON.stringify(body || {}),
  })
  const data = await response.json().catch(() => ({ message: '服务器没有返回可识别的结果。' }))
  return { ok: response.ok && data.code === 0, data }
}

function esc(value) {
  return String(value).replace(/[&<>"']/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]))
}

function show(id, kind, text) {
  const el = document.getElementById(id)
  el.hidden = false
  el.className = 'msg ' + kind
  el.textContent = text
}

function hide(id) {
  const el = document.getElementById(id)
  el.hidden = true
  el.textContent = ''
}

function render(next) {
  step = next
  document.querySelectorAll('[data-panel]').forEach((panel) => {
    panel.hidden = Number(panel.dataset.panel) !== step
  })
  document.getElementById('step-label').textContent = '第 ' + (step + 1) + ' 步，共 6 步'
  document.getElementById('progress-bar').style.width = (step === 0 ? 8 : (step / 5) * 100) + '%'
  document.querySelectorAll('#step-list li').forEach((item, index) => {
    item.classList.toggle('on', index === step)
    item.classList.toggle('done', index < step)
  })
  const scroller = document.querySelector('[data-panel="' + step + '"] .panel-scroll')
  if (scroller) scroller.scrollTop = 0
}

async function loadEnv() {
  hide('env-msg')
  const response = await fetch('/install/environment', { headers: { 'Accept': 'application/json' } })
  const data = await response.json()
  const box = document.getElementById('checks')
  box.innerHTML = ''
  const items = (data.data && data.data.items) || []
  envReady = !!(data.data && data.data.ready)
  items.forEach((item) => {
    const row = document.createElement('div')
    row.className = 'check' + (item.ok ? '' : ' bad')
    const pill = document.createElement('div')
    pill.className = 'pill ' + (item.ok ? 'ok' : 'bad')
    pill.textContent = item.ok ? (item.level === 'optional' ? '可选' : '通过') : (item.level === 'optional' ? '可选失败' : '失败')
    const body = document.createElement('div')
    const name = document.createElement('span')
    name.className = 'name'
    name.textContent = item.label
    const reason = document.createElement('div')
    reason.className = 'reason'
    reason.textContent = item.message
    body.append(name, reason)
    row.append(pill, body)
    box.append(row)
  })
  document.getElementById('env-next').disabled = !envReady
  if (!envReady) show('env-msg', 'bad', '还有必过项没有通过。请按红字原因处理后再检测。')
}

function databaseBody() {
  return {
    host: document.getElementById('db-host').value.trim(),
    port: Number(document.getElementById('db-port').value),
    database: document.getElementById('db-name').value.trim(),
    username: document.getElementById('db-user').value.trim(),
    password: document.getElementById('db-pass').value,
  }
}

document.getElementById('recheck').addEventListener('click', loadEnv)
document.getElementById('env-next').addEventListener('click', () => { if (envReady) render(1) })
;['db-host', 'db-port', 'db-name', 'db-user', 'db-pass'].forEach((id) => {
  document.getElementById(id).addEventListener('input', () => {
    dbReady = false
    draft.database = null
    document.getElementById('db-next').disabled = true
    hide('db-msg')
  })
})
document.getElementById('db-test').addEventListener('click', async () => {
  hide('db-msg')
  const button = document.getElementById('db-test')
  button.disabled = true
  const result = await post('/install/database/test', databaseBody())
  button.disabled = false
  if (!result.ok) {
    dbReady = false
    document.getElementById('db-next').disabled = true
    show('db-msg', 'bad', result.data.message || '连接失败')
    return
  }
  dbReady = true
  draft.database = { ...databaseBody(), server: result.data.data.server }
  document.getElementById('db-next').disabled = false
  show('db-msg', 'ok', result.data.message)
})
document.getElementById('db-next').addEventListener('click', () => { if (dbReady) render(2) })
document.getElementById('admin-next').addEventListener('click', async () => {
  hide('admin-msg')
  const result = await post('/install/admin', {
    nickname: document.getElementById('admin-name').value.trim(),
    email: document.getElementById('admin-email').value.trim(),
    password: document.getElementById('admin-pass').value,
    password_confirmation: document.getElementById('admin-pass2').value,
  })
  if (!result.ok) {
    show('admin-msg', 'bad', result.data.message || '管理员信息没有通过')
    return
  }
  draft.admin = result.data.data
  render(3)
})
document.getElementById('site-next').addEventListener('click', async () => {
  hide('site-msg')
  const result = await post('/install/site', {
    site_name: document.getElementById('site-name').value.trim(),
    app_url: document.getElementById('site-url').value.trim(),
    tenant_name: document.getElementById('tenant-name').value.trim(),
    tenant_slug: document.getElementById('tenant-slug').value.trim(),
    seed_demo: document.getElementById('seed-demo').checked,
  })
  if (!result.ok) {
    show('site-msg', 'bad', result.data.message || '站点信息没有通过')
    return
  }
  draft.site = result.data.data
  const review = document.getElementById('review')
  const rows = [
    ['数据库', draft.database.host + ':' + draft.database.port + ' / ' + draft.database.database],
    ['服务器', draft.database.server || '已测试'],
    ['管理员', draft.admin.nickname + ' · ' + draft.admin.email],
    ['站点', draft.site.site_name],
    ['地址', draft.site.app_url],
    ['租户', draft.site.tenant_name + ' / ' + draft.site.tenant_slug],
    ['演示数据', draft.site.seed_demo ? '写入' : '不写入'],
  ]
  review.innerHTML = rows.map(([key, value]) => '<div><dt>' + esc(key) + '</dt><dd>' + esc(value) + '</dd></div>').join('')
  hide('run-msg')
  document.getElementById('run-log').hidden = true
  render(4)
})
document.getElementById('run').addEventListener('click', async () => {
  const button = document.getElementById('run')
  button.disabled = true
  hide('run-msg')
  show('run-msg', 'ok', '正在写入配置并迁移，请稍候。')
  try {
    const result = await post('/install/run', {})
    if (result.ok && result.data.data && result.data.data.log) {
      const log = document.getElementById('run-log')
      log.hidden = false
      log.innerHTML = result.data.data.log.map((line) => '<li>' + esc(line) + '</li>').join('')
    }
    if (!result.ok && result.data.code !== 40901) {
      button.disabled = false
      show('run-msg', 'bad', result.data.message || '安装没有完成')
      return
    }
  } catch (error) {
    show('run-msg', 'ok', '配置已提交。若服务刚重启，正在打开结果页。')
  }
  window.location.href = '/install'
})
document.querySelectorAll('[data-back]').forEach((button) => {
  button.addEventListener('click', () => render(Math.max(0, step - 1)))
})
render(0)
loadEnv()
</script>
@endsection
