<?php

class DynamicRoutesUnitTest extends \PHPUnit\Framework\TestCase
{

    /**
     * Default setup
     *
     * {@inheritdoc}
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    public function setUp(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    /**
     * Testing hasParam method
     */
    public function testValidatingParameter(): void
    {
        // setup
        $router = new \Mezon\Router\Router();
        $router->addRoute('/catalog/[i:foo]/', function () {
            // do nothing
        });

        $router->callRoute('/catalog/1/');

        // test body and assertions
        $this->assertTrue($router->hasParam('foo'));
        $this->assertFalse($router->hasParam('unexisting'));
    }

    /**
     * Testing getParam for existing param
     */
    public function testGettingExistingParameter(): void
    {
        // setup
        $router = new \Mezon\Router\Router();
        $router->addRoute('/catalog/[i:foo]/', function () {
            // do nothing
        });

        $router->callRoute('/catalog/1/');

        // test body
        $foo = $router->getParam('foo');

        // assertions
        $this->assertEquals(1, $foo);
    }

    /**
     * Testing getParam for unexisting param
     */
    public function testGettingUnexistingParameter(): void
    {
        // setup
        $router = new \Mezon\Router\Router();
        $router->addRoute('/catalog/[i:foo]/', function () {
            // do nothing
        });

        $router->callRoute('/catalog/1/');

        $this->expectException(Exception::class);

        // test body and assertions
        $router->getParam('unexisting');
    }

    /**
     * Testing exception throwing for unexisting request method
     */
    public function testExceptionForUnexistingRequestMethod(): void
    {
        // setup
        $_SERVER['REQUEST_METHOD'] = 'OPTION';
        $router = new \Mezon\Router\Router();
        $router->addRoute('/catalog/[i:foo]/', function () {
            // do nothing
        });

        // assertions
        $this->expectException(Exception::class);

        // test body
        $router->callRoute('/catalog/1/');
    }

    /**
     * Testing saving of the route parameters
     */
    public function testSavingParameters(): void
    {
        $router = new \Mezon\Router\Router();
        $router->addRoute('/catalog/[i:foo]/', function ($route, $parameters) {
            return $parameters['foo'];
        });

        $router->callRoute('/catalog/-1/');

        $this->assertEquals($router->getParam('foo'), '-1', 'Float data violation');
    }

    /**
     * Testing command special chars.
     */
    public function testCommandSpecialChars(): void
    {
        $router = new \Mezon\Router\Router();

        $router->addRoute('/[a:url]/', function () {
            return 'GET';
        }, 'GET');

        $result = $router->callRoute('/.-@/');
        $this->assertEquals($result, 'GET', 'Invalid selected route');
    }

    /**
     * Testing strings.
     */
    public function testStringSpecialChars(): void
    {
        $router = new \Mezon\Router\Router();

        $router->addRoute('/[s:url]/', function () {
            return 'GET';
        }, 'GET');

        $result = $router->callRoute('/, ;:/');
        $this->assertEquals($result, 'GET', 'Invalid selected route');
    }

    /**
     * Testing invalid id list data types behaviour.
     */
    public function testInValidIdListParams(): void
    {
        $exception = '';
        $router = new \Mezon\Router\Router();
        $router->addRoute('/catalog/[il:cat_id]/', [
            $this,
            'helloWorldOutput'
        ]);

        try {
            $router->callRoute('/catalog/12345./');
        } catch (Exception $e) {
            $exception = $e->getMessage();
        }

        $msg = "The processor was not found for the route /catalog/12345./";

        $this->assertNotFalse(strpos($exception, $msg), 'Invalid error response');
    }

    /**
     * Method for checking id list.
     */
    public function ilTest($route, $params): string
    {
        return $params['ids'];
    }

    /**
     * Testing valid id list data types behaviour.
     */
    public function testValidIdListParams(): void
    {
        $router = new \Mezon\Router\Router();
        $router->addRoute('/catalog/[il:ids]/', [
            $this,
            'ilTest'
        ]);

        $result = $router->callRoute('/catalog/123,456,789/');

        $this->assertEquals($result, '123,456,789', 'Invalid router response');
    }

    /**
     * Testing valid id list data types behaviour.
     */
    public function testStringParamSecurity(): void
    {
        $router = new \Mezon\Router\Router();
        $router->addRoute('/catalog/[s:foo]/', function ($route, $parameters) {
            return $parameters['foo'];
        });

        $result = $router->callRoute('/catalog/123&456/');

        $this->assertEquals($result, '123&amp;456', 'Security data violation');
    }

    /**
     * Testing float value.
     */
    public function testFloatI(): void
    {
        $router = new \Mezon\Router\Router();
        $router->addRoute('/catalog/[i:foo]/', function ($route, $parameters) {
            return $parameters['foo'];
        });

        $result = $router->callRoute('/catalog/1.1/');

        $this->assertEquals($result, '1.1', 'Float data violation');
    }

    /**
     * Testing negative float value.
     */
    public function testNegativeFloatI(): void
    {
        $router = new \Mezon\Router\Router();
        $router->addRoute('/catalog/[i:foo]/', function ($route, $parameters) {
            return $parameters['foo'];
        });

        $result = $router->callRoute('/catalog/-1.1/');

        $this->assertEquals($result, '-1.1', 'Float data violation');
    }

    /**
     * Testing positive float value.
     */
    public function testPositiveFloatI(): void
    {
        $router = new \Mezon\Router\Router();
        $router->addRoute('/catalog/[i:foo]/', function ($route, $parameters) {
            return $parameters['foo'];
        });

        $result = $router->callRoute('/catalog/+1.1/');

        $this->assertEquals($result, '+1.1', 'Float data violation');
    }

    /**
     * Testing negative integer value
     */
    public function testNegativeIntegerI(): void
    {
        $router = new \Mezon\Router\Router();
        $router->addRoute('/catalog/[i:foo]/', function ($route, $parameters) {
            return $parameters['foo'];
        });

        $result = $router->callRoute('/catalog/-1/');

        $this->assertEquals('-1', $result, 'Float data violation');
    }

    /**
     * Testing positive integer value
     */
    public function testPositiveIntegerI(): void
    {
        $router = new \Mezon\Router\Router();
        $router->addRoute('/catalog/[i:foo]/', function ($route, $parameters) {
            return $parameters['foo'];
        });

        $result = $router->callRoute('/catalog/1/');

        $this->assertEquals('1', $result, 'Float data violation');
    }
    
    /**
     * Testing dynamic routes for DELETE requests.
     */
    public function testDeleteRequestForUnExistingDynamicRoute(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        
        $exception = '';
        $router = new \Mezon\Router\Router();
        $router->addRoute('/catalog/[i:cat_id]', [
            $this,
            'helloWorldOutput'
        ]);
        
        try {
            $router->callRoute('/catalog/1024/');
        } catch (Exception $e) {
            $exception = $e->getMessage();
        }
        
        $msg = "The processor was not found for the route /catalog/1024/";
        
        $this->assertNotFalse(strpos($exception, $msg), 'Invalid error response');
    }
    
    /**
     * Testing dynamic routes for DELETE requests.
     */
    public function testDeleteRequestForExistingDynamicRoute(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        
        $router = new \Mezon\Router\Router();
        $router->addRoute('/catalog/[i:cat_id]', function ($route) {
            return $route;
        }, 'DELETE');
            
            $result = $router->callRoute('/catalog/1024/');
            
            $this->assertEquals($result, '/catalog/1024/', 'Invalid extracted route');
    }
    
    /**
     * Testing dynamic routes for PUT requests.
     */
    public function testPutRequestForUnExistingDynamicRoute(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        
        $exception = '';
        $router = new \Mezon\Router\Router();
        $router->addRoute('/catalog/[i:cat_id]', [
            $this,
            'helloWorldOutput'
        ]);
        
        try {
            $router->callRoute('/catalog/1024/');
        } catch (Exception $e) {
            $exception = $e->getMessage();
        }
        
        $msg = "The processor was not found for the route /catalog/1024/";
        
        $this->assertNotFalse(strpos($exception, $msg), 'Invalid error response');
    }
    
    /**
     * Testing dynamic routes for PUT requests.
     */
    public function testPutRequestForExistingDynamicRoute(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        
        $router = new \Mezon\Router\Router();
        $router->addRoute('/catalog/[i:cat_id]', function ($route) {
            return $route;
        }, 'PUT');
            
            $result = $router->callRoute('/catalog/1024/');
            
            $this->assertEquals($result, '/catalog/1024/', 'Invalid extracted route');
    }
}
