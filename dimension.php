<?php
namespace PMVC\PlugIn\dimension;

use PMVC\Event;

\PMVC\initPlugin(['controller'=>null]);

// \PMVC\l(__DIR__.'/xxx.php');

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\dimension';

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
        $this['dimensionQuery'] = [];
    }

    public function onMapRequest($subject)
    {
        $subject->detach($this);
        $c = \PMVC\plug('controller');
        if ($this[\PMVC\NAME] === $c->getApp()) {
            return false;
        }
        if (empty($this['dimensionUrl'])) {
            $this['dimensionUrl'] = \PMVC\getOption('dimensionUrl'); 
            if (empty($this['dimensionUrl'])) {
                return false;
            }
        }
        $this['dimensionQuery']['SITE']   = basename(\PMVC\getAppsParent());
        $this['dimensionQuery']['APP']    = $c->getApp();
        $this['dimensionQuery']['ACTION'] = $c->getAppAction();
        $entry = explode(
            '.',
            \PMVC\plug('url')->getRunPhp()
        );
        $this['dimensionQuery']['ENTRY']=$entry[0];
        if (isset($this['getDimension'])) {
            call_user_func_array(
                $this['getDimension'],
                [
                    &$this['dimensionQuery'],
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
        $url->query = $this['dimensionQuery'];
        $curl = \PMVC\plug('curl');
        $curl->get($url, function($r){
            $json = \PMVC\fromJson($r->body, true); 
            if (is_array($json)) {
                $this->unsetCli($json);
                \PMVC\dev(function() use ($json){return $json;},'dimension');
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
