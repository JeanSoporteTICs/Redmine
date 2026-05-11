<?php
$pageTitle = $pageTitle ?? 'Redmine';
$includeTheme = $includeTheme ?? true;
?>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars((string) $pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <?php
    $faviconSvgVersion = @filemtime(__DIR__ . '/../../assets/favicon.svg') ?: time();
    $faviconIcoVersion = @filemtime(__DIR__ . '/../../assets/favicon.ico') ?: time();
  ?>
  <link rel="icon" type="image/x-icon" href="/redmine/favicon.ico?v=<?= (int)$faviconIcoVersion ?>">
  <link rel="shortcut icon" type="image/x-icon" href="/redmine/favicon.ico?v=<?= (int)$faviconIcoVersion ?>">
  <link rel="icon" type="image/svg+xml" href="/redmine/assets/favicon.svg?v=<?= (int)$faviconSvgVersion ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<?php if ($includeTheme): ?>
  <?php $themeVersion = @filemtime(__DIR__ . '/../../assets/theme.css') ?: time(); ?>
  <link href="/redmine/assets/theme.css?v=<?= (int)$themeVersion ?>" rel="stylesheet">
<?php endif; ?>
