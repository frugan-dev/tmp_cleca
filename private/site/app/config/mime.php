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

return [
    'file.allowedTypes' => [
        // '*' => 'application/x-empty',

        // Image formats
        // 'jpg|jpeg|jpe' => 'image/jpeg',
        // 'jpg' => 'image/jpg',
        // 'pjpeg' => 'image/pjpeg',
        // 'gif' => 'image/gif',
        // 'png' => 'image/png',
        // 'bmp' => 'image/bmp',
        // 'tif|tiff' => 'image/tiff',
        // 'ico' => 'image/x-icon',

        // Video formats
        // 'asf|asx' => 'video/x-ms-asf',
        // 'wmv' => 'video/x-ms-wmv',
        // 'wmx' => 'video/x-ms-wmx',
        // 'wm' => 'video/x-ms-wm',
        // 'avi' => 'video/avi',
        // 'divx' => 'video/divx',
        // 'flv' => 'video/x-flv',
        // 'mov|qt' => 'video/quicktime',
        // 'mpeg|mpg|mpe' => 'video/mpeg',
        // 'mp4|m4v' => 'video/mp4',
        // 'ogv' => 'video/ogg',
        // 'webm' => 'video/webm',
        // 'mkv' => 'video/x-matroska',

        // Text formats
        // 'txt' => 'text/plain',
        // 'txt|asc|c|cc|h' => 'text/plain',
        // 'csv' => 'text/csv',
        // 'tsv' => 'text/tab-separated-values',
        // 'ics' => 'text/calendar',
        // 'rtx' => 'text/richtext',
        // 'css' => 'text/css',
        // 'htm|html' => 'text/html',
        // 'rtf|rtf' => 'text/rtf',

        // Audio formats
        // 'mp3|m4a|m4b' => 'audio/mpeg',
        // 'ra|ram' => 'audio/x-realaudio',
        // 'wav' => 'audio/wav',
        // 'ogg|oga' => 'audio/ogg',
        // 'mid|midi' => 'audio/midi',
        // 'wma' => 'audio/x-ms-wma',
        // 'wax' => 'audio/x-ms-wax',
        // 'mka' => 'audio/x-matroska',

        // Misc application formats
        // 'rtf' => 'application/rtf',
        // 'js' => 'application/javascript',
        'pdf' => 'application/pdf',
        // 'ps|ps' => 'application/ps',
        // 'ps' => 'application/postscript',
        // 'swf' => 'application/x-shockwave-flash',
        // 'class' => 'application/java',
        // 'tar' => 'application/x-tar',
        // 'zip' => 'application/zip',
        // 'gz|gzip' => 'application/x-gzip',
        // 'rar' => 'application/rar',
        // '7z' => 'application/x-7z-compressed',
        // 'exe' => 'application/x-msdownload',

        // MS Office formats
        // 'doc' => 'application/msword',
        // 'pot|pps|ppt' => 'application/vnd.ms-powerpoint',
        // 'wri' => 'application/vnd.ms-write',
        // 'xla|xls|xlt|xlw' => 'application/vnd.ms-excel',
        // 'mdb' => 'application/vnd.ms-access',
        // 'mpp' => 'application/vnd.ms-project',
        // 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        // 'docm' => 'application/vnd.ms-word.document.macroEnabled.12',
        // 'dotx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
        // 'dotm' => 'application/vnd.ms-word.template.macroEnabled.12',
        // 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        // 'xlsm' => 'application/vnd.ms-excel.sheet.macroEnabled.12',
        // 'xlsb' => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
        // 'xltx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
        // 'xltm' => 'application/vnd.ms-excel.template.macroEnabled.12',
        // 'xlam' => 'application/vnd.ms-excel.addin.macroEnabled.12',
        // 'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        // 'pptm' => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
        // 'ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
        // 'ppsm' => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
        // 'potx' => 'application/vnd.openxmlformats-officedocument.presentationml.template',
        // 'potm' => 'application/vnd.ms-powerpoint.template.macroEnabled.12',
        // 'ppam' => 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
        // 'sldx' => 'application/vnd.openxmlformats-officedocument.presentationml.slide',
        // 'sldm' => 'application/vnd.ms-powerpoint.slide.macroEnabled.12',
        // 'onetoc|onetoc2|onetmp|onepkg' => 'application/onenote',

        // OpenOffice formats
        // 'odt' => 'application/vnd.oasis.opendocument.text',
        // 'odp' => 'application/vnd.oasis.opendocument.presentation',
        // 'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        // 'odg' => 'application/vnd.oasis.opendocument.graphics',
        // 'odc' => 'application/vnd.oasis.opendocument.chart',
        // 'odb' => 'application/vnd.oasis.opendocument.database',
        // 'odf' => 'application/vnd.oasis.opendocument.formula',

        // WordPerfect formats
        // 'wp|wpd' => 'application/wordperfect',

        // iWork formats
        // 'key' => 'application/vnd.apple.keynote',
        // 'numbers' => 'application/vnd.apple.numbers',
        // 'pages' => 'application/vnd.apple.pages',
    ],

    'img.allowedTypes' => [
        // Image formats
        'jpg|jpeg|jpe' => 'image/jpeg',
        'jpg' => 'image/jpg',
        // 'pjpeg' => 'image/pjpeg',
        // 'gif' => 'image/gif',
        'png' => 'image/png',
        // 'bmp' => 'image/bmp',
        // 'tif|tiff' => 'image/tiff',
        // 'ico' => 'image/x-icon',
    ],
];
