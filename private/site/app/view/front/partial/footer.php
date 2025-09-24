<?php declare(strict_types=1);

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
                <?php
                /*if (!$this->hasSection('nav-aside')) {
                    $this->setSection('nav-aside', $this->render('nav-aside'));
                }*/
                if (!empty($buffer = $this->getSection('nav-aside'))) {
                    ?>
                <div class="col-md-4 col-lg-3">
                    <aside>
                        <?php echo $buffer; ?>
                    </aside>
                </div>
                <?php } ?>
            </div>
        </div>
    </section>
</div>

<footer class="mt-auto bg-primary bg-gradient bg-opacity-25 border-top small pt-3 pb-5">
    <div class="container">
        <div class="row gy-3">
            <div class="col-md order-md-last">
                <h6 class="fw-bold border-bottom border-dark-subtle d-inline-block pb-2 pe-2 mb-3">
                    <?php echo $this->escape()->html(__('Links')); ?>
                </h6>

                <?php
                    if (!$this->hasSection('nav-bottom')) {
                        $this->setSection('nav-bottom', $this->render('nav-bottom'));
                    }
echo $this->getSection('nav-bottom');
?>

                <hr>

                <p class="mb-0 text-muted fs-xs">
                    <?php printf(
                        $this->escape()->html(__('This site is protected by reCAPTCHA and the Google %1$s and %2$s apply.')),
                        '<a class="text-reset" href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer nofollow">'.$this->escape()->html(__('Privacy Policy')).'</a>',
                        '<a class="text-reset" href="https://policies.google.com/terms" target="_blank" rel="noopener noreferrer nofollow">'.$this->escape()->html(__('Terms of Service')).'</a>'
                    ); ?>
                </p>
            </div>
            <?php if (!empty($value = settingOrConfig('block.contact'))) { ?>
                <div class="col-md">
                    <h6 class="fw-bold border-bottom border-dark-subtle d-inline-block pb-2 pe-2 mb-3">
                        <?php echo $this->escape()->html(__('Contacts')); ?>
                    </h6>

                    <?php // https://developer.mozilla.org/en-US/docs/Web/HTML/Element/address?>
                    <address>
                        <?php echo $value; ?>
                    </address>
                </div>
<?php } ?>
            <div class="col-md order-md-first">
                <?php echo $this->render('address-company'); ?>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col">
                <?php
                                                                                                                                                                                                                                                                                                                                                    if (!$this->hasSection('social-bottom')) {
                                                                                                                                                                                                                                                                                                                                                        $this->setSection('social-bottom', $this->render('social'));
                                                                                                                                                                                                                                                                                                                                                    }
echo $this->getSection('social-bottom');
?>
            </div>
            <div class="col-sm-auto">
                <p class="mb-0 text-muted">
                    <?php printf(
                        $this->escape()->html(__('© Copyright %1$d %2$s All rights reserved.')),
                        $this->helper->Carbon()->now()->year,
                        '<a target="_blank"'.$this->escapeAttr([
                            'href' => $this->config['credits.url'],
                            'title' => $this->config['credits.url.title'],
                        ]).'>'.$this->escape()->html($this->config['credits.name']).'</a>'
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
