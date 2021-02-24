<?php

use PHPUnit\Framework\TestCase;

function get_mock_codeigniter_instance($config_map=array()) {
    $CI = (object) array(
        'config' => (object) array(
            'item' => function($key) {
                return $config_map[$key];
            }
        ),
        'books' => (object) array(
            'save' => function($data) {
                return $data;
            }
        ),
        'users' => (object) array(
            'get_by_email' => function($data) {
                return array('user_id' => rand());
            },
            'db' => (object) array(
                'insert' => function() {
                    return true;
                }
            ),
            'save_books' => function() {
                return true;
            }
        ),
        'CSV_ROWS' => array('fullname','email','huid','in_index','relationship'),
        'RELATIONSHIPS' => array('author','editor','commentator','reviewer','reader')

    );
    return $CI;
}

class HarvardsettingsTest extends TestCase {

    public function create_mock_plugin_instance($codeigniter_instance, $data=array(), $base_url='http://localhost/') {

        $plugin = $this->getMockBuilder('Harvardsettings')
            ->disableOriginalConstructor()
            ->setMethods(array('get_codeigniter_instance', 'get_base_url'))
            ->getMock();
        $plugin->method('get_codeigniter_instance')->will($this->returnValue($codeigniter_instance));
        $plugin->method('get_base_url')->willReturn($base_url);

        $reflectedClass = new ReflectionClass('Harvardsettings');
        $constructor = $reflectedClass->getConstructor();
        $constructor->invoke($plugin, $data);

        return $plugin;
    }

    public function test_construct_plugin() {
        $data = array(
            'book' => (object) array(
                'title' => "The most awesomest book title ever!",
                'description' => 'A long time ago in a galaxy far, far away....'
            )
        );

        $mock_codeigniter_instance = get_mock_codeigniter_instance();
        $plugin = $this->create_mock_plugin_instance($mock_codeigniter_instance, $data);
        $this->assertEquals($mock_codeigniter_instance, $plugin->CI);
        $this->assertEquals('Harvard Settings', $plugin->name);
        $this->assertEquals('harvardsettings', $plugin->plugin_name);
        $this->assertEquals($data['book'], $plugin->book);
    }

    public function test_check_plugin() {
        $tests = array(
            array(
                'data' => array(),
                'expected' => array(false ,"Please select a book to manage using the pulldown menu above")
            ),
            array(
                'data' => array('book' => (object) array()),
                'expected' => array(false , "Plugin harvardsettings has not bee installed yet")
            ),
            array(
                'data' => array('book' => (object) array('subdomain_is_on' => true)),
                'expected' => array(true , "Plugin is ready")
            )
        );
        foreach($tests as $test) {
            $mock_codeigniter_instance = get_mock_codeigniter_instance();
            $plugin = $this->create_mock_plugin_instance($mock_codeigniter_instance, $test['data']);
            $this->assertEquals($test['expected'], $plugin->check_plugin());
        }
    }

    public function test_get_subdomain_is_on() {
        $tests = array(
            array(1, true),
            array(0, false),
            array(null, false),
            array('', false)
        );
        foreach($tests as $test) {
            list($subdomain_is_on, $expected_value) = $test;
            $mock_codeigniter_instance = get_mock_codeigniter_instance();
            $data = array('book' =>
                (object) array('subdomain_is_on' => $subdomain_is_on)
            );
            $plugin = $this->create_mock_plugin_instance($mock_codeigniter_instance, $data);
            $this->assertEquals($subdomain_is_on, $plugin->get_subdomain_is_on());
        }
    }

    public function get_subdomain_url() {
        $domain = "scalar.org";
        putenv("SCALAR_DOMAIN=$domain");

        $config_map = array('is_https' => true);
        $mock_codeigniter_instance = get_mock_codeigniter_instance($config_map);
        $data = array('book' =>
            (object) array(
                'slug' => 'awesomebook',
                'subdomain_is_on' => true
            )
        );
        $plugin = $this->create_mock_plugin_instance($mock_codeigniter_instance, $data);

        $expected_subdomain_url = "https://".$data['book']->slug.".".$domain.'/';
        $this->assertEquals($expected_url, $plugin->get_subdomain_url());
    }

    public function test_build_body_conditions() {
        $test_array = array(
            array(
                'fullname' => '',
                'email' => '',
                'huid' => 11
            ),
            array(
                'fullname' => 'Zahid',
                'email' => 'fake1@fake.org',
                'huid' => 12
            ),
            array(
                'fullname' => '',
                'email' => 'fake2@fake.org',
                'huid'=> ''
            )
        );
        $data = array(
            'book' => (object) array(
                'title' => "The most awesomest book title ever!",
                'description' => 'A long time ago in a galaxy far, far away....',
                'book_id' => 1
            )
        );
        $mock_codeigniter_instance = get_mock_codeigniter_instance();
        $plugin = $this->create_mock_plugin_instance($mock_codeigniter_instance, $data);
        $huid_conditions = $plugin->build_body_conditions($test_array, 'univid');
        $this->assertEquals(1, count($huid_conditions));
        $this->assertEquals(11, $huid_conditions[0]);
        $email_conditions = $plugin->build_body_conditions($test_array, 'loginName');
        $this->assertEquals(1, count($email_conditions));
        $this->assertEquals('fake2@fake.org', $email_conditions[0]);
    }

}
