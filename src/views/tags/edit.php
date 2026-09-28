<?php
$pageTitle = plainText($tag['name']);
$breadcrumbs = [['Tags', '/tags']];
$content_view = __DIR__ . '/edit-content.php';
include __DIR__ . '/../layout.php';
