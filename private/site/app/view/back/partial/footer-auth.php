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
                </main>
            </section>
        </div>
    </div>
</div>

<footer class="mt-auto small py-3">
    <div class="container">
        <p class="text-muted mb-0">
            <?php printf(
                $this->escape()->html(__('Powered by %1$s')),
                '<a target="_blank"'.$this->escapeAttr([
                    'href' => $this->config['credits.url'],
                    'title' => $this->config['credits.url.title'],
                ]).'>'.$this->escape()->html($this->config['credits.name']).'</a>'
            ); ?>
        </p>
    </div>
</footer>
