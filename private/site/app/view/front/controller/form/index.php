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

$ModCatform = $this->container->get('Mod\Catform\\'.ucfirst((string) $this->env));
$ModPage = $this->container->get('Mod\Page\\'.ucfirst((string) $this->env));

$this->beginSection('content');
if (!empty($this->result)) {
    ?>
    <ul>
        <?php
            if (!empty($this->{$ModCatform->modName.'Row'})) {
                ?>
                <li>
                    <a target="_blank"<?php echo $this->escapeAttr([
                        'href' => $this->uri([
                            'routeName' => $this->env.'.'.$ModCatform->modName.'.params',
                            'data' => [
                                'action' => 'print',
                                'params' => $this->{$ModCatform->modName.'Row'}['id'],
                                $ModCatform->modName.'_id' => $this->{$ModCatform->modName.'Row'}['id'],
                            ],
                        ]),
                        'title' => $this->{$ModCatform->modName.'Row'}['name'],
                    ]); ?>>
                        <?php echo $this->escape()->html(__('Header')); ?> - <?php echo $this->escape()->html($this->{$ModCatform->modName.'Row'}['name']); ?>
                    </a>
                </li>
                <?php
            }

    if (!empty($this->pageCatformResult)) {
        foreach ($this->pageCatformResult as $row) {
            $menuIds = explode(',', (string) $row['menu_ids']);
            $ModPage->filterValue->sanitize($menuIds, 'intvalArray');
            if (in_array(3, $menuIds, true)) {
                ?>
                    <li>
                        <a target="_blank"<?php echo $this->escapeAttr([
                            'href' => $this->uri([
                                'routeName' => $this->env.'.'.$ModPage->modName.'.params',
                                'data' => [
                                    'action' => 'print',
                                    'params' => $row['id'],
                                ],
                            ]),
                            'title' => $row['name'],
                        ]); ?>>
                            <?php echo $this->escape()->html(__('Header')); ?> - <?php echo $this->escape()->html($row['name']); ?>
                        </a>
                    </li>
                    <?php
            }
        }
    }
    ?>

        <?php foreach ($this->result as $n => $row) { ?>
            <li>
                <a target="_blank"<?php echo $this->escapeAttr([
                    'href' => $this->uri([
                        'routeName' => $this->env.'.'.$this->Mod->modName.'.params',
                        'data' => [
                            'action' => 'print',
                            'params' => $row['id'],
                        ],
                    ]),
                    'title' => $row['name'],
                ]); ?>>
                    <?php echo $this->escape()->html(sprintf(__('Form %1$d'), ++$n)); ?> - <?php echo $this->escape()->html($row['label']); ?>
                </a>
            </li>
        <?php } ?>
    </ul>
<?php
} else {
    echo '<p class="text-danger card-text">'.$this->escape()->html(__('No results found.')).'</p>'.PHP_EOL;
}
$this->endSection();

$this->beginSection('back-button');
$this->endSection();
