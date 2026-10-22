<?php

namespace Drupal\Tests\search_api_solr_admin\Unit;

use Drupal\Core\Session\AccountInterface;
use Drupal\search_api\ServerInterface;
use Drupal\search_api_solr\SolrBackendInterface;
use Drupal\search_api_solr\SolrConnectorInterface;
use Drupal\search_api_solr_admin\Access\SolrAdminAccessCheck;
use Drupal\search_api_solr_admin\Access\SolrAdminCloudAccessCheck;
use Drupal\search_api_solr_admin\Access\SolrAdminTrustedContextSupportedAccessCheck;
use Drupal\Tests\UnitTestCase;

/**
 * Tests Solr admin route access checks.
 *
 * @group search_api_solr
 *
 * @coversDefaultClass \Drupal\search_api_solr_admin\Access\SolrAdminAccessCheck
 */
class SolrAdminAccessCheckTest extends UnitTestCase {

  /**
   * Tests that core admin routes are only allowed for non-cloud connectors.
   *
   * @covers ::access
   */
  public function testAdminAccessCheck(): void {
    $access_check = new SolrAdminAccessCheck();
    $account = $this->createMock(AccountInterface::class);

    $this->assertTrue($access_check->access($account, $this->createServer(FALSE))->isAllowed());
    $this->assertTrue($access_check->access($account, $this->createServer(TRUE))->isForbidden());
    $this->assertTrue($access_check->access($account)->isForbidden());
  }

  /**
   * Tests that cloud admin routes are only allowed for cloud connectors.
   *
   * @covers \Drupal\search_api_solr_admin\Access\SolrAdminCloudAccessCheck::access
   */
  public function testCloudAccessCheck(): void {
    $access_check = new SolrAdminCloudAccessCheck();
    $account = $this->createMock(AccountInterface::class);

    $this->assertTrue($access_check->access($account, $this->createServer(TRUE))->isAllowed());
    $this->assertTrue($access_check->access($account, $this->createServer(FALSE))->isForbidden());
    $this->assertTrue($access_check->access($account)->isForbidden());
  }

  /**
   * Tests that trusted context routes require connector support.
   *
   * @covers \Drupal\search_api_solr_admin\Access\SolrAdminTrustedContextSupportedAccessCheck::access
   */
  public function testTrustedContextSupportedAccessCheck(): void {
    $access_check = new SolrAdminTrustedContextSupportedAccessCheck();
    $account = $this->createMock(AccountInterface::class);

    $this->assertTrue($access_check->access($account, $this->createServer(FALSE, TRUE))->isAllowed());
    $this->assertTrue($access_check->access($account, $this->createServer(FALSE, FALSE))->isForbidden());
    $this->assertTrue($access_check->access($account)->isForbidden());
  }

  /**
   * Creates a Search API server mock with a Solr backend and connector.
   */
  private function createServer(bool $is_cloud, bool $trusted_context_supported = FALSE): ServerInterface {
    $connector = $this->createMock(SolrConnectorInterface::class);
    $connector->method('isCloud')->willReturn($is_cloud);
    $connector->method('isTrustedContextSupported')->willReturn($trusted_context_supported);

    $backend = $this->createMock(SolrBackendInterface::class);
    $backend->method('getSolrConnector')->willReturn($connector);

    $server = $this->createMock(ServerInterface::class);
    $server->method('getBackend')->willReturn($backend);

    return $server;
  }

}
