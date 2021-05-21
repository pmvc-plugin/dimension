<?php
namespace PMVC\PlugIn\dimension;

use PMVC\TestCase;

class DimensionTest extends TestCase
{
    private $_plug = 'dimension';
    function pmvc_setup()
    {
        \PMVC\plug('get', ['order'=>['getenv']]); 
        \PMVC\unplug($this->_plug);
    }

    function testPlugin()
    {
        ob_start();
        print_r(\PMVC\plug($this->_plug));
        $output = ob_get_contents();
        ob_end_clean();
        $this->haveString($this->_plug,$output);
    }

    function testAvoidSameApp()
    {
        $c = \PMVC\plug('controller');
        $c->setApp($this->_plug); 
        $oPlug = $this->
            getMockBuilder('\PMVC\PlugIn\dimension\dimension')->
            setMethods(['process'])->
            getMock();
        $oPlug->method('process')
             ->willReturn(true);
        \PMVC\replug($this->_plug, [], $oPlug); 
        $p = \PMVC\plug($this->_plug);
        $p->init();
        $result = $p->onSetConfig__real_app_(new FakeSubject());
        $this->assertFalse($result);
        $c->setApp('fake'); 
        $p['dimensionUrl'] = 'http://xxx';
        $result = $p->onSetConfig__real_app_(new FakeSubject());
        $this->assertTrue($result);
    }

    function testEmptyDimensionUrl()
    {
        $c = \PMVC\plug('controller');
        $c->setApp('fake'); 
        $p = \PMVC\plug($this->_plug);
        $result = $p->onSetConfig__real_app_(new FakeSubject());
        $this->assertFalse($result);
    }

}

class FakeSubject {
   function detach(){} 
}
