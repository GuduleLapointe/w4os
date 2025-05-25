<?php 

class WP_Meta_VerifyTest extends WP_UnitTestCase
{
    public function set_up()
    {
        parent::set_up();
        $this->class_instance = new WP_Meta_Verify();
    }

    public function test_google_site_verification()
    {
        $meta_tag = $this->class_instance->google_site_verification('B6wFaCRbzWE42SyxSvKUOyyPxZfJCb5g');
        $expected = '<meta name="google-site-verification" content="B6wFaCRbzWE42SyxSvKUOyyPxZfJCb5g">';

        $this->assertEquals($expected, $meta_tag);
    }

    public function test_bing_site_verification()
    {
        $meta_tag = $this->class_instance->bing_site_verification('B6wFaCRbzWE42SyxSvKUOyyPxZfJCb5g');
        $expected = '<meta name="msvalidate.01" content="B6wFaCRbzWE42SyxSvKUOyyPxZfJCb5g">';

        $this->assertEquals($expected, $meta_tag);
    }
}
