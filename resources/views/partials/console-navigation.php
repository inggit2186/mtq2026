<?php
require_once __DIR__.'/icon.php';

$navigationItems = $navigation ?? [];
?>
<?php foreach ($navigationItems as $item): ?>
    <?php $children = $item['children'] ?? []; ?>
    <?php if ($children !== []): ?>
        <details class="group sidebar-dropdown"<?= !empty($item['active']) ? ' open' : '' ?>>
            <summary class="sidebar-link w-full cursor-pointer list-none justify-between <?= !empty($item['active']) ? 'sidebar-link-active' : '' ?>">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="icon-chip h-10 w-10 rounded-xl"><?= mtq_icon((string) ($item['icon'] ?? 'menu'), 'h-4 w-4') ?></span>
                    <span class="min-w-0 truncate"><?= e((string) ($item['label'] ?? 'Menu')) ?></span>
                </span>
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-slate-700/70 bg-slate-900/60 text-slate-400 transition group-open:rotate-180">
                    <?= mtq_icon('chevron-down', 'h-4 w-4') ?>
                </span>
            </summary>

            <div class="mt-2 space-y-2 pl-3">
                <?php foreach ($children as $child): ?>
                    <a href="<?= e((string) ($child['href'] ?? '#')) ?>" class="sidebar-sub-link <?= !empty($child['active']) ? 'sidebar-sub-link-active' : '' ?>">
                        <span class="icon-chip h-9 w-9 rounded-xl"><?= mtq_icon((string) ($child['icon'] ?? 'menu'), 'h-4 w-4') ?></span>
                        <span class="min-w-0 truncate"><?= e((string) ($child['label'] ?? 'Menu')) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </details>
    <?php else: ?>
        <a href="<?= e((string) ($item['href'] ?? '#')) ?>" class="sidebar-link <?= !empty($item['active']) ? 'sidebar-link-active' : '' ?>">
            <span class="icon-chip h-10 w-10 rounded-xl"><?= mtq_icon((string) ($item['icon'] ?? 'menu'), 'h-4 w-4') ?></span>
            <span class="min-w-0 truncate"><?= e((string) ($item['label'] ?? 'Menu')) ?></span>
        </a>
    <?php endif; ?>
<?php endforeach; ?>
