<?php
namespace PMVC\PlugIn\dimension;

use PMVC\Event;

\PMVC\initPlugin(['controller'=>null]);

// \PMVC\l(__DIR__.'/xxx.php');

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
        $env = \PMVC\plug('getenv');
        if ($this[\PMVC\NAME] === $c->getApp()) {
            return false;
        }
        if (empty($this['dimensionUrl'])) {
            $this['dimensionUrl'] = \PMVC\getOption('dimensionUrl'); 
            if (empty($this['dimensionUrl'])) {
                return false;
            }
        }
        $this[QUERY]['SITE']   = $env->get('SITE');
        $this[QUERY]['APP']    = $c->getApp();
        $this[QUERY]['ACTION'] = $c->getAppAction();
        $env = \PMVC\plug('getenv')->get('ENVIRONMENT');
        if ($env) {
            $this[QUERY]['ENVIRONMENT'] = $env;
        }
        $entry = explode(
            '.',
            \PMVC\plug('url')->getRunPhp()
        );
        $this[QUERY]['ENTRY']=$entry[0];
        if (isset($this['getDimension'])) {
            call_user_func_array(
                $this['getDimension'],
                [
                    $this[QUERY],
                    $c->getRequest() 
                ]
            );
        }
        $this->getDimension();
        return true;
    }

    public function getDimension()
    {
        $url = \PMVC\plug('url')
            ->getUrl($this['dimensionUrl']);
        $url->query = $this[QUERY];
        $curl = \PMVC\plug('curl');
        $curl->get($url, function($r){
            $json = \PMVC\fromJson($r->body, true); 
            if (is_array($json)) {
                $this->unsetCli($json);
                \PMVC\dev(function() use ($json){
                    return [
                        'query'=>\PMVC\get($this[QUERY]),
                        'configs'=>$json
                    ];
                },'dimension');
                \PMVC\option('set', $json);
            } else {
                $dot = \PMVC\plug('dotenv');
                if ($dot->fileExists($this['env'])) {
                    $dot->toPMVC($this['env']);
                }
            }
        })->set([
            CURLOPT_CONNECTTIMEOUT=>1
        ]);
        $curl->process();
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
