<?php
/**
 * MostSimpleCMS tests
 */
class MostSimpleCMSTest extends \PHPUnit_Framework_TestCase {

    public function testmostsimplecms_extract_templates()
    {
        $simpleCms = new MostSimpleCMS\MostSimpleCMS();
        $simpleCms->extractTemplates('test.html');
        $testArray = array(
            'Test' => array(
                '    <div>',
                '        Test text',
                '    </div>',
            )
        );
        $this->assertEquals($simpleCms->templates, $testArray);
    }
}
