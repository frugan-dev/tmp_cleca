<?php

declare(strict_types=1);

/*
 * This file is part of the Slim 4 PHP application.
 *
 * (ɔ) Frugan <dev@frugan.it>
 *
 * This source file is subject to the GNU GPLv3 license that is bundled
 * with this source code in the file COPYING.
 */

use PhpCsFixer\Config;
use PhpCsFixer\ConfigurationException\InvalidConfigurationException;
use PhpCsFixer\Finder;
use PhpCsFixer\FixerFactory;
use PhpCsFixer\RuleSet;

$header = <<<'EOF'
    This file is part of the Slim 4 PHP application.

    (ɔ) Frugan <dev@frugan.it>

    This source file is subject to the GNU GPLv3 license that is bundled
    with this source code in the file COPYING.
    EOF;

define('_ROOT', __DIR__);
define('_BOOT', __DIR__);
define('_PUBLIC', dirname(__DIR__, 2).'/public');

$_SERVER['APP_ENV'] = 'develop';

$app = require _ROOT.'/bootstrap.php';

// exclude will work only for directories, so if you need to exclude file, try notPath
// directories passed as exclude() argument must be relative to the ones defined with the in() method
$finder = Finder::create()
    ->in([_ROOT, _PUBLIC])
    ->exclude(['inc', 'patch', 'var', 'vendor'])
    ->append([__DIR__.'/.php-cs-fixer.dist.php'])
;

$config = new Config()
    ->setCacheFile(sys_get_temp_dir().'/.php_cs.cache')
    ->setRiskyAllowed(true)
    ->setUnsupportedPhpVersionAllowed(true)
    ->setRules([
        // https://mlocati.github.io/php-cs-fixer-configurator
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        '@PHP80Migration:risky' => true,
        '@PHP84Migration' => true,
        'general_phpdoc_annotation_remove' => ['annotations' => ['expectedDeprecation']],
        'header_comment' => ['header' => $header],
        // FIXME - inside some anonymous functions
        'no_useless_else' => false,
        // FIXME
        'static_lambda' => false,
    ])
    ->setFinder($finder)
;

// special handling of fabbot.io service if it's using too old PHP CS Fixer version
if (false !== getenv('FABBOT_IO')) {
    try {
        FixerFactory::create()
            ->registerBuiltInFixers()
            ->registerCustomFixers($config->getCustomFixers())
            ->useRuleSet(new RuleSet($config->getRules()))
        ;
    } catch (InvalidConfigurationException $e) {
        $config->setRules([]);
    } catch (UnexpectedValueException $e) {
        $config->setRules([]);
    } catch (InvalidArgumentException $e) {
        $config->setRules([]);
    }
}

return $config;
