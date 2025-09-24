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
<div id="swagger-ui"></div>
<?php
// https://swagger.io/docs/specification/basic-structure/
// https://odan.github.io/2020/06/12/slim4-swagger-ui.html
// https://github.com/swagger-api/swagger-ui/
// https://zircote.github.io/swagger-php/
// https://editor.swagger.io
$this->scriptsFoot()->beginInternal();
echo '(() => {
    SwaggerUI({
        //url: "https://petstore.swagger.io/v2/swagger.json",
        spec: '.$this->helper->Nette()->Json()->encode($this->result).',
        dom_id: "#swagger-ui",
        docExpansion: "none",
        defaultModelsExpandDepth: -1,
    })
})();';
$this->scriptsFoot()->endInternal();

if (!empty($this->browscapInfo)) {
    if (!empty($this->browscapInfo->ismobiledevice)) {
        $this->styles()->beginInternal();
        echo '.swagger-ui .opblock .opblock-summary-path {
    max-width: calc(100% - 6rem);
}';
        $this->styles()->endInternal();
    }
}

$this->beginSection('title');
$this->endSection();
