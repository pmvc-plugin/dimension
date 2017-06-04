<?php
namespace PMVC\PlugIn\dimension;

use PMVC\Event;

\PMVC\initPlugin(['controller'=>null]);

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\dimension';

const QUERY = 'dimensionQuery';

/**
 * @parameters string   dimensionUrl 
 * @parameters array    dimensionQuery
 * @parameters callable getDimension
 */
class dimension extends \PMVC\PlugIn
{
    public function init()
    {
        \PMVC\callPlugin(
            'dispatcher',
            'attach',
            [ 
                $this,
                Event\MAP_REQUEST,
            ]
        );
        $this[QUERY] = new \PMVC\Hashmap();
    }

    public function onMapRequest($subject)
    {
        $subject->detach($this);
        $c = \PMVC\plug('controller');
        $pEnv = \PMVC\plug('getenv');
        if ($this[\PMVC\NAME] === $c->getApp()) {
            return false;
        }
        if (empty($this['dimensionUrl'])) {
            $this['dimensionUrl'] = \PMVC\getOption('dimensionUrl'); 
            if (empty($this['dimensionUrl'])) {
                return false;
            }
        }
        $this[QUERY]['SITE']   = $pEnv->get('SITE');
        $this[QUERY]['APP']    = $c->getApp();
        $this[QUERY]['ACTION'] = $c->getAppAction();
        $env = $pEnv->get('ENVIRONMENT');
        if ($env) {
            $this[QUERY]['ENVIRONMENT'] = $env;
        }
        $market = $pEnv->get('MARKET');
        if ($market) {
            $this[QUERY]['MARKET'] = $market;
        }

        // Entry
        $entry = explode(
            '.',
            \PMVC\plug('url')->getRunPhp()
        );
        $this[QUERY]['ENTRY']=$entry[0];

        // Bucket
        $buckets = $pEnv->get('HTTP_X_BUCKET_TESTS');
        if (!empty($buckets)) {
            $this[QUERY]['BUCKET'] = array_diff(
                explode(',',$buckets),
                ['']
            );
        }

        // Last
        if (isset($this['getDimension'])) {
            call_user_func_array(
                $this['getDimension'],
                [
                    $this[QUERY],
                    $c->getRequest() 
                ]
            );
        }
        $this->process();
        return true;
    }

    public function process()
    {
        $configs = $this->getRemoteConfigs($this[QUERY]);
        if (!empty($configs)) {
            $this->unsetCli($configs);
            \PMVC\option('set', $configs);
        } else { // failback
            $dot = \PMVC\plug('dotenv');
            if ($dot->fileExists($this['env'])) {
                $dot->toPMVC($this['env']);
            }
        }
    }

    public function getRemoteConfigs($query)
    {
        $url = \PMVC\plug('url')
            ->getUrl($this['dimensionUrl']);
        $url->query = $query;
        $curl = \PMVC\plug('curl');
        $configs = [];
        $curl->get($url, function($r) use (&$configs, $query, $url) {
            $json = \PMVC\fromJson($r->body, true); 
            if (is_array($json)) {
                $configs = $json;
                \PMVC\dev(function() use ($json, $query, $url){
                    return [
                        'query'=>\PMVC\get($query),
                        'url'=>(string)$url,
                        'configs'=>$json
                    ];
                },'dimension');
            }
        })->set([
            CURLOPT_CONNECTTIMEOUT=>1
        ]);
        $curl->process();
        return $configs;
    }

    public function unsetCli(&$json)
    {
        $cliWhiteList = [
            _ROUTER,
            _VIEW_ENGINE,
            _RUN_APPS
        ];
        foreach ($cliWhiteList as $key) {
            if (\PMVC\getOption($key)) {
                unset($json[$key]);
            }
        }
    }
}
