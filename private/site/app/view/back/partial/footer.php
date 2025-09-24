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

if (!$this->hasSection('section-footer')) {
    $this->setSection('section-footer', $this->render('section-footer'));
}
echo $this->getSection('section-footer');
?>
            </main>
        </div>
    </section>
</div>

<footer class="mt-auto bg-light border-top small py-3">
    <div class="container-fluid">
        <div class="row text-muted">
            <div class="col">
                <p class="mb-1 mb-sm-0">
                    <?php echo $this->escape()->html(sprintf(
                        '%1$s %2$s',
                        $this->config['app.name'],
                        version()
                    )); ?>
                </p>
            </div>
            <div class="col-sm-auto">
                <p class="mb-0">
                    <?php printf(
                        $this->escape()->html(__('© Copyright %1$d %2$s All rights reserved.')),
                        $this->helper->Carbon()->now()->year,
                        '<a'.$this->escapeAttr([
                            'href' => $this->config['credits.url'],
                            'title' => $this->config['credits.url.title'],
                        ]).' target="_blank">'.$this->escape()->html($this->config['credits.name']).'</a>'
                    ); ?>
                </p>
            </div>
        </div>
    </div>

    <a class="scroll-to btn-scroll-top btn btn-primary rounded-circle<?php /* position-fixed */ ?>" href="#anchor-top"<?php echo $this->escapeAttr([
        'title' => __('Back to top'),
    ]); ?>>
        <i class="fas fa-arrow-up fa-lg"></i>
    </a>
</footer>
