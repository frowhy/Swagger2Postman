<?php
namespace Frowhy;

use Ramsey\Uuid\Uuid;

/**
 * Created by PhpStorm.
 * User: frowhy
 * Date: 2016/11/2
 * Time: 16:56
 */
class Swagger2Postman
{
    private $swagger, $array;
    private           $schemes, $host, $basePath;
    private           $json;

    public function openFile($filename)
    {
        $handle        = fopen($filename, 'r');
        $this->swagger = fread($handle, filesize($filename));
        fclose($handle);
        unset($handle);

        return $this;
    }

    public function setSwagger($json)
    {
        $this->swagger = $json;

        return $this;
    }

    public function writeFile($filename)
    {
        if (!empty($this->json)) {
            $handle = fopen($filename, 'w');
            $state  = fwrite($handle, $this->json);
            fclose($handle);
            unset($handle);

            return $state;
        } else {
            return FALSE;
        }
    }

    public function getPostman()
    {
        if (!empty($this->json)) {
            return $this->json;
        } else {
            return FALSE;
        }
    }

    public function convertPostman()
    {
        $array      = $this->convertJson($this->swagger);
        $this->json = $this->setPostman($array);

        unset($array);

        return $this;
    }

    protected function convertJson($json)
    {
        return json_decode($json, TRUE);
    }

    protected function setPostman($array)
    {
        /**
         * 定义接口信息
         */
        $this->array                      = new \stdClass();
        $this->array->info['name']        = $array['info']['title'];
        $this->array->info['_postman_id'] = Uuid::uuid4();
        $this->array->info['description'] = $array['info']['description'];
        $this->array->info['schema']      = 'https://schema.getpostman.com/json/collection/v2.0.0/collection.json';

        /**
         * 定义网址前缀
         */
        $this->schemes  = $array['schemes'][0];
        $this->host     = $array['host'];
        $this->basePath = $array['basePath'];

        /**
         * 循环文件夹
         */
        if (isset($array['tags'])) {
            foreach ($array['tags'] as $tag) {
                $tmp['name']        = $tag['name'];
                $tmp['description'] = $tag['description'];

                $this->array->item[ $tag['name'] ] = $tmp;

                unset($tmp);
            }
        }

        /**
         * 循环接口
         */
        foreach ($array['paths'] as $path => $items) {
            $tmp['url'] = $this->schemes . '://' . $this->host . $this->basePath . $path;

            foreach ($items as $method => $item) {
                foreach ($item['tags'] as $tag) {
                    $this->array->item[ $tag ]['item'][ $path ]['item'][ $method ]['name']                   = isset($item['summary']) ? $item['summary'] : 'nil';
                    $this->array->item[ $tag ]['item'][ $path ]['item'][ $method ]['request']                = $tmp;
                    $this->array->item[ $tag ]['item'][ $path ]['item'][ $method ]['request']['method']      = $method;
                    $this->array->item[ $tag ]['item'][ $path ]['item'][ $method ]['request']['description'] = isset($item['description']) ? $item['description'] : 'nil';
                    if (isset($item['responses']['default']['headers'])) {
                        foreach ($item['responses']['default']['headers'] as $key => $value) {
                            $tmpArr['key']                                                                        = $key;
                            $tmpArr['value']                                                                      = $value['format'];
                            $tmpArr['description']                                                                = $value['description'];
                            $this->array->item[ $tag ]['item'][ $path ]['item'][ $method ]['request']['header'][] = $tmpArr;
                            unset($tmpArr);
                        }
                    }
                    if (isset($item['parameters'])) {
                        if (count($item['parameters']) == 1) {
                            if ($item['parameters'][0]['in'] == 'body' && (isset($item['parameters'][0]['schema']['$ref']) || isset($item['parameters'][0]['schema']['items']['$ref']))) {
                                $this->array->item[ $tag ]['item'][ $path ]['item'][ $method ]['request']['body']['mode'] = 'raw';
                                if (isset($item['parameters'][0]['schema']['type']) && $item['parameters'][0]['schema']['type'] == 'array') {
                                    $tmpArr = [];
                                    foreach ($item['parameters'][0]['schema']['items'] as $_item) {
                                        $tmpArr[] = $this->convertModel($array, $_item, FALSE);

                                    }
                                    $this->array->item[ $tag ]['item'][ $path ]['item'][ $method ]['request']['body']['raw'] = json_encode($tmpArr, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

                                } else {
                                    $this->array->item[ $tag ]['item'][ $path ]['item'][ $method ]['request']['body']['raw'] = $this->convertModel($array, $item['parameters'][0]['schema']['$ref']);
                                }
                            } elseif ($item['parameters'][0]['in'] == 'query') {
                                if ($item['parameters'][0]['type'] == 'array') {
                                    if (isset($item['parameters'][0]['items']) && isset($item['parameters'][0]['items']['enum'])) {
                                        $tmpArr['key']                                                                        = 'Content-Type';
                                        $tmpArr['value']                                                                      = 'application/x-www-form-urlencoded';
                                        $tmpArr['description']                                                                = '';
                                        $this->array->item[ $tag ]['item'][ $path ]['item'][ $method ]['request']['header'][] = $tmpArr;
                                        unset($tmpArr);
                                        $this->array->item[ $tag ]['item'][ $path ]['item'][ $method ]['request']['body']['mode'] = 'urlencoded';
                                        $tmpArr                                                                                   = [];
                                        foreach ($item['parameters'][0]['items']['enum'] as $enum) {
                                            $tmpObj      = new \stdClass();
                                            $tmpObj->key = $item['parameters'][0]['name'];
                                            switch ($item['parameters'][0]['items']['type']) {
                                                case 'integer':
                                                    $tmpObj->value = (integer)$enum;
                                                    break;
                                                case 'boolean':
                                                    $tmpObj->value = (boolean)$enum;
                                                    break;
                                                default:
                                                    $tmpObj->value = $enum;
                                            }
                                            $tmpObj->type = 'text';
                                            if ($enum == $item['parameters'][0]['items']['default']) {
                                                $tmpObj->enabled = TRUE;
                                            } else {
                                                $tmpObj->enabled = FALSE;
                                            }
                                            $tmpArr[] = $tmpObj;
                                        }
                                        $this->array->item[ $tag ]['item'][ $path ]['item'][ $method ]['request']['body']['urlencoded'] = $tmpArr;
                                        unset($tmpArr);
                                    } else {
                                        $tmpArr['key']                                                                        = 'Content-Type';
                                        $tmpArr['value']                                                                      = 'application/x-www-form-urlencoded';
                                        $tmpArr['description']                                                                = '';
                                        $this->array->item[ $tag ]['item'][ $path ]['item'][ $method ]['request']['header'][] = $tmpArr;
                                        unset($tmpArr);
                                        $this->array->item[ $tag ]['item'][ $path ]['item'][ $method ]['request']['body']['mode'] = 'urlencoded';
                                        $tmpArr                                                                                   = [];
                                        $tmpObj                                                                                   = new \stdClass();
                                        $tmpObj->key                                                                              = $item['parameters'][0]['name'];
                                        switch ($item['parameters'][0]['items']['type']) {
                                            case 'integer':
                                                $tmpObj->value = (integer)'';
                                                break;
                                            case 'boolean':
                                                $tmpObj->value = (boolean)'';
                                                break;
                                            default:
                                                $tmpObj->value = '';
                                        }
                                        $tmpObj->type    = 'text';
                                        $tmpObj->enabled = TRUE;
                                        $tmpArr[]        = $tmpObj;

                                        $this->array->item[ $tag ]['item'][ $path ]['item'][ $method ]['request']['body']['urlencoded'] = $tmpArr;
                                    }
                                }
                            } elseif ($item['parameters'][0]['in'] == 'path') {
                                $this->array->item[ $tag ]['item'][ $path ]['item'][ $method ]['request']['header']           = [];
                                $this->array->item[ $tag ]['item'][ $path ]['item'][ $method ]['request']['body']['mode']     = 'formdata';
                                $this->array->item[ $tag ]['item'][ $path ]['item'][ $method ]['request']['body']['formdata'] = [];
                            }
                        } else {
                            $this->array->item[ $tag ]['item'][ $path ]['item'][ $method ]['request']['header']       = [];
                            $this->array->item[ $tag ]['item'][ $path ]['item'][ $method ]['request']['body']['mode'] = 'formdata';
                            $tmpArr                                                                                   = [];
                            $empty                                                                                    = '';
                            foreach ($item['parameters'] as $parameter) {
                                if ($parameter['in'] == 'formData') {
                                    $tmpObj      = new \stdClass();
                                    $tmpObj->key = $parameter['name'];
                                    switch ($parameter['type']) {
                                        case 'integer':
                                            $tmpObj->value = (integer)$empty;
                                            break;
                                        case 'boolean':
                                            $tmpObj->value = (boolean)$empty;
                                            break;
                                        default:
                                            $tmpObj->value = $parameter['description'];
                                    }
                                    $tmpObj->type    = 'text';
                                    $tmpObj->enabled = $parameter['required'];
                                    $tmpArr[]        = $tmpObj;
                                }
                            }
                            $this->array->item[ $tag ]['item'][ $path ]['item'][ $method ]['request']['body']['formdata'] = $tmpArr;
                            unset($tmpArr);
                            unset($empty);
                        }
                    }

                    if (isset($item['responses']['default']['schema'])) {

                        $this->array->item[ $tag ]['item'][ $path ]['item'][ $method ]['event'][0]['listen']         = 'test';
                        $this->array->item[ $tag ]['item'][ $path ]['item'][ $method ]['event'][0]['script']['type'] = 'application/json';

                        $this->array->item[ $tag ]['item'][ $path ]['item'][ $method ]['event'][0]['script']['exec'][0] =
                            $this->convertModel($array, $item['responses']['default']['schema']['$ref']);
                    }
                }
            }

            unset($tmp);
        }

        unset($array);
        foreach ($this->array->item as $tag => $value) {
            foreach ($this->array->item[ $tag ]['item'] as $k => $_) {
                if (isset($this->array->item[ $tag ]['item'][ $k ]['item'])) {
                    $tmp                                             = $this->array->item[ $tag ]['item'][ $k ]['item'];
                    $this->array->item[ $tag ]['item'][ $k ]['item'] = [];
                    foreach ($tmp as $v) {
                        $this->array->item[ $tag ]['item'][] = $v;
                    }
                }
            }
            foreach ($this->array->item[ $tag ]['item'] as $k => $_) {
                if (isset($_['item'])) {
                    unset($this->array->item[ $tag ]['item'][ $k ]);
                }
            }
            $this->array->item[ $tag ]['item'] = array_values($this->array->item[ $tag ]['item']);
        }
        $this->array->item = array_values($this->array->item);

        return json_encode($this->array);
    }

    protected function convertModel($array, $ref, $isJson = TRUE)
    {
        $refs = explode('/', $ref);
        unset($ref);
        $name = $refs[ count($refs) - 1 ];
        if (isset($array['definitions'][ $name ]['type'])) {
            switch ($array['definitions'][ $name ]['type']) {
                case 'array':
                    $tmp = [];
                    foreach ($array['definitions'][ $name ]['properties'] as $key => $value) {
                        if (isset($value['$ref'])) {
                            $tmp[]->$key = $this->convertModel($array, $value['$ref']);
                        } else {
                            if (isset($value['example'])) {
                                $tmpValue = $value['example'];
                            } elseif (isset($value['default'])) {
                                $tmpValue = $value['default'];
                            } else {
                                $tmpValue = '';
                            }
                            switch ($value['type']) {
                                case 'integer':
                                    $tmp[]->$key = (integer)$tmpValue;
                                    break;
                                case 'boolean':
                                    $tmp[]->$key = (integer)$tmpValue;
                                    break;
                                case 'array':
                                    if (isset($value['xml']['name'])) {
                                        $tmpVal[][ $value['xml']['name'] ] = (String)$tmpValue;
                                    } else {
                                        $tmpVal[] = (String)$tmpValue;
                                    }
                                    $tmp[]->$key = $tmpVal;
                                    unset($tmpVal);
                                    break;
                                default:
                                    $tmp[]->$key = (String)$tmpValue;
                            }
                            unset($tmpValue);
                        }
                    }
                    break;
                default:
                    $tmp = new \stdClass();
                    if (isset($array['definitions'][ $name ]['properties'])) {
                        foreach ($array['definitions'][ $name ]['properties'] as $key => $value) {
                            if (isset($value['$ref'])) {
                                $tmp->$key = $this->convertModel($array, $value['$ref'], FALSE);
                            } else {
                                if (isset($value['example'])) {
                                    $tmpValue = $value['example'];
                                } elseif (isset($value['default'])) {
                                    $tmpValue = $value['default'];
                                } else {
                                    $tmpValue = '';
                                }
                                switch ($value['type']) {
                                    case 'integer':
                                        $tmp->$key = (integer)$tmpValue;
                                        break;
                                    case 'boolean':
                                        $tmp->$key = (integer)$tmpValue;
                                        break;
                                    case 'array':
                                        if (isset($value['xml']['name'])) {
                                            $tmpVal[][ $value['xml']['name'] ] = (String)$tmpValue;
                                        } else {
                                            $tmpVal[] = (String)$tmpValue;
                                        }
                                        $tmp->$key = $tmpVal;
                                        unset($tmpVal);
                                        break;
                                    default:
                                        $tmp->$key = (String)$tmpValue;
                                }
                                unset($tmpValue);
                            }
                        }
                    }
            }
        } else {
            $tmp = [];
        }
        if ($isJson) {
            return json_encode($tmp, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            return $tmp;
        }
    }
}