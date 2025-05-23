<?php

namespace Drupal\Tests\password_policy_username\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Tests the password username constraint.
 *
 * @group password_policy_username
 */
class PasswordUsernameTest extends UnitTestCase {

  /**
   * Tests the password to make sure it doesn't contain the user's username.
   *
   * @dataProvider passwordUsernameDataProvider
   */
  public function testPasswordUsername($disallow_username, $password, $result) {
    $username_test = $this->getMockBuilder('Drupal\password_policy_username\Plugin\PasswordConstraint\PasswordUsername')
      ->disableOriginalConstructor()
      ->onlyMethods(['getConfiguration', 't'])
      ->getMock();

    $username_test
      ->method('getConfiguration')
      ->willReturn(['disallow_username' => $disallow_username]);

    $user = $this->createMock('Drupal\user\Entity\User');
    $user->method('getAccountName')->willReturn('username');
    $this->assertEquals($username_test->validate($password, $user)->isValid(), $result);
  }

  /**
   * Provides data for the testPasswordUsername method.
   */
  public static function passwordUsernameDataProvider(): array {
    return [
      // Passing conditions.
      [
        TRUE,
        'password',
        TRUE,
      ],
      [
        FALSE,
        'username',
        TRUE,
      ],
      // Failing conditions.
      [
        TRUE,
        'username',
        FALSE,
      ],
      [
        TRUE,
        'my_username',
        FALSE,
      ],
    ];
  }

}
