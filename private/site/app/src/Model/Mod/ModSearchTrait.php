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

trait ModSearchTrait
{
    public function search(
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

    public function _searchDefault($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (isset($this->postData['_search'])) {
            $this->searchData[$key] = [
                'value' => $this->postData['_search'],
                'mode' => 'DEFAULT',
            ];
        }
    }

    public function _searchLeftLike($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (isset($this->postData['_search'])) {
            $this->searchData[$key] = [
                'value' => $this->postData['_search'],
                'mode' => 'LEFT_LIKE',
            ];
        }
    }

    public function _searchRightLike($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (isset($this->postData['_search'])) {
            $this->searchData[$key] = [
                'value' => $this->postData['_search'],
                'mode' => 'RIGHT_LIKE',
            ];
        }
    }

    public function _searchStrict($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (isset($this->postData['_search'])) {
            $this->searchData[$key] = [
                'value' => $this->postData['_search'],
                'mode' => 'STRICT',
            ];
        }
    }

    public function _searchInt($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (isset($this->postData['_search'])) {
            $this->searchData[$key] = [
                'value' => (int) $this->postData['_search'],
                'mode' => 'STRICT',
            ];
        }
    }

    public function _searchTime($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (isset($this->postData['_search'])) {
            $this->searchData[$key] = [
                'value' => $this->postData['_search'],
                'mode' => 'TIME',
            ];
        }
    }

    public function _searchDate($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (isset($this->postData['_search'])) {
            $this->searchData[$key] = [
                'value' => $this->postData['_search'],
                'mode' => 'DATE',
            ];
        }
    }

    public function searchId($key, $langId = null, $field = null): void
    {
        $this->_searchInt($key, $langId, $field);
    }

    public function searchTime($key, $langId = null, $field = null): void
    {
        $this->_searchTime($key, $langId, $field);
    }

    public function searchIdate($key, $langId = null, $field = null): void
    {
        $this->_searchDate($key, $langId, $field);
    }

    public function searchSdate($key, $langId = null, $field = null): void
    {
        $this->_searchDate($key, $langId, $field);
    }

    public function searchEdate($key, $langId = null, $field = null): void
    {
        $this->_searchDate($key, $langId, $field);
    }
}
