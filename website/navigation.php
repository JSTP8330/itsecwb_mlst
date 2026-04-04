<?php
// Phase 3: store nav; SHOW_PHASE3_NAV_LINKS is defined in session.php (true when store is live).
if (!defined('SHOW_PHASE3_NAV_LINKS')) {
    define('SHOW_PHASE3_NAV_LINKS', false);
}

$nav_category_slug = isset($_GET['category_id']) ? strtolower(trim((string) $_GET['category_id'])) : '';
// Support legacy comma-separated values: use first segment for active state
if ($nav_category_slug !== '' && strpos($nav_category_slug, ',') !== false) {
    $nav_category_slug = strtolower(trim(explode(',', $nav_category_slug)[0]));
}

function buildCategoryUrl($newCategory) {
    $categories = isset($_GET['category_id']) ? explode(',', (string) $_GET['category_id']) : [];
    $categories = array_map('trim', $categories);
    if (!in_array($newCategory, $categories, true)) {
        $categories[] = $newCategory;
    }
    return 'store.php?category_id=' . rawurlencode(implode(',', array_filter($categories)));
}

function isCategoryActive($categorySlug) {
    global $nav_category_slug;
    return ($nav_category_slug === strtolower((string) $categorySlug)) ? 'active' : '';
}
?>

<!-- NAVIGATION -->
		<nav id="navigation">
			<!-- container -->
			<div class="container">
				<!-- responsive-nav -->
				<div id="responsive-nav">
					<!-- NAV -->
					<ul class="main-nav nav navbar-nav">
					<li class="<?= ($currentPage == 'home') ? 'active' : '' ?>"><a href="index.php">Home</a></li>
					<?php if (SHOW_PHASE3_NAV_LINKS): ?>
					<li class="<?= ($currentPage == 'categories') ? 'active' : '' ?>"><a href="categoriespage.php">Categories</a></li>
					<li class="<?= ($currentPage == 'store' && $nav_category_slug === '') ? 'active' : '' ?>"><a href="store.php">All products</a></li>
					<li class="<?= isCategoryActive('keyboards') ?>"><a href="store.php?category_id=keyboards">Keyboards</a></li>
					<li class="<?= isCategoryActive('headphones') ?>"><a href="store.php?category_id=headphones">Headphones</a></li>
					<li class="<?= isCategoryActive('monitors') ?>"><a href="store.php?category_id=monitors">Monitors</a></li>
					<li class="<?= isCategoryActive('mice') ?>"><a href="store.php?category_id=mice">Mice</a></li>
					<?php endif; ?>
					</ul>
					<!-- /NAV -->
				</div>
				<!-- /responsive-nav -->
			</div>
			<!-- /container -->
		</nav>
		<!-- /NAVIGATION -->
