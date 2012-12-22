<?php
/**
 * Phue: Philips Hue PHP Client
 *
 * @author    Michael Squires <sqmk@php.net>
 * @copyright Copyright (c) 2012 Michael K. Squires
 * @license   http://github.com/sqmk/Phue/wiki/License
 * @package   Phue
 */

namespace PhueTest\Transport;

use Phue\Transport\Http;

/**
 * Tests for Phue\Transport\Http
 *
 * @category Phue
 * @package  Phue
 */
class HttpTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Set up
     *
     * @return void
     */
    public function setUp()
    {
        // Mock client
        $this->mockClient = $this->getMock(
            '\Phue\Client',
            ['getTransport'],
            ['127.0.0.1']
        );

        // Mock transport adapter
        $this->mockAdapter = $this->getMock(
            '\Phue\Transport\Adapter\AdapterInterface'
        );

        // Set transport
        $this->transport = new Http($this->mockClient);
    }

    /**
     * Test: Client property
     *
     * @covers Phue\Transport\Http::__construct
     */
    public function testClientProperty()
    {
        // Ensure property is set properly
        $this->assertAttributeEquals(
            $this->mockClient,
            'client',
            $this->transport
        );
    }

    /**
     * Test: Get default adapter
     *
     * @covers Phue\Transport\Http::getAdapter
     */
    public function testGetDefaultAdapter()
    {
        $this->assertInstanceOf(
            '\Phue\Transport\Adapter\AdapterInterface',
            $this->transport->getAdapter()
        );
    }

    /**
     * Test: Custom adapter
     *
     * @covers Phue\Transport\Http::getAdapter
     * @covers Phue\Transport\Http::setAdapter
     */
    public function testCustomAdapter()
    {
        $this->transport->setAdapter($this->mockAdapter);

        $this->assertEquals(
            $this->mockAdapter,
            $this->transport->getAdapter()
        );
    }

    /**
     * Test: Send request with bad status code
     *
     * @covers Phue\Transport\Http::sendRequest
     *
     * @expectedException Phue\Transport\Exception\ConnectionException
     */
    public function testSendRequestBadStatusCode()
    {
        // Stub adapter methods
        $this->stubMockAdapterResponseMethods(null, 500, 'application/json');

        // Set mock adapter
        $this->transport->setAdapter($this->mockAdapter);

        // Send request
        $this->transport->sendRequest('dummy', 'GET');
    }

    /**
     * Test: Send request with bad content type
     *
     * @covers Phue\Transport\Http::sendRequest
     *
     * @expectedException Phue\Transport\Exception\ConnectionException
     */
    public function testSendRequestBadContentType()
    {
        // Stub adapter methods
        $this->stubMockAdapterResponseMethods(null, 200, 'unknown');

        // Set mock adapter
        $this->transport->setAdapter($this->mockAdapter);

        // Send request
        $this->transport->sendRequest('dummy', 'GET');
    }

    /**
     * Test: Send request with error response
     *
     * @covers Phue\Transport\Http::sendRequest
     *
     * @expectedException Phue\Transport\Exception\AuthorizationException
     */
    public function testSendRequestErrorResponse()
    {
        // Mock response
        $mockResponse = [
            'error' => [
                'type'        => 1,
                'description' => 'Some kind of error'
            ]
        ];

        // Stub adapter methods
        $this->stubMockAdapterResponseMethods($mockResponse, 200, 'application/json');

        // Set mock adapter
        $this->transport->setAdapter($this->mockAdapter);

        // Send request
        $this->transport->sendRequest('dummy', 'GET');
    }

    /**
     * Test: Send request with array response
     *
     * @covers Phue\Transport\Http::sendRequest
     */
    public function testSendRequestArray()
    {
        // Mock response
        $mockResponse = [
            'value 1', 'value 2'
        ];

        // Stub adapter methods
        $this->stubMockAdapterResponseMethods($mockResponse, 200, 'application/json');

        // Set mock adapter
        $this->transport->setAdapter($this->mockAdapter);

        // Send request
        $this->assertEquals(
            $mockResponse[0],
            $this->transport->sendRequest('dummy', 'GET')
        );
    }

    /**
     * Test: Send request with success resposne
     *
     * @covers Phue\Transport\Http::sendRequest
     */
    public function testSendRequestSuccess()
    {
        // Mock response
        $mockResponse = [
            'success' => '123'
        ];

        // Stub adapter methods
        $this->stubMockAdapterResponseMethods($mockResponse, 200, 'application/json');

        // Set mock adapter
        $this->transport->setAdapter($this->mockAdapter);

        // Send request
        $this->assertEquals(
            $mockResponse['success'],
            $this->transport->sendRequest('dummy', 'GET')
        );
    }

    /**
     * Test: Build request URL
     *
     * @covers Phue\Transport\Http::buildRequestUrl
     */
    public function testBuildRequestUrl()
    {
        $this->assertEquals(
            $this->transport->buildRequestUrl('dummy', true),
            "http://{$this->mockClient->getHost()}/api/dummy"
        );

        $this->assertEquals(
            $this->transport->buildRequestUrl('dummy'),
            "/api/dummy"
        );
    }

    /**
     * Test: Throw exception by type
     *
     * @dataProvider providerErrorTypes
     *
     * @covers Phue\Transport\Http::getExceptionByType
     */
    public function testThrowExceptionByType($type, $exceptionName)
    {
        $this->assertInstanceOf(
            $exceptionName,
            $this->transport->getExceptionByType($type, null)
        );
    }

    /**
     * Provider: Error types
     *
     * @return array
     */
    public function providerErrorTypes()
    {
        return [
            [1,   'Phue\Transport\Exception\AuthorizationException'],
            [2,   'Phue\Transport\Exception\InvalidBodyException'],
            [3,   'Phue\Transport\Exception\ResourceException'],
            [4,   'Phue\Transport\Exception\MethodException'],
            [5,   'Phue\Transport\Exception\InvalidParameterException'],
            [6,   'Phue\Transport\Exception\ParameterUnavailableException'],
            [7,   'Phue\Transport\Exception\InvalidValueException'],
            [101, 'Phue\Transport\Exception\LinkButtonException'],
            [301, 'Phue\Transport\Exception\GroupTableFullException'],
            [901, 'Phue\Transport\Exception\ThrottleException'],
            [-1,  'Phue\Transport\Exception\BridgeException'],
        ];
    }

    /**
     * Stub adapter response methods
     *
     * @param string $response       Response body
     * @param string $httpStatusCode Http status code
     * @param string $contentType    Content type
     *
     * @return void
     */
    protected function stubMockAdapterResponseMethods($response, $httpStatusCode, $contentType)
    {
        // Stub send method on transport adapter
        $this->mockAdapter->expects($this->once())
                          ->method('send')
                          ->will($this->returnValue(json_encode($response)));

        // Stub getHttpStatusCode on transport adapter
        $this->mockAdapter->expects($this->once())
                          ->method('getHttpStatusCode')
                          ->will($this->returnValue($httpStatusCode));

        // Stub getContentType on transport adapter
        $this->mockAdapter->expects($this->once())
                          ->method('getContentType')
                          ->will($this->returnValue($contentType));
    }
}
