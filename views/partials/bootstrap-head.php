<?php
$pageTitle = $pageTitle ?? 'Redmine';
$includeTheme = $includeTheme ?? true;
?>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars((string) $pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<?php if ($includeTheme): ?>
  <?php $themeVersion = @filemtime(__DIR__ . '/../../assets/theme.css') ?: time(); ?>
  <link href="/redmine/assets/theme.css?v=<?= (int)$themeVersion ?>" rel="stylesheet">
<?php endif; ?>
