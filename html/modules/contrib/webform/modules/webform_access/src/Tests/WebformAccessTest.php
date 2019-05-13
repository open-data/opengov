<?php

namespace Drupal\webform_access\Tests;

use Drupal\field\Entity\FieldConfig;

/**
 * Tests for webform access.
 *
 * @group WebformAccess
 */
class WebformAccessTest extends WebformAccessTestBase {

  /**
   * Tests webform access.
   */
  public function testWebformAccess() {
    $nid = $this->nodes['contact_01']->id();

    $this->drupalLogin($this->rootUser);

    // Check that employee and manager groups exist.
    $this->drupalGet('/admin/structure/webform/access/group/manage');
    $this->assertLink('employee_group');
    $this->assertLink('manager_group');

    // Check that webform node is assigned to groups.
    $this->assertLink($this->nodes['contact_01']->label());

    // Check that employee and manager users can't access webform results.
    foreach ($this->users as $account) {
      $this->drupalLogin($account);
      $this->drupalGet("/node/$nid/webform/results/submissions");
      $this->assertResponse(403);
    }

    // Assign users to groups via the UI.
    $this->drupalLogin($this->rootUser);
    foreach ($this->groups as $name => $group) {
      $this->drupalPostForm(
        "/admin/structure/webform/access/group/manage/$name",
        ['users[]' => $this->users[$name]->id()],
        t('Save')
      );
    }

    // Check that manager and employee users can access webform results.
    foreach (['manager', 'employee'] as $name) {
      $account = $this->users[$name];
      $this->drupalLogin($account);
      $this->drupalGet("/node/$nid/webform/results/submissions");
      $this->assertResponse(200);
    }

    // Check that employee can't delete results.
    $this->drupalLogin($this->users['employee']);
    $this->drupalGet("/node/$nid/webform/results/clear");
    $this->assertResponse(403);

    // Check that manager can delete results.
    $this->drupalLogin($this->users['manager']);
    $this->drupalGet("/node/$nid/webform/results/clear");
    $this->assertResponse(200);

    // Unassign employee user from employee group via the UI.
    $this->drupalLogin($this->rootUser);
    $this->drupalPostForm(
      '/admin/structure/webform/access/group/manage/employee',
      ['users[]' => 1],
      t('Save')
    );

    // Assign employee user to manager group via the UI.
    $this->drupalLogin($this->rootUser);
    $this->drupalPostForm(
      '/user/' . $this->users['employee']->id() . '/edit',
      ['webform_access_group[]' => 'manager'],
      t('Save')
    );

    // Check that employee can now delete results.
    $this->drupalLogin($this->users['employee']);
    $this->drupalGet("/node/$nid/webform/results/clear");
    $this->assertResponse(200);

    // Unassign node from groups.
    $this->drupalLogin($this->rootUser);
    foreach ($this->groups as $name => $group) {
      $this->drupalPostForm(
        "/admin/structure/webform/access/group/manage/$name",
        ['entities[]' => 'node:' . $this->nodes['contact_02']->id() . ':webform:contact'],
        t('Save')
      );
    }

    // Check that employee can't access results.
    $this->drupalLogin($this->users['employee']);
    $this->drupalGet("/node/$nid/webform/results/clear");
    $this->assertResponse(403);

    // Assign webform node to group via the UI.
    $this->drupalLogin($this->rootUser);
    $this->drupalPostForm(
      "/node/$nid/edit",
      ['webform[0][settings][webform_access_group][]' => 'manager'],
      t('Save')
    );

    // Check that employee can now access results.
    $this->drupalLogin($this->users['employee']);
    $this->drupalGet("/node/$nid/webform/results/clear");
    $this->assertResponse(200);

    // Delete employee group.
    $this->groups['employee']->delete();

    // Check that employee group is configured.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/admin/structure/webform/access/group/manage');
    $this->assertRaw('manager_type');
    $this->assertLink('manager_group');
    $this->assertLink('manager_user');
    $this->assertLink('employee_user');
    $this->assertLink('contact_01');
    $this->assertLink('contact_02');

    // Reset caches.
    \Drupal::entityTypeManager()->getStorage('webform_access_group')->resetCache();
    \Drupal::entityTypeManager()->getStorage('webform_access_type')->resetCache();

    // Delete types.
    foreach ($this->types as $type) {
      $type->delete();
    }

    // Check that manager type has been removed.
    $this->drupalGet('/admin/structure/webform/access/group/manage');
    $this->assertNoRaw('manager_type');

    // Delete users.
    foreach ($this->users as $user) {
      $user->delete();
    }

    // Check that manager type has been removed.
    $this->drupalGet('/admin/structure/webform/access/group/manage');
    $this->assertNoLink('manager_user');
    $this->assertNoLink('employee_user');

    // Delete contact 2.
    $this->nodes['contact_02']->delete();

    // Check that contact_02 has been removed.
    $this->drupalGet('/admin/structure/webform/access/group/manage');
    $this->assertNoLink('contact_02');

    // Delete webform field config.
    FieldConfig::loadByName('node', 'webform', 'webform')->delete();

    // Check that contact_02 has been removed.
    $this->drupalGet('/admin/structure/webform/access/group/manage');
    $this->assertNoLink('contact_02');
  }

}
