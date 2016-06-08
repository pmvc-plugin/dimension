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
            'attach',
            [ 
                $this,
                Event\SET_CONFIG.'_'._RUN_FORM,
            ]
        );
    }

    public function onSetConfig__run_form_($subject)
    {
        $subject->detach($this);
        if (!\PMVC\getOption('DIMENTION_ON')) {
            return false;
        }
        if (isset($this['getDimension'])) {
            call_user_func_array(
                $this['getDimension'],
                [
                    $this['this'],
                    \PMVC\getOption(_RUN_FORM)
                ]
            );
            $this->getDimension();
        }
    }

    public function getDimension()
    {
        $url = \PMVC\plug('url')->getUrl(
            \PMVC\getOption('DIMENTION_URL')
        );
        $url->query = $this['DIMENTION_QUERY'];
        $curl = \PMVC\plug('curl');
        $curl->get($url, function($r){
            $json = \PMVC\fromJson($r->body); 
            \PMVC\option('set', $json);
        });
        $curl->process();
    }
}
