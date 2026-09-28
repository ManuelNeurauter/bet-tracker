<?php
$pageTitle = plainText($bet['event_name']);
$breadcrumbs = [['Bets', '/bets']];
$content_view = __DIR__ . '/view-content.php';
include __DIR__ . '/../layout.php';
