<?php

namespace App\Support;

use App\Exceptions\ApiException;
use App\Models\SiteSetting;

/**
 * 租户的 App 形态：勾选页面、每页皮肤、底栏样式、自定义页与子页。
 * 实际用到的页面才会被壳层下发，关掉的页面在下次打包里去掉。
 */
final class AppShell
{
    public const MAX_PAGES = 20;

    /**
     * @var array<string, array{label: string, path: string, summary: string}>
     */
    public const MODULES = [
        'home' => ['label' => '首页', 'path' => 'pages/home/home', 'summary' => '社区动态与内容聚合'],
        'community' => ['label' => '社区', 'path' => 'pages/community/community', 'summary' => '圈子动态与帖子互动'],
        'discover' => ['label' => '发现', 'path' => 'pages/discover/discover', 'summary' => '货架、宫格与应用发现'],
        'mall' => ['label' => '商城', 'path' => 'pages/mall/home', 'summary' => '商品展示和下单'],
        'messages' => ['label' => '消息', 'path' => 'pages/messages/messages', 'summary' => '站内消息和通知中心'],
        'mine' => ['label' => '我的', 'path' => 'pages/mine/mine', 'summary' => '个人中心和账号管理'],
    ];

    /**
     * @var array<string, array<string, string>>
     */
    public const SKINS = [
        'home' => ['feed' => '视频号', 'compact' => '单列', 'magazine' => '双列', 'haosns' => 'HaoSNS'],
        'community' => ['board' => '版块', 'compact' => '紧凑', 'forum' => '论坛', 'haosns' => 'HaoSNS'],
        'discover' => ['shelf' => '货架', 'grid' => '宫格', 'list' => '列表', 'haosns' => 'HaoSNS'],
        'mall' => ['card' => '卡片', 'compact' => '紧凑', 'poster' => '海报', 'haosns' => 'HaoSNS'],
        'messages' => ['list' => '列表', 'compact' => '紧凑', 'bubble' => '气泡', 'haosns' => 'HaoSNS'],
        'mine' => ['plain' => '小红书', 'card' => '卡片', 'grid' => '宫格', 'haosns' => 'HaoSNS'],
    ];

    /**
     * 旗舰展示清单（UI 主推）。遗留 id 仍留在 SKINS 里，当前租户继续可用。
     *
     * @var array<string, list<string>>
     */
    public const FLAGSHIP_SKINS = [
        'home' => ['magazine', 'feed', 'haosns'],
        'community' => ['forum', 'haosns'],
        'discover' => ['grid', 'haosns'],
        'mall' => ['poster', 'haosns'],
        'messages' => ['list', 'bubble'],
        'mine' => ['card', 'haosns'],
    ];

    /**
     * 仅用于新建默认，或用户主动放弃遗留皮肤之后的建议映射。
     * normalize() 与 current() 读路径禁止套用。
     *
     * @var array<string, array<string, string>>
     */
    public const LEGACY_SKIN_MAP = [
        'home' => ['compact' => 'magazine'],
        'community' => ['board' => 'forum', 'compact' => 'forum'],
        'discover' => ['shelf' => 'grid', 'list' => 'haosns'],
        'mall' => ['card' => 'haosns', 'compact' => 'haosns'],
        'messages' => ['compact' => 'list', 'haosns' => 'list'],
        'mine' => ['plain' => 'card', 'grid' => 'card'],
    ];

    /**
     * 产品定位。禁止写成「标题更紧」这类换皮句。
     *
     * @var array<string, array<string, string>>
     */
    private const SKIN_BLURBS = [
        'home' => [
            'feed' => '全屏视频舞台，作者和互动贴在画面上。',
            'compact' => '单列信息流，封面跟在正文下面。',
            'magazine' => '关注、推荐、最新和搜索，双列封面瀑布。',
            'haosns' => '单列社区动态，头像、封面和互动在同一张卡。',
        ],
        'community' => [
            'board' => '按版块进帖，一个版一块地盘。',
            'compact' => '高密度帖子行，适合快速扫版。',
            'forum' => '论坛信息卡，话题按楼层往下读。',
            'haosns' => '圈子发现流，搜索、关注和横滑版块。',
        ],
        'discover' => [
            'shelf' => '货架陈列，分类横滑后再点进应用。',
            'grid' => '真三列宫格，图标和名称都露出来。',
            'list' => '搜索结果长列表，一行一条。',
            'haosns' => '搜索发现，热词和内容卡混在一列。',
        ],
        'mall' => [
            'card' => '商品卡同时给出封面、说明和价格。',
            'compact' => '价签列表，一行看完入口和价格。',
            'poster' => '每件商品一张通栏大图，价格跟在图下。',
            'haosns' => '右图商品卡，标题和价格排在左侧。',
        ],
        'messages' => [
            'list' => '通知入口加上私信会话列表。',
            'compact' => '高密度通知行，一屏扫完未读。',
            'bubble' => '左右气泡的真实会话。',
            'haosns' => '消息做成动态卡，头像和正文在一起。',
        ],
        'mine' => [
            'plain' => '浅色头图的个人主页。',
            'card' => '头图加上双列笔记封面。',
            'grid' => '三列快捷入口的个人中心。',
            'haosns' => '头图、成就标和动态贴在一起。',
        ],
    ];

    /**
     * @var array<string, string>
     */
    public const TABBARS = [
        'labeled' => '图标+文字',
        'icon' => '仅图标',
        'raised' => '中间凸起',
    ];

    /**
     * @return array{modules: list<string>, skins: array<string, string>, tabbar: string, customPages: list<array{id: string, label: string, path: string, summary: string}>, subpages: array<string, list<array{id: string, label: string, path: string}>>}
     */
    public static function defaults(): array
    {
        $modules = array_keys(self::MODULES);
        $skins = [];
        foreach ($modules as $id) {
            $skins[$id] = $id === 'community' ? 'haosns' : (string) array_key_first(self::SKINS[$id]);
        }

        return [
            'modules' => $modules,
            'skins' => $skins,
            'tabbar' => 'labeled',
            'customPages' => [],
            'subpages' => self::defaultSubpages($modules),
        ];
    }

    /**
     * @param  list<string>  $moduleIds
     * @return array<string, list<array{id: string, label: string, path: string}>>
     */
    private static function defaultSubpages(array $moduleIds): array
    {
        $out = [];
        foreach ($moduleIds as $id) {
            if (! isset(self::MODULES[$id])) {
                continue;
            }
            $meta = self::MODULES[$id];
            $out[$id] = [
                [
                    'id' => $id.'_main',
                    'label' => $meta['label'],
                    'path' => $meta['path'],
                ],
            ];
        }

        return $out;
    }

    /**
     * @return array{modules: list<string>, skins: array<string, string>, tabbar: string, customPages: list<array{id: string, label: string, path: string, summary: string}>, subpages: array<string, list<array{id: string, label: string, path: string}>>}
     */
    public static function current(): array
    {
        $saved = SiteSetting::query()->first()?->shell;
        if (! is_array($saved)) {
            return self::defaults();
        }
        try {
            return self::normalize($saved);
        } catch (ApiException) {
            return self::defaults();
        }
    }

    /**
     * @param  array{modules: list<string>, skins: array<string, string>, tabbar: string, customPages?: list<array{id: string, label: string, path: string, summary?: string}>, subpages?: array<string, list<array{id: string, label: string, path: string}>>}  $shell
     * @return array<string, mixed>
     */
    public static function present(array $shell): array
    {
        return [
            'modules' => $shell['modules'],
            'skins' => $shell['skins'],
            'tabbar' => $shell['tabbar'],
            'customPages' => $shell['customPages'],
            'subpages' => $shell['subpages'],
            'catalog' => self::catalog(),
            'pack' => self::trim($shell),
            'limits' => ['max_pages' => self::MAX_PAGES],
        ];
    }

    /**
     * @return array{modules: list<array{id: string, label: string, path: string, summary: string, skins: list<array{id: string, label: string, flagship: bool, thumb: string, blurb: string}>}>, tabbars: list<array{id: string, label: string}>}
     */
    public static function catalog(): array
    {
        $modules = [];
        foreach (self::MODULES as $id => $meta) {
            $flagship = array_fill_keys(self::FLAGSHIP_SKINS[$id], true);
            $skins = [];
            foreach (self::SKINS[$id] as $skin => $label) {
                $skins[] = [
                    'id' => $skin,
                    'label' => $label,
                    'flagship' => isset($flagship[$skin]),
                    'thumb' => '/admin/shell-thumbs/'.$id.'-'.$skin.'.png',
                    'blurb' => self::SKIN_BLURBS[$id][$skin],
                ];
            }
            $modules[] = [
                'id' => $id,
                'label' => $meta['label'],
                'path' => $meta['path'],
                'summary' => $meta['summary'],
                'skins' => $skins,
            ];
        }
        $tabbars = [];
        foreach (self::TABBARS as $id => $label) {
            $tabbars[] = ['id' => $id, 'label' => $label];
        }

        return ['modules' => $modules, 'tabbars' => $tabbars];
    }

    /**
     * @return array{modules: list<string>, skins: array<string, string>, tabbar: string, customPages: list<array{id: string, label: string, path: string, summary: string}>, subpages: array<string, list<array{id: string, label: string, path: string}>>}
     */
    public static function normalize(mixed $input): array
    {
        if (! is_array($input)) {
            throw new ApiException(ApiCode::BUSINESS, '请提交页面配置。', 422);
        }
        $modules = $input['modules'] ?? null;
        if (! is_array($modules) || $modules === []) {
            throw new ApiException(ApiCode::BUSINESS, '至少保留一个页面。', 422);
        }
        $picked = [];
        foreach ($modules as $id) {
            if (! is_string($id) || ! isset(self::MODULES[$id])) {
                throw new ApiException(ApiCode::BUSINESS, '没有这个页面。', 422);
            }
            $picked[$id] = true;
        }
        $ordered = [];
        foreach (array_keys(self::MODULES) as $id) {
            if (isset($picked[$id])) {
                $ordered[] = $id;
            }
        }
        $skinsIn = is_array($input['skins'] ?? null) ? $input['skins'] : [];
        $skins = [];
        foreach ($ordered as $id) {
            $fallback = $id === 'community' ? 'haosns' : array_key_first(self::SKINS[$id]);
            // 已存 skin 原样保留。LEGACY_SKIN_MAP 只建议新建或用户放弃遗留后的默认，这里禁止套用。
            $skin = $skinsIn[$id] ?? $fallback;
            if (! is_string($skin) || ! isset(self::SKINS[$id][$skin])) {
                throw new ApiException(ApiCode::BUSINESS, self::MODULES[$id]['label'].'样式不存在。', 422);
            }
            $skins[$id] = $skin;
        }
        $tabbar = $input['tabbar'] ?? 'labeled';
        if (! is_string($tabbar) || ! isset(self::TABBARS[$tabbar])) {
            throw new ApiException(ApiCode::BUSINESS, '底栏样式不存在。', 422);
        }

        $customPages = self::normalizeCustomPages($input['customPages'] ?? []);
        $subpages = self::normalizeSubpages($input['subpages'] ?? [], $ordered, $customPages);

        $selectedCount = count($ordered) + count($customPages);
        if ($selectedCount > self::MAX_PAGES) {
            throw new ApiException(ApiCode::BUSINESS, '最多选择 '.self::MAX_PAGES.' 个页面。', 422);
        }

        return [
            'modules' => $ordered,
            'skins' => $skins,
            'tabbar' => $tabbar,
            'customPages' => $customPages,
            'subpages' => $subpages,
        ];
    }

    /**
     * @return list<array{id: string, label: string, path: string, summary: string}>
     */
    private static function normalizeCustomPages(mixed $input): array
    {
        if ($input === null || $input === []) {
            return [];
        }
        if (! is_array($input)) {
            throw new ApiException(ApiCode::BUSINESS, '自定义页面格式不正确。', 422);
        }
        $out = [];
        $seen = [];
        foreach ($input as $row) {
            if (! is_array($row)) {
                throw new ApiException(ApiCode::BUSINESS, '自定义页面格式不正确。', 422);
            }
            $id = isset($row['id']) && is_string($row['id']) ? trim($row['id']) : '';
            $label = isset($row['label']) && is_string($row['label']) ? trim($row['label']) : '';
            $path = isset($row['path']) && is_string($row['path']) ? trim($row['path']) : '';
            $summary = isset($row['summary']) && is_string($row['summary']) ? trim($row['summary']) : '';
            if ($id === '' || ! preg_match('/^custom_[a-zA-Z0-9_-]{1,40}$/', $id)) {
                throw new ApiException(ApiCode::BUSINESS, '自定义页面标识不合法。', 422);
            }
            if (isset(self::MODULES[$id]) || isset($seen[$id])) {
                throw new ApiException(ApiCode::BUSINESS, '自定义页面标识重复。', 422);
            }
            if ($label === '' || mb_strlen($label) > 20) {
                throw new ApiException(ApiCode::BUSINESS, '自定义页面名称不合法。', 422);
            }
            if ($path === '' || mb_strlen($path) > 120 || ! preg_match('/^pages\/[a-zA-Z0-9_\/-]+$/', $path)) {
                throw new ApiException(ApiCode::BUSINESS, '自定义页面路径不合法。', 422);
            }
            if (mb_strlen($summary) > 60) {
                throw new ApiException(ApiCode::BUSINESS, '自定义页面说明过长。', 422);
            }
            $seen[$id] = true;
            $out[] = [
                'id' => $id,
                'label' => $label,
                'path' => $path,
                'summary' => $summary,
            ];
        }

        return $out;
    }

    /**
     * @param  list<string>  $moduleIds
     * @param  list<array{id: string, label: string, path: string, summary: string}>  $customPages
     * @return array<string, list<array{id: string, label: string, path: string}>>
     */
    private static function normalizeSubpages(mixed $input, array $moduleIds, array $customPages): array
    {
        $raw = is_array($input) ? $input : [];
        $parents = $moduleIds;
        foreach ($customPages as $page) {
            $parents[] = $page['id'];
        }
        $out = [];
        foreach ($parents as $parentId) {
            $rows = $raw[$parentId] ?? null;
            if (! is_array($rows) || $rows === []) {
                if (isset(self::MODULES[$parentId])) {
                    $out[$parentId] = self::defaultSubpages([$parentId])[$parentId];
                } else {
                    $custom = null;
                    foreach ($customPages as $page) {
                        if ($page['id'] === $parentId) {
                            $custom = $page;
                            break;
                        }
                    }
                    $out[$parentId] = $custom ? [[
                        'id' => $parentId.'_main',
                        'label' => $custom['label'],
                        'path' => $custom['path'],
                    ]] : [];
                }

                continue;
            }
            $normalized = [];
            $seen = [];
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    throw new ApiException(ApiCode::BUSINESS, '子页面格式不正确。', 422);
                }
                $id = isset($row['id']) && is_string($row['id']) ? trim($row['id']) : '';
                $label = isset($row['label']) && is_string($row['label']) ? trim($row['label']) : '';
                $path = isset($row['path']) && is_string($row['path']) ? trim($row['path']) : '';
                if ($id === '' || ! preg_match('/^[a-zA-Z0-9_-]{1,48}$/', $id) || isset($seen[$id])) {
                    throw new ApiException(ApiCode::BUSINESS, '子页面标识不合法。', 422);
                }
                if ($label === '' || mb_strlen($label) > 20) {
                    throw new ApiException(ApiCode::BUSINESS, '子页面名称不合法。', 422);
                }
                if ($path === '' || mb_strlen($path) > 120 || ! preg_match('/^pages\/[a-zA-Z0-9_\/-]+$/', $path)) {
                    throw new ApiException(ApiCode::BUSINESS, '子页面路径不合法。', 422);
                }
                $seen[$id] = true;
                $normalized[] = ['id' => $id, 'label' => $label, 'path' => $path];
            }
            if (count($normalized) > 12) {
                throw new ApiException(ApiCode::BUSINESS, '每个页面最多 12 个子页。', 422);
            }
            $out[$parentId] = $normalized;
        }

        return $out;
    }

    /**
     * 实际下发的壳层：只带启用的内置页和自定义页。
     *
     * @param  array{modules: list<string>, skins: array<string, string>, tabbar: string, customPages?: list<array{id: string, label: string, path: string, summary?: string}>, subpages?: array<string, list<array{id: string, label: string, path: string}>>}  $shell
     * @return array{tabs: list<array{id: string, label: string, path: string, custom?: bool, subpages?: list<array{id: string, label: string, path: string}>}>, omitted: list<array{id: string, label: string, path: string}>}
     */
    public static function trim(array $shell): array
    {
        $enabled = array_fill_keys($shell['modules'], true);
        $tabs = [];
        $omitted = [];
        $subpages = is_array($shell['subpages'] ?? null) ? $shell['subpages'] : [];
        foreach (self::MODULES as $id => $meta) {
            $row = [
                'id' => $id,
                'label' => $meta['label'],
                'path' => $meta['path'],
                'subpages' => $subpages[$id] ?? [],
            ];
            if (isset($enabled[$id])) {
                $tabs[] = $row;
            } else {
                $omitted[] = ['id' => $id, 'label' => $meta['label'], 'path' => $meta['path']];
            }
        }
        foreach ($shell['customPages'] ?? [] as $page) {
            $tabs[] = [
                'id' => $page['id'],
                'label' => $page['label'],
                'path' => $page['path'],
                'custom' => true,
                'subpages' => $subpages[$page['id']] ?? [],
            ];
        }

        return ['tabs' => $tabs, 'omitted' => $omitted];
    }
}
