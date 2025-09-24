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
?>
<div class="fancybox-hidden">
    <hr>

    <?php
    if (!$this->hasSection('nav-siblings')) {
        $this->setSection('nav-siblings', $this->render('nav-siblings'));
    }
echo $this->getSection('nav-siblings');

if (!$this->hasSection('back-button')) {
    $this->beginSection('back-button');

    if (!isset($this->backButton) || $this->backButton) {
        if (empty($this->backButtonUrl)) {
            $this->addData([
                'backButtonUrl' => $this->uri([
                    'routeName' => $this->env.'.'.$this->controller.'.params',
                    'data' => [
                        'action' => 'index',
                        'params' => implode(
                            '/',
                            array_merge(
                                $this->session->get('routeParamsArrWithoutPg', []),
                                [
                                    $this->session->get('pg', $this->pager->pg),
                                ]
                            )
                        ),
                    ],
                ]),
            ]);
        } ?>
            <div class="d-grid">
                <a class="btn btn-outline-secondary mx-sm-auto"<?php echo $this->escapeAttr([
                    'href' => $this->backButtonUrl,
                ]); ?>>
                    <?php echo $this->escape()->html(__('Back')); ?>
                </a>
            </div>
            <?php
    }

    $this->endSection();
}

echo $this->getSection('back-button');
?>
</div>
