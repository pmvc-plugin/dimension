<?php
namespace PMVC\PlugIn\dimension;
use PHPUnit_Framework_TestCase;

\PMVC\Load::plug();
\PMVC\addPlugInFolders(['../']);

class DimensionTest extends PHPUnit_Framework_TestCase
{
    private $_plug = 'dimension';
    function setup()
    {
        \PMVC\unplug($this->_plug);
    }

    function testPlugin()
    {
        ob_start();
        print_r(\PMVC\plug($this->_plug));
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertContains($this->_plug,$output);
    }

    function testAvoidSameApp()
    {
        $c = \PMVC\plug('controller');
        $c->setApp($this->_plug); 
        $p = \PMVC\plug($this->_plug);
        $result = $p->onMapRequest(new FakeSubject());
        $this->assertFalse($result);
        $c->setApp('fake'); 
        $p['dimensionUrl'] = 'http://xxx';
        $result = $p->onMapRequest(new FakeSubject());
        $this->assertTrue($result);
    }

    function testEmptyDimensionUrl()
    {
        $c = \PMVC\plug('controller');
        $c->setApp('fake'); 
        $p = \PMVC\plug($this->_plug);
        $result = $p->onMapRequest(new FakeSubject());
        $this->assertFalse($result);
    }

}

class FakeSubject {
   function detach(){} 
}
