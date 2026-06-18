<div class="console-nav" x-data="{ expandedGroups: {} }">
    <?php
    $navigationItems = $navigation ?? [];
    $userRole = auth()->user()?->role ?? '';
    $isMobile = isset($isMobile) ? $isMobile : false;
    ?>

    <?php foreach ($navigationItems as $index => $item): ?>
        <?php
        $children = $item['children'] ?? [];
        $isGroup = !empty($children);
        $isActive = !empty($item['active']);
        $groupKey = $item['key'] ?? 'group_'.$index;
        $itemIcon = $item['icon'] ?? 'spark';
        ?>

        <?php if ($isGroup): ?>
            <!-- Dropdown Group -->
            <div class="nav-group"
                 x-data="{ open: <?= $isActive ? 'true' : 'false' ?> }"
                 :class="{ 'is-expanded': open }">
                <button
                    type="button"
                    class="nav-group-trigger w-full"
                    @click="open = !open"
                    :aria-expanded="open.toString()"
                >
                    <span class="nav-item-icon <?= $isActive ? 'nav-item-icon--active' : '' ?>">
                        <?= mtq_icon((string) $itemIcon, 'h-5 w-5') ?>
                    </span>
                    <span class="nav-item-label"><?= e((string) ($item['label'] ?? 'Menu')) ?></span>
                    <span class="nav-chevron" :class="{ 'rotate-180': open }">
                        <?= mtq_icon('chevron-down', 'h-4 w-4') ?>
                    </span>
                </button>

                <div class="nav-group-children"
                     x-show="open"
                     x-collapse
                     x-cloak>
                    <div class="nav-children-inner">
                        <?php foreach ($children as $child): ?>
                            <?php
                            $childActive = !empty($child['active']);
                            $childIcon = $child['icon'] ?? 'spark';
                            ?>
                            <a href="<?= e((string) ($child['href'] ?? '#')) ?>"
                               class="nav-child-item <?= $childActive ? 'nav-child-item--active' : '' ?>">
                                <span class="nav-child-icon">
                                    <?= mtq_icon((string) $childIcon, 'h-4 w-4') ?>
                                </span>
                                <span class="nav-child-label"><?= e((string) ($child['label'] ?? 'Menu')) ?></span>
                                <?php if ($childActive): ?>
                                    <span class="nav-active-indicator"></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Single Link -->
            <a href="<?= e((string) ($item['href'] ?? '#')) ?>"
               class="nav-item <?= $isActive ? 'nav-item--active' : '' ?>">
                <span class="nav-item-icon <?= $isActive ? 'nav-item-icon--active' : '' ?>">
                    <?= mtq_icon((string) $itemIcon, 'h-5 w-5') ?>
                </span>
                <span class="nav-item-label"><?= e((string) ($item['label'] ?? 'Menu')) ?></span>
                <?php if ($isActive): ?>
                    <span class="nav-active-glow"></span>
                <?php endif; ?>
            </a>
        <?php endif; ?>

        <?php if ($index === 0): ?>
            <!-- Section divider after dashboard -->
            <div class="nav-section-divider">
                <span class="nav-section-label">Menu Utama</span>
            </div>
        <?php elseif ($index === 3): ?>
            <!-- Section divider before admin -->
            <div class="nav-section-divider">
                <span class="nav-section-label">Pengaturan</span>
            </div>
        <?php endif; ?>

    <?php endforeach; ?>
</div>

<style>
    /* Console Navigation Redesign */
    .console-nav {
        --nav-primary: #22d3ee;
        --nav-primary-glow: rgba(34, 211, 238, 0.25);
        --nav-bg-hover: rgba(255, 255, 255, 0.06);
        --nav-bg-active: linear-gradient(135deg, rgba(34, 211, 238, 0.15) 0%, rgba(59, 130, 246, 0.08) 100%);
        --nav-border-active: rgba(34, 211, 238, 0.3);
        --nav-text: #cbd5e1;
        --nav-text-hover: #f1f5f9;
        --nav-text-active: #ffffff;
        --nav-icon-bg: rgba(100, 116, 139, 0.2);
        --nav-icon-bg-active: rgba(34, 211, 238, 0.15);
    }

    /* Nav Items - Single Links */
    .nav-item {
        position: relative;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.75rem;
        border-radius: 0.75rem;
        border: 1px solid transparent;
        color: var(--nav-text);
        font-weight: 500;
        font-size: 0.8125rem;
        text-decoration: none;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
    }

    .nav-item:hover {
        background: var(--nav-bg-hover);
        color: var(--nav-text-hover);
        border-color: rgba(255, 255, 255, 0.08);
        transform: translateX(4px);
    }

    .nav-item:hover .nav-item-icon {
        background: rgba(34, 211, 238, 0.12);
    }

    .nav-item--active {
        background: var(--nav-bg-active);
        border-color: var(--nav-border-active);
        color: var(--nav-text-active);
        box-shadow: 0 4px 20px -4px var(--nav-primary-glow);
    }

    .nav-item--active .nav-item-icon {
        background: var(--nav-icon-bg-active);
        color: var(--nav-primary);
    }

    /* Nav Item Icon */
    .nav-item-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        border-radius: 0.5rem;
        background: var(--nav-icon-bg);
        color: #94a3b8;
        flex-shrink: 0;
        transition: all 0.2s ease;
    }

    .nav-item-icon--active {
        background: var(--nav-icon-bg-active);
        color: var(--nav-primary);
        box-shadow: 0 0 20px var(--nav-primary-glow);
    }

    /* Nav Item Label */
    .nav-item-label {
        flex: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Nav Group (Dropdown) */
    .nav-group {
        margin-bottom: 0.125rem;
    }

    .nav-group-trigger {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        width: 100%;
        padding: 0.5rem 0.75rem;
        border-radius: 0.75rem;
        border: 1px solid transparent;
        background: transparent;
        color: var(--nav-text);
        font-weight: 500;
        font-size: 0.8125rem;
        text-align: left;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .nav-group-trigger:hover {
        background: var(--nav-bg-hover);
        color: var(--nav-text-hover);
        border-color: rgba(255, 255, 255, 0.08);
    }

    .nav-group.is-expanded .nav-group-trigger {
        background: var(--nav-bg-active);
        border-color: var(--nav-border-active);
        color: var(--nav-text-active);
    }

    .nav-group.is-expanded .nav-item-icon {
        background: var(--nav-icon-bg-active);
        color: var(--nav-primary);
    }

    /* Chevron */
    .nav-chevron {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 1.375rem;
        height: 1.375rem;
        border-radius: 0.375rem;
        background: rgba(100, 116, 139, 0.15);
        color: #94a3b8;
        flex-shrink: 0;
        transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .nav-group.is-expanded .nav-chevron {
        background: rgba(34, 211, 238, 0.15);
        color: var(--nav-primary);
    }

    /* Group Children */
    .nav-group-children {
        overflow: hidden;
    }

    .nav-children-inner {
        padding: 0.25rem 0 0.25rem 0.75rem;
        margin-left: 0.75rem;
        border-left: 2px solid rgba(100, 116, 139, 0.15);
        display: flex;
        flex-direction: column;
        gap: 0.125rem;
    }

    /* Child Items */
    .nav-child-item {
        position: relative;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.75rem;
        border-radius: 0.625rem;
        border: 1px solid transparent;
        color: var(--nav-text);
        font-weight: 450;
        font-size: 0.75rem;
        text-decoration: none;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .nav-child-item:hover {
        background: var(--nav-bg-hover);
        color: var(--nav-text-hover);
        border-color: rgba(255, 255, 255, 0.06);
        transform: translateX(4px);
    }

    .nav-child-item:hover .nav-child-icon {
        background: rgba(34, 211, 238, 0.1);
    }

    .nav-child-item--active {
        background: var(--nav-bg-active);
        border-color: var(--nav-border-active);
        color: var(--nav-text-active);
    }

    .nav-child-item--active .nav-child-icon {
        background: rgba(34, 211, 238, 0.15);
        color: var(--nav-primary);
    }

    /* Child Icon */
    .nav-child-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 1.625rem;
        height: 1.625rem;
        border-radius: 0.375rem;
        background: var(--nav-icon-bg);
        color: #94a3b8;
        flex-shrink: 0;
        transition: all 0.2s ease;
    }

    .nav-child-label {
        flex: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Section Divider */
    .nav-section-divider {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.75rem 0.25rem;
        margin-top: 0.125rem;
    }

    .nav-section-divider::before,
    .nav-section-divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(100, 116, 139, 0.3), transparent);
    }

    .nav-section-label {
        font-size: 0.625rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: #64748b;
        white-space: nowrap;
    }

    /* Light mode adjustments */
    [data-theme='light'] .console-nav {
        --nav-primary: #0891b2;
        --nav-primary-glow: rgba(8, 145, 178, 0.2);
        --nav-bg-hover: rgba(0, 0, 0, 0.04);
        --nav-bg-active: linear-gradient(135deg, rgba(8, 145, 178, 0.1) 0%, rgba(59, 130, 246, 0.05) 100%);
        --nav-border-active: rgba(8, 145, 178, 0.25);
        --nav-text: #475569;
        --nav-text-hover: #0f172a;
        --nav-text-active: #0f172a;
        --nav-icon-bg: rgba(100, 116, 139, 0.12);
        --nav-icon-bg-active: rgba(8, 145, 178, 0.12);
    }

    [data-theme='light'] .nav-children-inner {
        border-left-color: rgba(100, 116, 139, 0.2);
    }

    [data-theme='light'] .nav-section-divider::before,
    [data-theme='light'] .nav-section-divider::after {
        background: linear-gradient(90deg, transparent, rgba(100, 116, 139, 0.25), transparent);
    }

    [data-theme='light'] .nav-item:hover,
    [data-theme='light'] .nav-group-trigger:hover,
    [data-theme='light'] .nav-child-item:hover {
        background: rgba(8, 145, 178, 0.06);
        border-color: rgba(8, 145, 178, 0.15);
    }
</style>
