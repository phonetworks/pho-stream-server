<?php

namespace Pho\Stream\Controller;

use Pho\Stream\Authorization;
use Pho\Stream\Exception\ValidationFailedException;
use Pho\Stream\Model\FeedModel;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

class FeedControllerTest extends TestCase
{
    public function provide_data_for_invalidBodyContents_in_addActivity()
    {
        return [
            [
                [],
            ],
            [
                [ 'actor' => '', 'verb' => 'verb', 'object' => 'object' ],
            ],
            [
                [ 'actor' => 'actor', 'verb' => '', 'object' => 'object' ],
            ],
            [
                [ 'actor' => 'actor', 'verb' => 'verb', 'object' => '' ],
            ],
            [
                [ 'actor' => 'actor', 'verb' => 'verb', 'object' => 'object', 'time' => '2018-11-26' ],
            ],
            [
                [ 'actor' => 'actor', 'verb' => 'verb', 'object' => 'object', 'time' => '2018-11-26T22:34:40' ],
            ],
        ];
    }

    /**
     * @covers \Pho\Stream\Controller\FeedController::addActivity
     * @dataProvider provide_data_for_invalidBodyContents_in_addActivity
     */
    public function test_addActivity_throws_ValidationFailedException_when_the_data_provided_is_not_valid($invalidBodyContents)
    {
        $feedModel = $this->getMockBuilder(FeedModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $authorization = $this->getMockBuilder(Authorization::class)
            ->disableOriginalConstructor()
            ->getMock();
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->getMock();
        $body = $this->getMockBuilder(StreamInterface::class)
            ->getMock();
        $feedController = new FeedController($feedModel, $authorization);

        $authorization->expects($this->once())
            ->method('authorize');
        $request->method('getBody')
            ->willReturn($body);
        $body->method('getContents')
            ->willReturn(json_encode($invalidBodyContents));

        $this->expectException(ValidationFailedException::class);
        $feedController->addActivity('feed_slug', 'user_id', $request);
    }

    public function provide_data_for_validBodyContents_in_addActivity()
    {
        return [
            [
                [ 'actor' => 'actor', 'verb' => 'verb', 'object' => 'object', 'time' => '2018-11-26T22:34:40.000' ],
            ],
            [
                [ 'actor' => 'user100', 'verb' => 'likes', 'object' => 'photo202', 'attachment' => 'photo1.jpg' ],
            ],
        ];
    }

    /**
     * @covers \Pho\Stream\Controller\FeedController::addActivity
     * @dataProvider provide_data_for_validBodyContents_in_addActivity
     */
    public function test_addActivity_returns_response_when_the_data_provided_is_valid($validBodyContents)
    {
        $feedModel = $this->getMockBuilder(FeedModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['addActivity'])
            ->getMock();
        $authorization = $this->getMockBuilder(Authorization::class)
            ->disableOriginalConstructor()
            ->getMock();
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->getMock();
        $body = $this->getMockBuilder(StreamInterface::class)
            ->getMock();
        $feedController = new FeedController($feedModel, $authorization);

        $feedSlug = 'timeline';
        $userId = '300';
        $authorization->expects($this->once())
            ->method('authorize');
        $request->method('getBody')
            ->willReturn($body);
        $body->method('getContents')
            ->willReturn(json_encode($validBodyContents));
        $feedModel->expects($this->once())
            ->method('addActivity');

        $response = $feedController->addActivity($feedSlug, $userId, $request);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function provide_data_for_invalidBodyContents_in_follow()
    {
        return [
            [
                'feed-slug', 'user-id', [],
            ],
            [
                'feed-slug', 'user-id', [ 'target' => '' ],
            ],
            [
                'feed-slug', 'user-id', [ 'target' => 'feed-slug:user-id' ], // cannot follow itself
            ],
        ];
    }

    /**
     * @covers \Pho\Stream\Controller\FeedController::follow
     * @dataProvider provide_data_for_invalidBodyContents_in_follow
     */
    public function test_follow_throws_ValidationFailedException_when_the_data_provided_is_not_valid($feedSlug, $userId, $invalidBodyContents)
    {
        $feedModel = $this->getMockBuilder(FeedModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $authorization = $this->getMockBuilder(Authorization::class)
            ->disableOriginalConstructor()
            ->getMock();
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->getMock();
        $body = $this->getMockBuilder(StreamInterface::class)
            ->getMock();
        $feedController = new FeedController($feedModel, $authorization);

        $authorization->expects($this->once())
            ->method('authorize');
        $request->method('getBody')
            ->willReturn($body);
        $body->method('getContents')
            ->willReturn(json_encode($invalidBodyContents));

        $this->expectException(ValidationFailedException::class);
        $feedController->follow($feedSlug, $userId, $request);
    }

    public function provide_data_for_validBodyContents_in_follow()
    {
        return [
            [
                'test-feed', [ 'target' => 'other-feed' ],
            ],
            [
                'foo', [ 'target' => 'bar' ],
            ],
        ];
    }

    /**
     * @covers \Pho\Stream\Controller\FeedController::follow
     * @dataProvider provide_data_for_validBodyContents_in_follow
     */
    public function test_follow_returns_response_when_the_data_provided_is_valid($feed, $validBodyContents)
    {
        $feedModel = $this->getMockBuilder(FeedModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $authorization = $this->getMockBuilder(Authorization::class)
            ->disableOriginalConstructor()
            ->getMock();
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->getMock();
        $body = $this->getMockBuilder(StreamInterface::class)
            ->getMock();
        $feedController = new FeedController($feedModel, $authorization);

        $authorization->expects($this->once())
            ->method('authorize');
        $request->method('getBody')
            ->willReturn($body);
        $body->method('getContents')
            ->willReturn(json_encode($validBodyContents));
        $feedModel->expects($this->once())
            ->method('feedExists')
            ->willReturn(true);
        $feedModel->expects($this->once())
            ->method('follow');

        $response = $feedController->follow('feed_slug', 'user_id', $request);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function provide_data_for_invalidQueryParameters_in_get()
    {
        return [
            [
                [ 'limit' => '', 'offset' => '' ],
            ],
            [
                [ 'limit' => 'a' ],
            ],
            [
                [ 'offset' => 'a' ],
            ],
            [
                [ 'limit' => '0', 'offset' => '10' ],
            ],
            [
                [ 'limit' => '10', 'offset' => '-1' ],
            ],
        ];
    }

    /**
     * @covers \Pho\Stream\Controller\FeedController::get
     * @dataProvider provide_data_for_invalidQueryParameters_in_get
     */
    public function test_get_throws_ValidationFailedException_when_the_query_parameter_is_not_valid($queryParams)
    {
        $feedModel = $this->getMockBuilder(FeedModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $authorization = $this->getMockBuilder(Authorization::class)
            ->disableOriginalConstructor()
            ->getMock();
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->getMock();
        $feedController = new FeedController($feedModel, $authorization);

        $authorization->expects($this->once())
            ->method('authorize');
        $request->method('getQueryParams')
            ->willReturn($queryParams);

        $this->expectException(ValidationFailedException::class);
        $feedController->get('feed_slug', 'user_id', $request);
    }

    public function provide_data_for_validQueryParameters_in_get()
    {
        return [
            [
                [],
            ],
            [
                [ 'limit' => '10', 'offset' => '10' ],
            ],
            [
                [ 'limit' => '10' ],
            ],
            [
                [ 'offset' => '10' ],
            ],
        ];
    }

    /**
     * @covers \Pho\Stream\Controller\FeedController::get
     * @dataProvider provide_data_for_validQueryParameters_in_get
     */
    public function test_get_returns_response_when_the_query_parameter_is_not_valid($queryParams)
    {
        $feedModel = $this->getMockBuilder(FeedModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $authorization = $this->getMockBuilder(Authorization::class)
            ->disableOriginalConstructor()
            ->getMock();
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->getMock();
        $feedController = new FeedController($feedModel, $authorization);

        $authorization->expects($this->once())
            ->method('authorize');
        $request->method('getQueryParams')
            ->willReturn($queryParams);
        $feedModel->expects($this->once())
            ->method('get')
            ->willReturn([]);

        $response = $feedController->get('feed_slug', 'user_id', $request);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}
