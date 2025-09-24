<?php declare(strict_types=1); ?>
<td>
    <?php if ('' !== trim((string) $row[$key])) {
        $nameRichtext = $this->helper->Nette()->Strings()->truncate(trim(strip_tags((string) ($row['formfield_name'] ?? '').' '.($row['formfield_richtext'] ?? ''))), 50);

        echo $row['formfield_id'].' - '.(!empty($nameRichtext) ? $nameRichtext : '').' (';

        if (method_exists($this->container->get('Mod\Formfield\\'.ucfirst((string) $this->env)), 'getFieldTypes') && is_callable([$this->container->get('Mod\Formfield\\'.ucfirst((string) $this->env)), 'getFieldTypes'])) {
            echo $this->container->get('Mod\Formfield\\'.ucfirst((string) $this->env))->getFieldTypes()[$row[$key]] ?? $row[$key];
        } else {
            echo $row[$key];
        }

        echo ')';
    } ?>
</td>
