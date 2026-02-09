<?php
// Get current selected category_id(s) from query string
$current_categories = isset($_GET['category_id']) ? explode(',', $_GET['category_id']) : [];
function buildCategoryUrl($newCategory) {
	$categories = isset($_GET['category_id']) ? explode(',', $_GET['category_id']) : [];
	if (!in_array($newCategory, $categories)) {
		$categories[] = $newCategory;
	}
	return 'store.php?category_id=' . implode(',', array_filter($categories));
}

function isCategoryActive($category) {
	global $current_categories;
	return in_array($category, $current_categories) ? 'active' : '';
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
					<li class="<?= ($currentPage == 'categories') ? 'active' : '' ?>"><a href="categoriespage.php">Categories</a></li>
					<li class="<?= isCategoryActive(2) ?>"><a href="store.php?category_id=keyboards">Keyboards</a></li>
					<li class="<?= isCategoryActive(4) ?>"><a href="store.php?category_id=headphones">Headphones</a></li>
					<li class="<?= isCategoryActive(3) ?>"><a href="store.php?category_id=monitors">Monitors</a></li>
					<li class="<?= isCategoryActive(1) ?>"><a href="store.php?category_id=mice">Mice</a></li>
					</ul>
					<!-- /NAV -->
				</div>
				<!-- /responsive-nav -->
			</div>
			<!-- /container -->
		</nav>
		<!-- /NAVIGATION -->