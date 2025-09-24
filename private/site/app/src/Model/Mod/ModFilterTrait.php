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

namespace App\Model\Mod;

use Psr\Http\Message\RequestInterface;

trait ModFilterTrait
{
    public function filter(
        RequestInterface $request
    ): void {
        $action = $this->action;

        $this->filterValue->sanitize($action, 'string', '-', ' ');
        $this->filterValue->sanitize($action, 'titlecase');
        $this->filterValue->sanitize($action, 'string', ' ', '');

        foreach ($this->fieldsMonolang as $key => $val) {
            $filteredKey = $key;

            $this->filterValue->sanitize($filteredKey, 'string', ['_', '-'], ' ');
            $this->filterValue->sanitize($filteredKey, 'titlecase');
            $this->filterValue->sanitize($filteredKey, 'string', ' ', '');

            if (isset($val[static::$env]['skip']) && \in_array(__FUNCTION__, $val[static::$env]['skip'], true)) {
                continue;
            }

            if (method_exists($this, __FUNCTION__.$action.$filteredKey.ucfirst((string) static::$env)) && \is_callable([$this, __FUNCTION__.$action.$filteredKey.ucfirst((string) static::$env)])) {
                \call_user_func_array([$this, __FUNCTION__.$action.$filteredKey.ucfirst((string) static::$env)], [$key]);
            } elseif (method_exists($this, __FUNCTION__.$filteredKey.ucfirst((string) static::$env)) && \is_callable([$this, __FUNCTION__.$filteredKey.ucfirst((string) static::$env)])) {
                \call_user_func_array([$this, __FUNCTION__.$filteredKey.ucfirst((string) static::$env)], [$key]);
            } elseif (method_exists($this, __FUNCTION__.$action.$filteredKey) && \is_callable([$this, __FUNCTION__.$action.$filteredKey])) {
                \call_user_func_array([$this, __FUNCTION__.$action.$filteredKey], [$key]);
            } elseif (method_exists($this, __FUNCTION__.$filteredKey) && \is_callable([$this, __FUNCTION__.$filteredKey])) {
                \call_user_func_array([$this, __FUNCTION__.$filteredKey], [$key]);
            } elseif (method_exists($this, '_'.__FUNCTION__.'Default') && \is_callable([$this, '_'.__FUNCTION__.'Default'])) {
                \call_user_func_array([$this, '_'.__FUNCTION__.'Default'], [$key]);
            }
        }

        if (\count($this->fieldsMultilang) > 0) {
            foreach ($this->fieldsMultilang as $key => $val) {
                $filteredKey = $key;

                $this->filterValue->sanitize($filteredKey, 'string', ['_', '-'], ' ');
                $this->filterValue->sanitize($filteredKey, 'titlecase');
                $this->filterValue->sanitize($filteredKey, 'string', ' ', '');

                if (isset($val[static::$env]['skip']) && \in_array(__FUNCTION__, $val[static::$env]['skip'], true)) {
                    continue;
                }

                foreach ($this->lang->arr as $langId => $langRow) {
                    if (method_exists($this, __FUNCTION__.$action.$filteredKey.ucfirst((string) static::$env)) && \is_callable([$this, __FUNCTION__.$action.$filteredKey.ucfirst((string) static::$env)])) {
                        \call_user_func_array([$this, __FUNCTION__.$action.$filteredKey.ucfirst((string) static::$env)], [$key, $langId]);
                    } elseif (method_exists($this, __FUNCTION__.$filteredKey.ucfirst((string) static::$env)) && \is_callable([$this, __FUNCTION__.$filteredKey.ucfirst((string) static::$env)])) {
                        \call_user_func_array([$this, __FUNCTION__.$filteredKey.ucfirst((string) static::$env)], [$key, $langId]);
                    } elseif (method_exists($this, __FUNCTION__.$action.$filteredKey) && \is_callable([$this, __FUNCTION__.$action.$filteredKey])) {
                        \call_user_func_array([$this, __FUNCTION__.$action.$filteredKey], [$key, $langId]);
                    } elseif (method_exists($this, __FUNCTION__.$filteredKey) && \is_callable([$this, __FUNCTION__.$filteredKey])) {
                        \call_user_func_array([$this, __FUNCTION__.$filteredKey], [$key, $langId]);
                    } elseif (method_exists($this, '_'.__FUNCTION__.'Default') && \is_callable([$this, '_'.__FUNCTION__.'Default'])) {
                        \call_user_func_array([$this, '_'.__FUNCTION__.'Default'], [$key, $langId]);
                    }
                }
            }
        }
    }

    public function _filterDefault($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (isset($this->postData[$postDataKey]) && !isBlank($this->postData[$postDataKey])) {
            $this->filterData[$key] = [
                'value' => $this->postData[$postDataKey],
                'mode' => 'DEFAULT',
            ];
        }
    }

    public function _filterLeftLike($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (isset($this->postData[$postDataKey]) && !isBlank($this->postData[$postDataKey])) {
            $this->filterData[$key] = [
                'value' => $this->postData[$postDataKey],
                'mode' => 'LEFT_LIKE',
            ];
        }
    }

    public function _filterRightLike($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (isset($this->postData[$postDataKey]) && !isBlank($this->postData[$postDataKey])) {
            $this->filterData[$key] = [
                'value' => $this->postData[$postDataKey],
                'mode' => 'RIGHT_LIKE',
            ];
        }
    }

    public function _filterStrict($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (isset($this->postData[$postDataKey]) && !isBlank($this->postData[$postDataKey])) {
            $this->filterData[$key] = [
                'value' => $this->postData[$postDataKey],
                'mode' => 'STRICT',
            ];
        }
    }

    public function _filterInt($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (isset($this->postData[$postDataKey]) && !isBlank($this->postData[$postDataKey])) {
            $this->filterData[$key] = [
                'value' => (int) $this->postData[$postDataKey],
                'mode' => 'STRICT',
            ];
        }
    }

    public function _filterTime($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (isset($this->postData[$postDataKey]) && !isBlank($this->postData[$postDataKey])) {
            $this->filterData[$key] = [
                'value' => $this->postData[$postDataKey],
                'mode' => 'TIME',
            ];
        }
    }

    public function _filterDate($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (isset($this->postData[$postDataKey]) && !isBlank($this->postData[$postDataKey])) {
            $this->filterData[$key] = [
                'value' => $this->postData[$postDataKey],
                'mode' => 'DATE',
            ];
        }
    }

    public function filterId($key, $langId = null, $field = null): void
    {
        $this->_filterInt($key, $langId, $field);
    }

    public function filterUserId($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (isset($this->postData[$postDataKey]) && !isBlank($this->postData[$postDataKey])) {
            $this->filterData[$key] = [
                'key' => 'username',
                'value' => $this->postData[$postDataKey],
                'mode' => 'DEFAULT',
                'alias' => 'c.',
            ];
        }
    }

    public function filterTime($key, $langId = null, $field = null): void
    {
        $this->_filterTime($key, $langId, $field);
    }

    public function filterIdate($key, $langId = null, $field = null): void
    {
        $this->_filterDate($key, $langId, $field);
    }

    public function filterSdate($key, $langId = null, $field = null): void
    {
        $this->_filterDate($key, $langId, $field);
    }

    public function filterEdate($key, $langId = null, $field = null): void
    {
        $this->_filterDate($key, $langId, $field);
    }

    /*public function filterUsername($key, $langId = null, $field = null): void
    {
        $this->_filterStrict($key, $langId, $field);
    }*/

    public function filterMain($key, $langId = null, $field = null): void
    {
        $this->_filterInt($key, $langId, $field);
    }

    public function filterPreselected($key, $langId = null, $field = null): void
    {
        $this->_filterInt($key, $langId, $field);
    }

    public function filterActive($key, $langId = null, $field = null): void
    {
        $this->_filterInt($key, $langId, $field);
    }
}
