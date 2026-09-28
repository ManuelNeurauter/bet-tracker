<?php
$pageTitle = 'Edit bet';
$breadcrumbs = [['Bets', '/bets'], [plainText($bet['event_name']), '/bets/' . $bet['id']]];
$content_view = __DIR__ . '/edit-content.php';
include __DIR__ . '/../layout.php';
