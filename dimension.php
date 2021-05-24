<?php

namespace PMVC\PlugIn\dimension;

use PMVC\Event;

\PMVC\initPlugin(['controller' => null]);

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__ . '\dimension';

const QUERY = 'dimensionQuery';
const DIMENSION_URL = 'dimensionUrl';

/**
 * @parameters string   dimensionUrl
 * @parameters array    dimensionQuery
 * @parameters callable getDimension
 */
class dimension extends \PMVC\PlugIn
{
    public function init()
    {
        \PMVC\callPlugin('dispatcher', 'attach', [
            $this,
            \PMVC\callPlugin('dispatcher', 'getOptionKey', [_REAL_APP]),
        ]);
        $this[QUERY] = new \PMVC\Hashmap();
    }

    public function onSetConfig__real_app_($subject)
    {
        $subject->detach($this);
        $c = \PMVC\plug('controller');
        if ($this[\PMVC\NAME] === $c->getApp()) {
            return false;
        }
        if (empty($this[DIMENSION_URL])) {
            $this[DIMENSION_URL] = \PMVC\getOption(DIMENSION_URL);
            if (empty($this[DIMENSION_URL])) {
                return $this->_processFailback();
            }
        }
        $pEnv = \PMVC\plug('get');
        $this[QUERY]['SITE'] = $pEnv->get('SITE');
        $this[QUERY]['APP'] = $c[_REAL_APP];
        $this[QUERY]['ACTION'] = $c[_RUN_ACTION];
        $env = $pEnv->get('ENVIRONMENT');
        if ($env) {
            $this[QUERY]['ENVIRONMENT'] = $env;
        }
        $market = $pEnv->get('MARKET');
        if ($market) {
            $this[QUERY]['MARKET'] = $market;
        }
        $utm = $pEnv->get('UTM');
        if ($utm) {
            $this[QUERY]['UTM'] = $utm;
        }
        $colo = $pEnv->get('COLO');
        if ($colo) {
            $this[QUERY]['COLO'] = $colo;
        }

        // Entry
        $entry = explode('.', \PMVC\plug('url')->getRunPhp());
        $this[QUERY]['ENTRY'] = $entry[0];

        // Bucket
        $buckets = $pEnv->get('HTTP_X_BUCKET_TESTS');
        if (!empty($buckets)) {
            $this[QUERY]['BUCKET'] = array_diff(explode(',', $buckets), ['']);
        }

        // Last
        if (isset($this['getDimension'])) {
            call_user_func_array($this['getDimension'], [
                $this[QUERY],
                $c->getRequest(),
            ]);
        }
        return $this->process();
    }

    public function getQuery($key)
    {
        return \PMVC\get($this[QUERY], $key);
    }

    public function process()
    {
        $configs = $this->getRemoteConfigs($this[QUERY]);
        if (!empty($configs)) {
            $this->unsetCli($configs);
            \PMVC\option('set', $configs);
            if (isset($configs['resetBuckets'])) {
                \PMVC\plug('getenv', [
                    'HTTP_X_BUCKET_TESTS' => $configs['resetBuckets'],
                ]);
            }
            return true;
        } else {
            // failback
            return $this->_processFailback();
        }
    }

    private function _processFailback()
    {
        $dot = \PMVC\plug('dotenv');
        if ($dot->fileExists($this['env'])) {
            $dot->toPMVC($this['env']);
        }
        return false;
    }

    public function getRemoteConfigs($query)
    {
        $url = \PMVC\plug('url')->getUrl($this[DIMENSION_URL]);
        $url->query = $query;
        $curl = \PMVC\plug('curl');
        $configs = [];
        $curl
            ->get($url, function ($r) use (&$configs, $query, $url) {
                $json = \PMVC\fromJson($r->body, true);
                if (is_array($json)) {
                    $configs = $json;
                    \PMVC\dev(function () use ($json, $query, $url) {
                        $json['PW'] = '*secret*'; 
                        return [
                            'query' => \PMVC\get($query),
                            'url' => (string) $url,
                            'configs' => $json,
                        ];
                    }, 'dimension');
                } else {
                    \PMVC\triggerJson(
                        'Get remote dimension failed. Error code',
                        [
                            'CURL_ERROR' => [$r->errno, $r->error],
                            'url' => $r->url,
                        ],
                        E_USER_WARNING
                    );
                }
            })
            ->set([
                CURLOPT_FORBID_REUSE => false,
                CURLOPT_FRESH_CONNECT => false,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 5,
            ]);
        $curl->process();
        return $configs;
    }

    public function unsetCli(&$json)
    {
        $cliWhiteList = [_ROUTER, _VIEW_ENGINE, _RUN_APPS];
        foreach ($cliWhiteList as $key) {
            if (\PMVC\getOption($key)) {
                unset($json[$key]);
            }
        }
    }
}
