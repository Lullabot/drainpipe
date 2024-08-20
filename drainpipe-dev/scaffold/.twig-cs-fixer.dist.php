<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
  return;
}

if (is_file(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}
else {
    echo "Composer autoload file not found.\n";
    echo "You need to run 'composer install'.\n";
    exit(1);
}

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\File\FileUrlGenerator;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Template\TwigExtension;

$renderer = Mockery::mock(RendererInterface::class);
$urlGenerator = Mockery::mock(UrlGeneratorInterface::class);
$themeManager = Mockery::mock(ThemeManagerInterface::class);
$dateFormatter = Mockery::mock(DateFormatterInterface::class);
$fileUrlGenerator = Mockery::mock(FileUrlGenerator::class);

$ruleset = new TwigCsFixer\Ruleset\Ruleset();

// default standard.
$ruleset->addStandard(new TwigCsFixer\Standard\TwigCsFixer());

$config = new TwigCsFixer\Config\Config();
$config->setRuleset($ruleset);
$config->addTwigExtension(new TwigExtension($renderer, $urlGenerator, $themeManager, $dateFormatter, $fileUrlGenerator));

if (class_exists('\TwigStorybook\Twig\TwigExtension')) {
  $composer_json = json_decode(file_get_contents(__DIR__ . '/../../../../composer.json'), true);
  if (json_last_error()) {
    throw new \RuntimeException('Could not parse composer.json');
  }

  $web_root = $composer_json['extra']['drupal-scaffold']['locations']['web-root'];
  $config->addExtension(new \TwigStorybook\Twig\TwigExtension(new \TwigStorybook\Service\StoryCollector(), '/../../../../' . $web_root));
}

return $config;
