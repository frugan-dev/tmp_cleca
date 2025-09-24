<?php declare(strict_types=1);

$ModPage = $this->container->get('Mod\Page\\'.ucfirst((string) $this->env));
?>
<ul>
    <?php
    $result = array_merge($this->pageCatformResult ?? [], $this->pageResult ?? []);

if (!empty($result)) {
    foreach ($result as $row) {
        $menuIds = explode(',', (string) $row['menu_ids']);
        $ModPage->filterValue->sanitize($menuIds, 'intvalArray');
        if (in_array(2, $menuIds, true)) {
            if (!empty($row['level'])) {
                if (empty($foundRoot)) {
                    continue;
                }

                if (empty($foundChild)) {
                    echo '<ul>'.PHP_EOL;
                } else {
                    echo '</li>'.PHP_EOL;
                }

                $foundChild = true;
            } elseif (!empty($foundChild)) {
                $foundChild = false;

                echo '</ul>'.PHP_EOL;
                echo '</li>'.PHP_EOL;
            } elseif (!empty($found)) {
                echo '</li>'.PHP_EOL;
            }

            if (empty($row['level'])) {
                $foundRoot = true;
            }

            $found = true;

            echo '<li>'.PHP_EOL;
            ?>
                    <a<?php echo $this->escapeAttr([
                        'href' => $this->uri([
                            'routeName' => $this->env.'.'.$ModPage->modName.'.params',
                            'data' => [
                                'action' => 'view',
                                'params' => $row['id'],
                            ],
                        ]),
                        'title' => $row['name'],
                    ]); ?>>
                        <?php echo $this->escape()->html($row['label']); ?>
                    </a>
                <?php
        }
    }

    if (!empty($found)) {
        echo '</li>'.PHP_EOL;
    }
    if (!empty($foundChild)) {
        echo '</ul>'.PHP_EOL;
        echo '</li>'.PHP_EOL;
    }
}
?>
    <?php if (!empty($this->lang->arr[$this->lang->id]['privacyUrl'])) { ?>
        <li>
            <a target="_blank"<?php echo $this->escapeAttr([
                'href' => $this->lang->arr[$this->lang->id]['privacyUrl'],
                'title' => __('Privacy Policy'),
            ]); ?>>
                <?php echo $this->escape()->html(__('Privacy Policy')); ?>
            </a>
        </li>
    <?php } ?>
    <?php if (!empty($this->lang->arr[$this->lang->id]['cookieUrl'])) { ?>
        <li>
            <a target="_blank"<?php echo $this->escapeAttr([
                'href' => $this->lang->arr[$this->lang->id]['cookieUrl'],
                'title' => __('Cookie Policy'),
            ]); ?>>
                <?php echo $this->escape()->html(__('Cookie Policy')); ?>
            </a>
        </li>
    <?php } ?>
    <li>
        <a data-cc="c-settings" href="javascript:;"<?php echo $this->escapeAttr([
            'title' => __('Cookie preferences'),
        ]); ?>>
            <?php echo $this->escape()->html(__('Cookie preferences')); ?>
        </a>
    </li>
</ul>
