<?php

use PHPUnit\Framework\TestCase;
use GraphQL\Type\Schema;
use Fawaz\GraphQLSchemaBuilder;
use Tests\Helper\GraphQLSchemaBuilderHelper;

class GraphQLSchemaBuilderTest extends TestCase

{
    use GraphQLSchemaBuilderHelper;
    protected $schemaBuilder;
    protected $tokenDouble;
    protected $userMapperDouble;
    protected $fileReader;
    protected $resolveUsers;
    protected $userService;
    protected $resolveSearchUser;
    protected function setUp(): void
    {
        $dependicies = $this->createDependencies($this);
        $this->userMapperDouble = $dependicies['userMapper'];
        $this->tokenDouble = $dependicies['tokenService'];
        $this->fileReader = $dependicies['fileReader'];
        $this->userService= $dependicies['userService'];
        $this->schemaBuilder = new GraphQLSchemaBuilder(...$dependicies);
        $this->resolveUsers = $this->schemaBuilder->buildResolvers()['Query']['getallusers'];
        $this->resolveSearchUser = $this->schemaBuilder->buildResolvers()['Query']['searchuser'];

    }

    public function testBuildSchemaForGuestUser()
    {

        $expectedSchemaPath = dirname(__DIR__, levels: 2) . '/' . 'schemaguest.graphl';
        $this->fileReader->method('getFileContents')->with($expectedSchemaPath)->willReturn(file_get_contents($expectedSchemaPath));
        $schema = $this->schemaBuilder->build();

        $this->assertInstanceOf(Schema::class, $schema);
    }

    public function testBuildSchemaForRegularUser()
    {
        $this->sharedCorrectSchemaUsed(1, 0, 'schema.graphl');
    }

    public function testBuildSchemaForAdminUser()
    {
        $this->sharedCorrectSchemaUsed(1, 1, 'admin_schema.graphl');
    }

    public function testResolveUsersUnauthorized()
    {
        $result = (($this->resolveUsers))(null, []);
        $this->assertEquals(['status' => 'error', 'ResponseCode' => 'Unauthorized'], $result);
    }

    public function testResolveUsersRoleNot16()
    {
        $this->stubCurrentUserAndRole(1, 0, $this->schemaBuilder, $this, $this->userMapperDouble, $this->tokenDouble);

        $this->userService->method('fetchAll')->willReturn(['user1', 'user2']);

        $result = ($this->resolveUsers)(null, ['some' => 'args']);

        $this->assertEquals(['user1', 'user2'], $result);
    }

    public function testResolveUsersRole16()
    {

        $this->stubCurrentUserAndRole(1, 16, $this->schemaBuilder, $this, $this->userMapperDouble, $this->tokenDouble);

        $this->userService->method('fetchAllAdvance')->willReturn(['user1', 'user2', 'user3']);

        $result = ($this->resolveUsers)(null, ['some' => 'args']);

        $this->assertEquals(['user1', 'user2', 'user3'], $result);
    }

    public function testResolveSearchUsersUnauthorizedAccess()
    {
        $result = ($this->resolveSearchUser)(null, []);
        $this->assertEquals(['status' => 'error', 'ResponseCode' => 'Unauthorized'], $result);
    }

    public function testResolveSearchUsersNoArgumentsProvided()
    {
        $this->checkResolveSearchUserArgsValidity([], 'No arguments provided. Please provide valid input parameters.');
    }

    public function testResolveSearchUsersUsernameTooShort()
    {
        $this->checkResolveSearchUserArgsValidity(['username' => 'ab'],  'Username must be between 3 and 23 characters');
    }

    public function testResolveSearchUsersUsernameWring()
    {
        $this->checkResolveSearchUserArgsValidity(['username' => 'ab!~'],  'Username must only contain letters and numbers');
    }

    public function testResolveSearchUsersInvalidUUID()
    {
        $this->checkResolveSearchUserArgsValidity(['userid' => 'invaliduuid'],  'Invalid uuid input');
    }

    public function testResolveSearchUsersUsernameAndUserIdProvided()
    {
        $this->checkResolveSearchUserArgsValidity(['username' => 'testuser', 'userid' => '550e8400-e29b-41d4-a716-446655440000'],  'args username and userId arguments not allowed');
    }

    public function testResolveSearchUsersInValidIP()
    {
        $this->checkResolveSearchUserArgsValidity(['username' => 'testuser', 'ip' => '12'],  "The IP '12' is not a valid IP address.");
    }

    public function testResolveSearchUsersValidUserSearch()
    {
        $this->stubCurrentUserAndRole(1, 0, $this->schemaBuilder, $this, $this->userMapperDouble, $this->tokenDouble);
        $this->userService->method('fetchAllAdvance')->with(['username' => 'validuser', 'limit' => 10])->willReturn([
            ['id' => '123', 'username' => 'validuser']
        ]);

        $result = ($this->resolveSearchUser)(null, ['username' => 'validuser']);
        $this->assertCount(1, $result);
        $this->assertEquals('validuser', $result[0]['username']);
    }

    public function testResolveSearchUsersNoUsersFound()
    {
        $this->stubCurrentUserAndRole(1, 0, $this->schemaBuilder, $this, $this->userMapperDouble, $this->tokenDouble);
        $this->userService->method('fetchAllAdvance')->willReturn([]);

        $result = ($this->resolveSearchUser)(null, ['username' => 'unknownuser']);
        $this->assertEquals(['status' => 'error', 'ResponseCode' => 'No users founds'], $result);
    }
}
