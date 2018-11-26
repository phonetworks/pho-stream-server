<?php

namespace Pho\Stream;

use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Pho\Stream\Exception\AuthorizationFailedException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class AuthorizationTest extends TestCase
{
    public function provide_data_for_queryParams()
    {
        return [
            [ [] ],
            [ [ 'api_key' => '' ] ],
        ];
    }

    /**
     * @covers \Pho\Stream\Authorization::authorize
     * @dataProvider provide_data_for_queryParams
     */
    public function test_authorize_throws_AuthorizationFailedException_when_api_key_is_not_provided_in_query_parameter($queryParams)
    {
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->getMock();
        $authorization = $this->getMockBuilder(Authorization::class)
            ->setConstructorArgs([$request])
            ->setMethods(['getKey', 'getSecret'])
            ->getMock();

        $request->method('getQueryParams')
            ->willReturn($queryParams);

        $this->expectException(AuthorizationFailedException::class);
        $authorization->authorize('sample_feed_slug', '123', 'test_resource', 'some_action');
    }

    /**
     * @covers \Pho\Stream\Authorization::authorize
     */
    public function test_authorize_throws_AuthorizationFailedException_when_authorization_is_not_provided_in_headers()
    {
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->getMock();
        $authorization = $this->getMockBuilder(Authorization::class)
            ->setConstructorArgs([$request])
            ->setMethods(['getKey', 'getSecret'])
            ->getMock();

        $request->method('getQueryParams')
            ->willReturn([ 'api_key' => 'test-key' ]);
        $request->expects($this->any())->method('getHeaderLine')
            ->willReturnMap([
                [ 'Authorization', '' ],
                [ 'Stream-Auth-Type', 'test-stream-auth-type' ],
            ]);

        $this->expectException(AuthorizationFailedException::class);
        $authorization->authorize('sample_feed_slug', '123', 'test_resource', 'some_action');
    }

    /**
     * @covers \Pho\Stream\Authorization::authorize
     */
    public function test_authorize_throws_AuthorizationFailedException_when_stream_auth_type_is_not_provided_in_headers()
    {
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->getMock();
        $authorization = $this->getMockBuilder(Authorization::class)
            ->setConstructorArgs([$request])
            ->setMethods(['getKey', 'getSecret'])
            ->getMock();

        $request->method('getQueryParams')
            ->willReturn([ 'api_key' => 'test-key' ]);
        $request->expects($this->any())->method('getHeaderLine')
            ->willReturnMap([
                [ 'Authorization', 'test-authorization' ],
                [ 'Stream-Auth-Type', '' ],
            ]);

        $this->expectException(AuthorizationFailedException::class);
        $authorization->authorize('sample_feed_slug', '123', 'test_resource', 'some_action');
    }

    public function provide_data_for_invalidStreamAuthType()
    {
        return [
            [ 'basic' ],
            [ 'digest' ],
        ];
    }

    /**
     * @covers \Pho\Stream\Authorization::authorize
     * @dataProvider provide_data_for_invalidStreamAuthType
     */
    public function test_authorize_throws_AuthorizationFailedException_when_invalid_stream_auth_type_is_provided_in_headers($invalidStreamAuthType)
    {
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->getMock();
        $authorization = $this->getMockBuilder(Authorization::class)
            ->setConstructorArgs([$request])
            ->setMethods(['getKey', 'getSecret'])
            ->getMock();

        $request->method('getQueryParams')
            ->willReturn([ 'api_key' => 'test-key' ]);
        $request->expects($this->any())->method('getHeaderLine')
            ->willReturnMap([
                [ 'Authorization', 'test-authorization' ],
                [ 'Stream-Auth-Type', $invalidStreamAuthType ],
            ]);

        $this->expectException(AuthorizationFailedException::class);
        $authorization->authorize('sample_feed_slug', '123', 'test_resource', 'some_action');
    }

    public function provide_data_for_apiKeyQuery_and_apiKey()
    {
        return [
            [ 'foo', 'bar' ],
            [ 'bar', 'foo' ],
        ];
    }

    /**
     * @covers \Pho\Stream\Authorization::authorize
     * @dataProvider provide_data_for_apiKeyQuery_and_apiKey
     */
    public function test_authorize_throws_AuthorizationFailedException_when_api_key_does_not_match($apiKeyQuery, $apiKey)
    {
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->getMock();
        $authorization = $this->getMockBuilder(Authorization::class)
            ->setConstructorArgs([$request])
            ->setMethods(['getKey', 'getSecret'])
            ->getMock();

        $request->method('getQueryParams')
            ->willReturn([ 'api_key' => $apiKeyQuery ]);
        $request->expects($this->any())->method('getHeaderLine')
            ->willReturnMap([
                [ 'Authorization', 'test-authorization' ],
                [ 'Stream-Auth-Type', 'jwt' ],
            ]);
        $authorization->method('getKey')
            ->willReturn($apiKey);

        $this->expectException(AuthorizationFailedException::class);
        $authorization->authorize('sample_feed_slug', '123', 'test_resource', 'some_action');
    }

    public function provide_data_for_exceptionType()
    {
        return [
            [ SignatureInvalidException::class ],
            [ BeforeValidException::class ],
            [ ExpiredException::class ],
            [ \UnexpectedValueException::class ],
        ];
    }

    /**
     * @covers \Pho\Stream\Authorization::authorize
     * @dataProvider provide_data_for_exceptionType
     */
    public function test_authorize_throws_AuthorizationFailedException_when_stream_auth_type_is_jwt_and_decodeToken_fails_with_exception($exceptionType)
    {
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->getMock();
        $authorization = $this->getMockBuilder(Authorization::class)
            ->setConstructorArgs([$request])
            ->setMethods(['getKey', 'getSecret', 'decodeToken' ])
            ->getMock();

        $request->method('getQueryParams')
            ->willReturn([ 'api_key' => 'test-key' ]);
        $request->expects($this->any())->method('getHeaderLine')
            ->willReturnMap([
                [ 'Authorization', 'test-authorization' ],
                [ 'Stream-Auth-Type', 'jwt' ],
            ]);
        $authorization->method('getKey')
            ->willReturn('test-key');
        $authorization->method('decodeToken')
            ->willThrowException($this->createMock($exceptionType));

        $this->expectException(AuthorizationFailedException::class);
        $authorization->authorize('sample_feed_slug', '123', 'test_resource', 'some_action');
    }

    public function provide_data_for_feedSlug_userId_resource_action_and_decoded()
    {
        return [
            [
                'test-slug', 'test-user-id', 'test-resource', 'test-action',
                [ 'feed_id' => 'invalid', 'resource' => 'test-resource', 'action' => 'test-action' ],
            ],
            [
                'test-slug', 'test-user-id', 'test-resource', 'test-action',
                [ 'feed_id' => 'test-slugtest-user-id', 'resource' => 'invalid', 'action' => 'test-action' ],
            ],
            [
                'test-slug', 'test-user-id', 'test-resource', 'test-action',
                [ 'feed_id' => 'test-slugtest-user-id', 'resource' => 'test-resource', 'action' => 'invalid' ],
            ],
        ];
    }

    /**
     * @covers \Pho\Stream\Authorization::authorize
     * @dataProvider provide_data_for_feedSlug_userId_resource_action_and_decoded
     */
    public function test_authorize_throws_AuthorizationFailedException_when_stream_auth_type_is_jwt_and_decoded_values_are_invalid($feedSlug, $userId, $resource, $action, $decoded)
    {
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->getMock();
        $authorization = $this->getMockBuilder(Authorization::class)
            ->setConstructorArgs([$request])
            ->setMethods(['getKey', 'getSecret', 'decodeToken' ])
            ->getMock();

        $request->method('getQueryParams')
            ->willReturn([ 'api_key' => 'test-key' ]);
        $request->expects($this->any())->method('getHeaderLine')
            ->willReturnMap([
                [ 'Authorization', 'test-authorization' ],
                [ 'Stream-Auth-Type', 'jwt' ],
            ]);
        $authorization->method('getKey')
            ->willReturn('test-key');
        $authorization->method('decodeToken')
            ->willReturn($decoded);

        $this->expectException(AuthorizationFailedException::class);
        $authorization->authorize($feedSlug, $userId, $resource, $action);
    }

    public function provide_value_for_invalidSimpleAuthorization()
    {
        return [
            [ 'foo' ],
            [ 'foo bar baz' ],
        ];
    }

    /**
     * @covers \Pho\Stream\Authorization::authorize
     * @dataProvider provide_value_for_invalidSimpleAuthorization
     */
    public function test_authorize_throws_AuthorizationFailedException_when_stream_auth_type_is_simple_and_invalid_authorization_is_provided($invalidSimpleAuthorization)
    {
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->getMock();
        $authorization = $this->getMockBuilder(Authorization::class)
            ->setConstructorArgs([$request])
            ->setMethods(['getKey', 'getSecret' ])
            ->getMock();

        $request->method('getQueryParams')
            ->willReturn([ 'api_key' => 'test-key' ]);
        $request->expects($this->any())->method('getHeaderLine')
            ->willReturnMap([
                [ 'Authorization', $invalidSimpleAuthorization ],
                [ 'Stream-Auth-Type', 'simple' ],
            ]);
        $authorization->method('getKey')
            ->willReturn('test-key');

        $this->expectException(AuthorizationFailedException::class);
        $authorization->authorize('sample_feed_slug', '123', 'test_resource', 'some_action');
    }

    public function provide_data_for_feedSlug_userId_feedId()
    {
        return [
            [ 'test-feed-slug', 'test-user-id', 'feed-user-id-feed-slug' ],
        ];
    }

    /**
     * @covers \Pho\Stream\Authorization::authorize
     * @dataProvider provide_data_for_feedSlug_userId_feedId
     */
    public function test_authorize_throws_AuthorizationFailedException_when_stream_auth_type_is_simple_and_feedId_does_not_match($feedSlug, $userId, $feedId)
    {
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->getMock();
        $authorization = $this->getMockBuilder(Authorization::class)
            ->setConstructorArgs([$request])
            ->setMethods(['getKey', 'getSecret' ])
            ->getMock();

        $request->method('getQueryParams')
            ->willReturn([ 'api_key' => 'test-key' ]);
        $request->expects($this->any())->method('getHeaderLine')
            ->willReturnMap([
                [ 'Authorization', "$feedId test-token" ],
                [ 'Stream-Auth-Type', 'simple' ],
            ]);
        $authorization->method('getKey')
            ->willReturn('test-key');

        $this->expectException(AuthorizationFailedException::class);
        $authorization->authorize($feedSlug, $userId, 'test_resource', 'some_action');
    }

    public function provide_data_for_token_and_signature()
    {
        return [
            [ 'foo', 'bar' ],
            [ 'abc', 'xyz' ],
        ];
    }

    /**
     * @covers \Pho\Stream\Authorization::authorize
     * @dataProvider provide_data_for_feedSlug_userId_feedId
     */
    public function test_authorize_throws_AuthorizationFailedException_when_stream_auth_type_is_simple_and_token_does_not_match($token, $signature)
    {
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->getMock();
        $authorization = $this->getMockBuilder(Authorization::class)
            ->setConstructorArgs([$request])
            ->setMethods(['getKey', 'getSecret', 'signature' ])
            ->getMock();

        $request->method('getQueryParams')
            ->willReturn([ 'api_key' => 'test-key' ]);
        $request->expects($this->any())->method('getHeaderLine')
            ->willReturnMap([
                [ 'Authorization', "sample_feed_slug123 {$token}" ],
                [ 'Stream-Auth-Type', 'simple' ],
            ]);
        $authorization->method('getKey')
            ->willReturn('test-key');
        $authorization->method('getSecret')
            ->willReturn('test-secret');
        $authorization->method('signature')
            ->with('sample_feed_slug123', 'test-secret')
            ->willReturn($signature);

        $this->expectException(AuthorizationFailedException::class);
        $authorization->authorize('sample_feed_slug', '123', 'test_resource', 'some_action');
    }
}
