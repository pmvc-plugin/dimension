<?php
namespace PMVC\PlugIn\dimension;

use PMVC\Event;

\PMVC\initPlugin(['controller'=>null]);

// \PMVC\l(__DIR__.'/xxx.php');

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\dimension';

class dimension extends \PMVC\PlugIn
{
    public function init()
    {
        \PMVC\callPlugin(
            'dispatcher',
            'attachAfter',
            [ 
                $this,
                Event\MAP_REQUEST,
            ]
        );
        $this['DIMENSION_QUERY'] = [];
    }

    public function onMapRequest_post($subject)
    {
        $subject->detach($this);
        if ($this[_PLUGIN] === \PMVC\plug('controller')->getApp()) {
            return false;
        }
        if (!\PMVC\getOption('DIMENSION_URL')) {
            return false;
        }
        $c = \PMVC\plug('controller');
        $this['DIMENSION_QUERY']['SITE']   = basename(\PMVC\getAppsParent());
        $this['DIMENSION_QUERY']['APP']    = $c->getApp();
        $this['DIMENSION_QUERY']['ACTION'] = $c->getAppAction();
        $entry = explode(
            '.',
            \PMVC\plug('url')->getRunPhp()
        );
        $this['DIMENSION_QUERY']['ENTRY']=$entry[0];
        if (isset($this['getDimension'])) {
            call_user_func_array(
                $this['getDimension'],
                [
                    &$this['DIMENSION_QUERY'],
                    $c->getRequest() 
                ]
            );
        }
        $this->getDimension();
    }

    public function getDimension()
    {
        $url = \PMVC\plug('url')->getUrl(
            \PMVC\getOption('DIMENSION_URL')
        );
        $url->query = $this['DIMENSION_QUERY'];
        $curl = \PMVC\plug('curl');
        $curl->get($url, function($r){
            $json = \PMVC\fromJson($r->body, true); 
            \PMVC\option('set', $json);
        })->set([
            CURLOPT_CONNECTTIMEOUT_MS=>100,
            CURLOPT_TIMEOUT=>1
        ]);
        $curl->process();
    }
}
