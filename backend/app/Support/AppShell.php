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
     * @return array{modules: list<array{id: string, label: string, path: string, summary: string, skins: list<array{id: string, label: string}>}>, tabbars: list<array{id: string, label: string}>}
     */
    public static function catalog(): array
    {
        $modules = [];
        foreach (self::MODULES as $id => $meta) {
            $skins = [];
            foreach (self::SKINS[$id] as $skin => $label) {
                $skins[] = ['id' => $skin, 'label' => $label];
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
